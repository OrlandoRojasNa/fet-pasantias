param(
    [string]$BaseUrl = 'http://localhost/PruebaFet',
    [string]$OutputCsv = "$PSScriptRoot/blackbox-results.csv",
    [string]$EvidenceHtmlDir = "$PSScriptRoot/rendered"
)

$ErrorActionPreference = 'Stop'
$results = [System.Collections.Generic.List[object]]::new()
New-Item -ItemType Directory -Force -Path $EvidenceHtmlDir | Out-Null

function New-AppSession {
    return New-Object Microsoft.PowerShell.Commands.WebRequestSession
}

function Get-Page {
    param(
        [string]$Path,
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session
    )

    return Invoke-WebRequest -UseBasicParsing -Uri ($BaseUrl.TrimEnd('/') + '/' + $Path.TrimStart('/')) -WebSession $Session
}

function Get-CsrfToken {
    param([string]$Content)

    $match = [regex]::Match($Content, 'name="csrf_token" value="([a-f0-9]{64})"')
    if (-not $match.Success) {
        throw 'No se encontró el token CSRF en la respuesta.'
    }
    return $match.Groups[1].Value
}

function Invoke-Login {
    param(
        [string]$Identifier,
        [string]$Password,
        [bool]$Remember = $false,
        [Microsoft.PowerShell.Commands.WebRequestSession]$Session
    )

    $loginPage = Get-Page -Path 'index.php' -Session $Session
    $body = @{
        csrf_token = Get-CsrfToken -Content $loginPage.Content
        identifier = $Identifier
        password = $Password
    }
    if ($Remember) {
        $body.remember = '1'
    }

    return Invoke-WebRequest -UseBasicParsing -Uri ($BaseUrl.TrimEnd('/') + '/index.php') -Method Post -Body $body -WebSession $Session
}

function Add-Result {
    param(
        [string]$Id,
        [string]$Expected,
        [string]$Actual,
        [bool]$Passed
    )

    $results.Add([pscustomobject]@{
        ID = $Id
        Esperado = $Expected
        ResultadoActual = $Actual
        Estado = if ($Passed) { 'Pass' } else { 'Fail' }
        EjecutadoEn = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')
    })
}

function Save-EvidenceHtml {
    param(
        [string]$Id,
        [string]$Content
    )

    $path = Join-Path $EvidenceHtmlDir ($Id + '.html')
    [System.IO.File]::WriteAllText($path, $Content, [System.Text.UTF8Encoding]::new($false))
}

try {
    $session = New-AppSession
    $response = Invoke-Login -Identifier '1076502055' -Password 'Fet2026*' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-001' $response.Content
    $pass = $response.Content -match '<h1>Bienvenido,'
    Add-Result 'TC-LOGIN-001' 'Redirige a Inicio y crea una sesion activa.' 'La respuesta mostro el panel de Inicio con sesion activa.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier 'estudiante@fet.edu.co' -Password 'Fet2026*' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-002' $response.Content
    $pass = $response.Content -match '<h1>Bienvenido,'
    Add-Result 'TC-LOGIN-002' 'Permite el acceso con el correo registrado.' 'El acceso por correo mostro el panel de Inicio.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier '1076502055' -Password 'Incorrecta1!' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-003' $response.Content
    $pass = $response.Content -match 'Usuario o contrase'
    Add-Result 'TC-LOGIN-003' 'Rechaza la clave incorrecta con mensaje generico.' 'El sistema permanecio en Login y mostro el mensaje esperado.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier '9999999999' -Password 'Fet2026*' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-004' $response.Content
    $pass = $response.Content -match 'Usuario o contrase'
    Add-Result 'TC-LOGIN-004' 'Rechaza un usuario no registrado sin revelar su inexistencia.' 'El sistema mostro el mismo mensaje generico de credenciales.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier '' -Password 'Fet2026*' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-005' $response.Content
    $pass = $response.Content -match 'El usuario o correo es obligatorio'
    Add-Result 'TC-LOGIN-005' 'No envia el login y avisa que el usuario/correo es obligatorio.' 'El formulario mostro la validacion de campo obligatorio.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier '1076502055' -Password '' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-006' $response.Content
    $pass = $response.Content -match 'La contrase'
    Add-Result 'TC-LOGIN-006' 'No inicia sesion sin contrasena.' 'El formulario mostro la validacion de contrasena obligatoria.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier 'correo-invalido' -Password 'Fet2026*' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-007' $response.Content
    $pass = ($response.Content -match 'correo v') -and ($response.Content -match '6 a 20 d')
    Add-Result 'TC-LOGIN-007' 'Rechaza formatos que no son correo ni identificacion.' 'El sistema mostro el mensaje de formato invalido.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier "' OR 1=1 --" -Password 'x' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-008' $response.Content
    $pass = ($response.Content -match 'correo v') -and ($response.Content -notmatch '<h1>Bienvenido,')
    Add-Result 'TC-LOGIN-008' 'La entrada SQL no autentica ni altera el flujo.' 'La cadena fue rechazada por validacion y no inicio sesion.' $pass

    $session = New-AppSession
    $payload = '<script>alert(1)</script>'
    $response = Invoke-Login -Identifier $payload -Password 'x' -Session $session
    Save-EvidenceHtml 'TC-LOGIN-009' $response.Content
    $pass = ($response.Content -notmatch [regex]::Escape($payload)) -and ($response.Content -match '&lt;script&gt;')
    Add-Result 'TC-LOGIN-009' 'El contenido HTML se muestra escapado y no se ejecuta.' 'La entrada se devolvio codificada como texto seguro.' $pass

    $session = New-AppSession
    $response = Invoke-Login -Identifier '1076502055' -Password 'Fet2026*' -Remember $true -Session $session
    $rememberCookie = $session.Cookies.GetCookies(($BaseUrl.TrimEnd('/') + '/'))['fet_remember']
    $freshSession = New-AppSession
    if ($rememberCookie) {
        $copy = New-Object System.Net.Cookie($rememberCookie.Name, $rememberCookie.Value, $rememberCookie.Path, $rememberCookie.Domain)
        $freshSession.Cookies.Add($copy)
    }
    $restored = Get-Page -Path 'dashboard.php' -Session $freshSession
    Save-EvidenceHtml 'TC-LOGIN-010' $restored.Content
    $restoredDashboard = $restored.Content -match '<h1>Bienvenido,'
    $pass = ($null -ne $rememberCookie) -and $restoredDashboard
    $actual10 = "Cookie persistente=$($null -ne $rememberCookie); panel restaurado=$restoredDashboard."
    Add-Result 'TC-LOGIN-010' 'Recordarme restaura la sesion sin conservar la contrasena.' $actual10 $pass

    $session = New-AppSession
    $dashboard = Invoke-Login -Identifier '1076502055' -Password 'Fet2026*' -Session $session
    $logoutToken = Get-CsrfToken -Content $dashboard.Content
    $logout = Invoke-WebRequest -UseBasicParsing -Uri ($BaseUrl.TrimEnd('/') + '/logout.php') -Method Post -Body @{ csrf_token = $logoutToken } -WebSession $session
    Save-EvidenceHtml 'TC-LOGIN-011' $logout.Content
    $protected = Get-Page -Path 'dashboard.php' -Session $session
    $logoutMessage = $logout.Content -match 'se cerr'
    $accessBlocked = $protected.Content -match 'Debes iniciar sesi'
    $pass = $logoutMessage -and $accessBlocked
    $actual11 = "Mensaje de cierre=$logoutMessage; acceso posterior bloqueado=$accessBlocked."
    Add-Result 'TC-LOGIN-011' 'Cerrar sesion invalida el acceso al panel protegido.' $actual11 $pass

    $session = New-AppSession
    $recoveryPage = Get-Page -Path 'forgot-password.php' -Session $session
    $recovery = Invoke-WebRequest -UseBasicParsing -Uri ($BaseUrl.TrimEnd('/') + '/forgot-password.php') -Method Post -Body @{
        csrf_token = Get-CsrfToken -Content $recoveryPage.Content
        email = 'estudiante@fet.edu.co'
    } -WebSession $session
    Save-EvidenceHtml 'TC-LOGIN-012' $recovery.Content
    $pass = ($recovery.Content -match 'se gener') -and ($recovery.Content -match 'solicitud de recuperaci')
    Add-Result 'TC-LOGIN-012' 'Registra la solicitud y muestra una respuesta que no revela cuentas.' 'La solicitud fue procesada con un mensaje generico de seguridad.' $pass
}
catch {
    Add-Result 'ERROR-EJECUCION' 'La suite debe completarse.' $_.Exception.Message $false
}

$results | Export-Csv -LiteralPath $OutputCsv -NoTypeInformation -Encoding UTF8
$results | Format-Table -AutoSize

$failed = @($results | Where-Object Estado -ne 'Pass').Count
Write-Output "Total: $($results.Count) | Pass: $($results.Count - $failed) | Fail: $failed"
if ($failed -gt 0) {
    exit 1
}
