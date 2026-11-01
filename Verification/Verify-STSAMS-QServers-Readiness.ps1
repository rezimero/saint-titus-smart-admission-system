#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory)][string]$Repository)
$ErrorActionPreference = 'Stop'
$backend = Join-Path $Repository 'backend'
if (-not (Test-Path -LiteralPath (Join-Path $backend 'artisan'))) { throw 'Laravel backend missing.' }
Push-Location $backend
try {
    & php -v
    if ($LASTEXITCODE -ne 0) { throw 'PHP not available.' }
    & php -m | Select-String -Pattern '^pdo_mysql$|^openssl$|^mbstring$|^fileinfo$|^curl$|^dom$|^xml$' -CaseSensitive:$false
    & composer --version
    if ($LASTEXITCODE -ne 0) { throw 'Composer unavailable.' }
    Write-Host 'Local environment inspected. QServers SERVER capabilities, document root, offsite backups and TLS still require provider confirmation.'
    Write-Host 'Do not copy the local .env or development applicant records to a public hosting account.'
} finally { Pop-Location }
