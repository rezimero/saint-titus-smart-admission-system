param([Parameter(Mandatory=$true)][string]$Repository)
$ErrorActionPreference='Stop'
$repo=(Resolve-Path -LiteralPath $Repository).Path
$backend=Join-Path $repo 'backend'
$patchFiles=@(
    'app\Modules\Admissions\Controllers\DocumentController.php',
    'app\Modules\Admissions\Controllers\PaymentController.php',
    'app\Modules\Admissions\Controllers\PaystackWebhookController.php',
    'app\Modules\Admissions\Services\DocumentRequirements.php',
    'app\Modules\Admissions\Services\DocumentScanService.php',
    'app\Modules\Admissions\Services\PaystackGateway.php',
    'app\Modules\Admissions\Services\ApplicationPaymentService.php',
    'app\Modules\Admissions\Services\AdmissionWorkflowService.php',
    'database\migrations\2026_10_31_200001_add_admission_document_and_payment_foundation.php',
    'config\stsams_documents.php',
    'config\stsams_payments.php'
)
foreach ($f in $patchFiles) {
    & php -l (Join-Path $backend $f)
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed: $f" }
}
Push-Location $backend
try {
    & php artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'Config clear failed' }
    & php artisan route:list --path=admissions
    if ($LASTEXITCODE -ne 0) { throw 'Admissions routes failed' }
    & php artisan route:list --path=api/v1/payments
    if ($LASTEXITCODE -ne 0) { throw 'Paystack webhook route failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Blade compilation failed' }
    & php artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect migrations' }
} finally { Pop-Location }
Write-Host 'Static integration checks completed. Does not run PHPUnit, migrations or payments.'
Write-Host 'Use dedicated stsams_test schema for destructive RefreshDatabase tests; NEVER run them on saint_titus_stsams.'
