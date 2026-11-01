param([Parameter(Mandatory=$true)][string]$Repository)
$ErrorActionPreference='Stop'
$repo=(Resolve-Path -LiteralPath $Repository).Path
$backend=Join-Path $repo 'backend'
Push-Location $backend
try {
    & php -l (Join-Path $backend 'app\Modules\Admissions\Controllers\CatalogueController.php')
    if ($LASTEXITCODE -ne 0) { throw 'Fee controller failed PHP syntax check.' }
    & php -l (Join-Path $backend 'tests\Feature\Admissions\AdmissionSessionFeeTest.php')
    if ($LASTEXITCODE -ne 0) { throw 'Fee test file failed PHP syntax check.' }
    & php artisan route:list --name=stsams.admin.session.fee
    if ($LASTEXITCODE -ne 0) { throw 'Session fee route unavailable.' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Blade compilation failed.' }
    & php artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'Migration status unavailable.' }
} finally { Pop-Location }
Write-Host 'Static checks passed. Reopen /admissions/admin/catalogue and set the institution-approved fee.'
Write-Host 'This script did not run destructive tests or modify MySQL data.'
