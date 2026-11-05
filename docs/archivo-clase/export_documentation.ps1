$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$source = (Resolve-Path (Join-Path $root 'docs\Documentacion_Pruebas_Login_FET.html')).Path
$docx = Join-Path $root 'docs\Documentacion_Pruebas_Login_FET.docx'
$pdf = Join-Path $root 'docs\Documentacion_Pruebas_Login_FET.pdf'
$chrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$chromeProfile = Join-Path $PSScriptRoot 'chrome-document-profile'

function Add-ZipTextEntry {
    param(
        [System.IO.Compression.ZipArchive]$Archive,
        [string]$Name,
        [string]$Content
    )

    $entry = $Archive.CreateEntry($Name, [System.IO.Compression.CompressionLevel]::Optimal)
    $stream = $entry.Open()
    $writer = New-Object System.IO.StreamWriter($stream, [System.Text.UTF8Encoding]::new($false))
    try {
        $writer.Write($Content)
    }
    finally {
        $writer.Dispose()
        $stream.Dispose()
    }
}

# Extrae una copia editable y estructurada del documento fuente.
$html = [System.IO.File]::ReadAllText($source)
$plainHtml = [regex]::Replace($html, '<style[^>]*>.*?</style>', '', 'IgnoreCase, Singleline')
$plainHtml = [regex]::Replace($plainHtml, '<img\s+src="([^"]+)"[^>]*>', '<p>Captura adjunta: $1</p>', 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '<h1[^>]*>', "`r`n# ", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '<h2[^>]*>', "`r`n## ", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '<h3[^>]*>', "`r`n### ", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '</h[1-3]>', "`r`n", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '<li[^>]*>', "`r`n- ", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '</li>', '', 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '</(p|tr|table|figure|section|ol|ul)>', "`r`n", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '</(td|th)>', ' | ', 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '<br\s*/?>', "`r`n", 'IgnoreCase')
$plainHtml = [regex]::Replace($plainHtml, '<[^>]+>', '', 'IgnoreCase')
$plainText = [System.Net.WebUtility]::HtmlDecode($plainHtml)
$lines = @($plainText -split "`r?`n" | ForEach-Object {
    ([regex]::Replace($_, '\s+', ' ')).Trim(' ', '|')
} | Where-Object { $_ -ne '' })

$paragraphs = New-Object System.Text.StringBuilder
foreach ($line in $lines) {
    $style = 'Normal'
    $text = $line
    if ($line.StartsWith('### ')) {
        $style = 'Heading2'
        $text = $line.Substring(4)
    }
    elseif ($line.StartsWith('## ')) {
        $style = 'Heading1'
        $text = $line.Substring(3)
    }
    elseif ($line.StartsWith('# ')) {
        $style = 'Title'
        $text = $line.Substring(2)
    }

    $escaped = [System.Security.SecurityElement]::Escape($text)
    [void]$paragraphs.Append('<w:p><w:pPr><w:pStyle w:val="')
    [void]$paragraphs.Append($style)
    [void]$paragraphs.Append('"/></w:pPr><w:r><w:t xml:space="preserve">')
    [void]$paragraphs.Append($escaped)
    [void]$paragraphs.Append('</w:t></w:r></w:p>')
}

$contentTypes = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
'@

$relationships = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
'@

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' +
    '<w:body>' + $paragraphs.ToString() +
    '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>' +
    '</w:body></w:document>'

$stylesXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/><w:qFormat/>
    <w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:sz w:val="21"/><w:szCs w:val="21"/></w:rPr>
    <w:pPr><w:spacing w:after="100" w:line="276" w:lineRule="auto"/></w:pPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
    <w:pPr><w:spacing w:before="180" w:after="180"/></w:pPr>
    <w:rPr><w:b/><w:color w:val="075716"/><w:sz w:val="38"/><w:szCs w:val="38"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
    <w:pPr><w:keepNext/><w:spacing w:before="260" w:after="120"/><w:outlineLvl w:val="0"/></w:pPr>
    <w:rPr><w:b/><w:color w:val="075716"/><w:sz w:val="30"/><w:szCs w:val="30"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
    <w:pPr><w:keepNext/><w:spacing w:before="200" w:after="80"/><w:outlineLvl w:val="1"/></w:pPr>
    <w:rPr><w:b/><w:color w:val="176021"/><w:sz w:val="25"/><w:szCs w:val="25"/></w:rPr>
  </w:style>
</w:styles>
'@

Add-Type -AssemblyName System.IO.Compression
$fileStream = [System.IO.File]::Open($docx, [System.IO.FileMode]::Create, [System.IO.FileAccess]::ReadWrite)
$archive = New-Object System.IO.Compression.ZipArchive($fileStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
try {
    Add-ZipTextEntry $archive '[Content_Types].xml' $contentTypes
    Add-ZipTextEntry $archive '_rels/.rels' $relationships
    Add-ZipTextEntry $archive 'word/document.xml' $documentXml
    Add-ZipTextEntry $archive 'word/styles.xml' $stylesXml
}
finally {
    $archive.Dispose()
    $fileStream.Dispose()
}

if (-not (Test-Path -LiteralPath $chrome)) {
    throw 'No se encontro Google Chrome para generar el PDF.'
}

New-Item -ItemType Directory -Force -Path $chromeProfile | Out-Null
& $chrome `
    --headless=new `
    --disable-gpu `
    --no-first-run `
    --disable-extensions `
    --no-pdf-header-footer `
    --virtual-time-budget=2000 `
    --user-data-dir="$chromeProfile" `
    --print-to-pdf="$pdf" `
    'http://localhost/PruebaFet/docs/Documentacion_Pruebas_Login_FET.html' | Out-Null

if (-not (Test-Path -LiteralPath $pdf)) {
    throw 'No se genero el PDF.'
}

Get-Item -LiteralPath $docx, $pdf | Select-Object Name, Length, LastWriteTime

