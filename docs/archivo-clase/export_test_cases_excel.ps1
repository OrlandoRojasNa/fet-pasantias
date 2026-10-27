param(
    [string]$SourceCsv = '',
    [string]$OutputBaseName = 'Test_Cases_Login_Completo'
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
if ($SourceCsv -eq '') {
    $SourceCsv = Join-Path $root 'docs\Test_Cases_Login.csv'
}
$source = $SourceCsv
$xlsxOutput = Join-Path $root ('docs\' + $OutputBaseName + '.xlsx')
$csvOutput = Join-Path $root ('docs\' + $OutputBaseName + '.csv')

# Lee el CSV aun si el usuario lo tiene abierto en Excel.
$stream = New-Object System.IO.FileStream(
    $source,
    [System.IO.FileMode]::Open,
    [System.IO.FileAccess]::Read,
    [System.IO.FileShare]::ReadWrite
)
$reader = New-Object System.IO.StreamReader($stream, [System.Text.Encoding]::UTF8, $true)
try {
    $csvText = $reader.ReadToEnd()
}
finally {
    $reader.Dispose()
    $stream.Dispose()
}

$firstLine = ($csvText -split "`r?`n", 2)[0]
$detectedDelimiter = if (($firstLine.ToCharArray() | Where-Object { $_ -eq ';' }).Count -gt ($firstLine.ToCharArray() | Where-Object { $_ -eq ',' }).Count) { ';' } else { ',' }
$rows = @($csvText | ConvertFrom-Csv -Delimiter $detectedDelimiter)
if ($rows.Count -eq 0) {
    throw 'El archivo no contiene casos de prueba.'
}

if ($OutputBaseName -eq 'Test_Cases_Login_Completo') {
    foreach ($row in $rows) {
        $row.Evidencia = 'EV-' + $row.ID + '.png'
    }
}

$headers = @($rows[0].PSObject.Properties.Name)

function Convert-ToCsvCell {
    param([AllowNull()][object]$Value)
    $text = if ($null -eq $Value) { '' } else { [string]$Value }
    return '"' + $text.Replace('"', '""') + '"'
}

# Variante compatible con la configuracion regional de Excel en espanol.
$csvLines = New-Object System.Collections.Generic.List[string]
$csvLines.Add((($headers | ForEach-Object { Convert-ToCsvCell $_ }) -join ';'))
foreach ($row in $rows) {
    $values = foreach ($header in $headers) {
        Convert-ToCsvCell $row.$header
    }
    $csvLines.Add(($values -join ';'))
}
$utf8Bom = New-Object System.Text.UTF8Encoding($true)
[System.IO.File]::WriteAllText($csvOutput, (($csvLines -join "`r`n") + "`r`n"), $utf8Bom)

$excel = $null
$workbook = $null
$casesSheet = $null
$summarySheet = $null

try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false

    $workbook = $excel.Workbooks.Add()
    $casesSheet = $workbook.Worksheets.Item(1)
    $casesSheet.Name = 'Casos de prueba'
    while ($workbook.Worksheets.Count -gt 1) {
        $extraSheet = $workbook.Worksheets.Item($workbook.Worksheets.Count)
        $extraSheet.Delete()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($extraSheet) | Out-Null
    }

    $rowCount = $rows.Count + 1
    $columnCount = $headers.Count
    $matrix = New-Object 'object[,]' $rowCount, $columnCount

    for ($column = 0; $column -lt $columnCount; $column++) {
        $matrix[0, $column] = $headers[$column]
    }

    for ($rowIndex = 0; $rowIndex -lt $rows.Count; $rowIndex++) {
        $targetRow = $rowIndex + 1
        for ($column = 0; $column -lt $columnCount; $column++) {
            $property = $headers[$column]
            $matrix[$targetRow, $column] = [string]$rows[$rowIndex].$property
        }
    }

    $dataRange = $casesSheet.Range(
        $casesSheet.Cells.Item(1, 1),
        $casesSheet.Cells.Item($rowCount, $columnCount)
    )
    $dataRange.Value2 = $matrix
    $dataRange.VerticalAlignment = -4160
    $dataRange.WrapText = $true
    $dataRange.Borders.LineStyle = 1
    $dataRange.Borders.Color = 0xD9D9D9

    $headerRange = $casesSheet.Range(
        $casesSheet.Cells.Item(1, 1),
        $casesSheet.Cells.Item(1, $columnCount)
    )
    $headerRange.Font.Bold = $true
    $headerRange.Font.Color = 0xFFFFFF
    $headerRange.Interior.Color = 0x165707
    $headerRange.HorizontalAlignment = -4108
    $headerRange.RowHeight = 32
    $headerRange.AutoFilter() | Out-Null

    $widths = @(18, 32, 18, 13, 26, 34, 34, 52, 45, 45, 12, 35)
    for ($column = 1; $column -le $columnCount; $column++) {
        $casesSheet.Columns.Item($column).ColumnWidth = $widths[$column - 1]
    }

    $casesSheet.Rows.Item("2:$rowCount").AutoFit() | Out-Null
    for ($rowNumber = 2; $rowNumber -le $rowCount; $rowNumber++) {
        if ($casesSheet.Rows.Item($rowNumber).RowHeight -gt 90) {
            $casesSheet.Rows.Item($rowNumber).RowHeight = 90
        }
        $stateCell = $casesSheet.Cells.Item($rowNumber, 11)
        if ($stateCell.Value2 -eq 'Pass') {
            $stateCell.Interior.Color = 0xD7F0DC
            $stateCell.Font.Color = 0x216E2A
            $stateCell.Font.Bold = $true
            $stateCell.HorizontalAlignment = -4108
        }
        elseif ($stateCell.Value2 -eq 'Fail') {
            $stateCell.Interior.Color = 0xD9D9FF
            $stateCell.Font.Color = 0x1823B4
            $stateCell.Font.Bold = $true
            $stateCell.HorizontalAlignment = -4108
        }
        elseif ($stateCell.Value2 -eq 'Blocked') {
            $stateCell.Interior.Color = 0xBFE5FF
            $stateCell.Font.Color = 0x0066A3
            $stateCell.Font.Bold = $true
            $stateCell.HorizontalAlignment = -4108
        }
    }

    $casesSheet.Activate()
    $excel.ActiveWindow.SplitRow = 1
    $excel.ActiveWindow.FreezePanes = $true

    $summarySheet = $workbook.Worksheets.Add()
    $summarySheet.Name = 'Resumen'
    $summarySheet.Cells.Item(1, 1).Value2 = 'Resumen de ejecucion'
    $summarySheet.Cells.Item(1, 1).Font.Bold = $true
    $summarySheet.Cells.Item(1, 1).Font.Size = 18
    $summarySheet.Cells.Item(1, 1).Font.Color = 0x165707
    $summarySheet.Cells.Item(3, 1).Value2 = 'Total de casos'
    $summarySheet.Cells.Item(3, 2).Value2 = $rows.Count
    $summarySheet.Cells.Item(4, 1).Value2 = 'Pass'
    $summarySheet.Cells.Item(4, 2).Value2 = @($rows | Where-Object Estado -eq 'Pass').Count
    $summarySheet.Cells.Item(5, 1).Value2 = 'Fail'
    $summarySheet.Cells.Item(5, 2).Value2 = @($rows | Where-Object Estado -eq 'Fail').Count
    $summarySheet.Cells.Item(6, 1).Value2 = 'Blocked'
    $summarySheet.Cells.Item(6, 2).Value2 = @($rows | Where-Object Estado -eq 'Blocked').Count
    $summarySheet.Cells.Item(7, 1).Value2 = 'Casos negativos'
    $summarySheet.Cells.Item(7, 2).Value2 = @($rows | Where-Object Tipo -eq 'Negativo').Count
    $summarySheet.Range('A3:B7').Borders.LineStyle = 1
    $summarySheet.Range('A3:A7').Font.Bold = $true
    $summarySheet.Range('A3:A7').Interior.Color = 0xE8F4EA
    $summarySheet.Columns.Item(1).ColumnWidth = 24
    $summarySheet.Columns.Item(2).ColumnWidth = 14
    $passCount = @($rows | Where-Object Estado -eq 'Pass').Count
    $failCount = @($rows | Where-Object Estado -eq 'Fail').Count
    $blockedCount = @($rows | Where-Object Estado -eq 'Blocked').Count
    $summarySheet.Cells.Item(9, 1).Value2 = "Resultado: $passCount Pass, $failCount Fail, $blockedCount Blocked."
    $summarySheet.Cells.Item(9, 1).Font.Bold = $true
    $summarySheet.Cells.Item(9, 1).Font.Color = 0x216E2A

    $casesSheet.Activate()
    $workbook.SaveAs($xlsxOutput, 51)
}
finally {
    if ($workbook) {
        $workbook.Close($false)
    }
    if ($excel) {
        $excel.Quit()
    }
    foreach ($item in @($summarySheet, $casesSheet, $workbook, $excel)) {
        if ($item) {
            [System.Runtime.InteropServices.Marshal]::ReleaseComObject($item) | Out-Null
        }
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}

Get-Item -LiteralPath $xlsxOutput, $csvOutput | Select-Object Name, Length, LastWriteTime
