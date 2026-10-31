#requires -Version 7.0
[CmdletBinding()]
param([string]$Repository = 'C:\Projects\saint-titus-smart-admission-system')
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$backend = Join-Path $Repository 'backend'
if (!(Test-Path (Join-Path $backend 'artisan'))) { throw 'No Laravel backend found.' }
$files = @(Get-ChildItem (Join-Path $backend 'app/Modules/Admissions') -Filter *.php -File -Recurse)
$files += @(Get-ChildItem (Join-Path $backend 'app/Models') -Filter 'Stsams*.php' -File)
$files += @(Get-ChildItem (Join-Path $backend 'app/Policies') -Filter 'Stsams*.php' -File)
$files += @(Get-ChildItem (Join-Path $backend 'database/migrations') -Filter '*stsams*.php' -File)
foreach ($file in $files) {
    & php -l $file.FullName
    if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $($file.Name)" }
}
Push-Location $backend
try {
    & php artisan route:list --path=admissions
    if ($LASTEXITCODE -ne 0) { throw 'Web portal routes not registered correctly.' }
    & php artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'Migration status check failed.' }
} finally { Pop-Location }
Write-Host 'Source checks and route checks passed. Database writes were NOT executed.' -ForegroundColor Green
