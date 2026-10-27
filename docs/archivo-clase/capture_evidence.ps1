param(
    [string]$BaseUrl = 'http://localhost/PruebaFet',
    [string]$ChromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe',
    [string]$OutputDir = '',
    [string]$FilePrefix = 'EV-'
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $ChromePath)) {
    throw "No se encontro Google Chrome en $ChromePath"
}

$root = Split-Path -Parent $PSScriptRoot
if ($OutputDir -eq '') {
    $OutputDir = Join-Path $root 'docs\evidencias'
}
$profileDir = Join-Path $PSScriptRoot 'chrome-evidence-profile'
New-Item -ItemType Directory -Force -Path $OutputDir, $profileDir | Out-Null

$cases = @(
    'TC-LOGIN-001',
    'TC-LOGIN-002',
    'TC-LOGIN-003',
    'TC-LOGIN-004',
    'TC-LOGIN-005',
    'TC-LOGIN-006',
    'TC-LOGIN-007',
    'TC-LOGIN-008',
    'TC-LOGIN-009',
    'TC-LOGIN-010',
    'TC-LOGIN-011',
    'TC-LOGIN-012'
)

foreach ($caseId in $cases) {
    $url = $BaseUrl.TrimEnd('/') + '/tests/rendered/' + $caseId + '.html'
    $destination = Join-Path $OutputDir ($FilePrefix + $caseId + '.png')
    & $ChromePath `
        --headless=new `
        --disable-gpu `
        --hide-scrollbars `
        --no-first-run `
        --disable-extensions `
        --window-size=1900,900 `
        --virtual-time-budget=1000 `
        --user-data-dir="$profileDir" `
        --screenshot="$destination" `
        $url | Out-Null

    if (-not (Test-Path -LiteralPath $destination)) {
        throw "No se genero la evidencia de $caseId"
    }
}

Get-ChildItem -LiteralPath $OutputDir -Filter ($FilePrefix + 'TC-LOGIN-*.png') |
    Select-Object Name, Length, LastWriteTime
