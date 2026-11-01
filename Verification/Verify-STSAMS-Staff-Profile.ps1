#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory=$true)][string]$Repository)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$root = (Resolve-Path -LiteralPath $Repository).Path
$backend = Join-Path $root 'backend'
$files = @(
'backend/app/Modules/Administration/Controllers/StaffProfileController.php',
'backend/app/Http/Middleware/RequireStaffPasswordChange.php',
'backend/database/migrations/2026_11_01_000002_add_staff_self_service_and_credential_lifecycle.php',
'backend/routes/stsams_staff_profile.php',
'backend/app/Modules/Administration/Services/StaffAccessService.php',
'backend/app/Providers/StsamsAuthorizationServiceProvider.php',
'backend/tests/Feature/Administration/StaffSelfServiceTest.php'
)
Push-Location $backend
try {
    foreach($f in $files) {
        $full = Join-Path $root $f
        & php -l $full
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed: $f" }
    }
    & php artisan route:list --path=account
    if ($LASTEXITCODE -ne 0) { throw 'Staff profile routes failed.' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Blade template compilation failed.' }
    & php artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'Migration status check failed.' }
} finally { Pop-Location }
& git -C $root diff --check
if ($LASTEXITCODE -ne 0) { throw 'Git whitespace validation failed.' }
Write-Host 'Source/route/view verification completed. Real MySQL migration and protected staff workflow require separate acceptance.'
