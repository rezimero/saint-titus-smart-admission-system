#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory)][string]$Repository)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$repo = (Resolve-Path -LiteralPath $Repository).Path
$backend = Join-Path $repo 'backend'
Push-Location $backend
try {
    $files = @(
        'app/Modules/Admissions/Controllers/AdmissionReviewController.php',
        'app/Modules/Admissions/Services/AdmissionWorkflowService.php',
        'app/Modules/Institutional/Controllers/WebsiteAdminController.php',
        'app/Modules/Operations/Controllers/AssignmentController.php',
        'app/Modules/Operations/Controllers/DocumentReviewController.php',
        'app/Modules/Operations/Controllers/OperationsAuditController.php',
        'app/Modules/Operations/Services/AssignmentService.php',
        'app/Modules/Operations/Services/DocumentReviewService.php',
        'app/Modules/Operations/Services/OperationsAudit.php',
        'app/Models/StsamsOperationsAudit.php',
        'app/Models/StsamsAdmissionApplication.php',
        'app/Http/Middleware/StsamsPublicLaunchGate.php',
        'database/seeders/RolePermissionSeeder.php',
        'database/migrations/2026_11_01_000003_add_operational_controls_and_document_reviews.php',
        'routes/stsams_operations.php', 'routes/stsams_institution.php',
        'config/stsams_launch.php'
    )
    foreach ($file in $files) {
        & php -l $file
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $file" }
    }
    foreach ($command in @(@('artisan','route:list','--path=admissions/staff'),
                          @('artisan','route:list','--path=system-admin/audit'),
                          @('artisan','route:list','--path=institution-admin/content'),
                          @('artisan','view:cache'), @('artisan','migrate:status'))) {
        & php @command
        if ($LASTEXITCODE -ne 0) { throw "Artisan command failed: $($command -join ' ')" }
    }
    Write-Host 'Non-destructive integration verification passed. Tests, migration and browser acceptance remain separate.'
} finally { Pop-Location }
