#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory)][string]$Repository)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$root = (Resolve-Path -LiteralPath $Repository).Path
$backend = Join-Path $root 'backend'
$paths = @(
    'app/Providers/StsamsAuthorizationServiceProvider.php',
    'database/seeders/RolePermissionSeeder.php',
    'app/Models/StsamsStaffAccessAudit.php',
    'database/migrations/2026_11_01_000001_create_stsams_staff_access_audits_table.php',
    'app/Modules/Administration/Controllers/StaffAccessController.php',
    'app/Modules/Administration/Services/StaffAccessService.php',
    'app/Modules/Admissions/Controllers/AdmissionReviewController.php',
    'app/Modules/Admissions/Controllers/PortalAuthController.php',
    'app/Modules/Admissions/Services/AdmissionWorkflowService.php',
    'app/Policies/StsamsAdmissionApplicationPolicy.php',
    'app/Modules/Institutional/Controllers/WebsiteAdminController.php',
    'routes/stsams_staff_access.php',
    'bootstrap/providers.php',
    'routes/web.php'
)
foreach ($p in $paths) {
    $full = Join-Path $backend $p
    if (-not (Test-Path -LiteralPath $full -PathType Leaf)) { throw "Missing: $full" }
    & php -l $full
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax error in $p" }
}
Push-Location $backend
try {
    & php artisan route:list --path=system-admin
    if ($LASTEXITCODE -ne 0) { throw 'System admin routes not registered.' }
    & php artisan route:list --path=admissions/staff
    if ($LASTEXITCODE -ne 0) { throw 'Admissions staff routes failed.' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Blade compilation failed.' }
    & php artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'Migration status check failed.' }
} finally { Pop-Location }
Push-Location $root
try {
    & git diff --check
    if ($LASTEXITCODE -ne 0) { throw 'Git whitespace errors found.' }
} finally { Pop-Location }
Write-Host 'Non-destructive verification completed. Functional tests and MySQL migration still pending.'
