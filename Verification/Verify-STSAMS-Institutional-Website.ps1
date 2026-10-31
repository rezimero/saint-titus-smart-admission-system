#requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory=$true)][string]$Repository, [switch]$Live)
$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path -LiteralPath $Repository).Path
$backend = Join-Path $repo 'backend'
if (!(Test-Path (Join-Path $backend 'artisan'))) { throw 'Not a Laravel STSAMS backend.' }
$phpFiles = @(
 'config\stsams_institution.php',
 'routes\stsams_institution.php',
 'app\Models\InstitutionContent.php',
 'app\Models\InstitutionPost.php',
 'app\Models\InstitutionMedia.php',
 'app\Models\InstitutionInquiry.php',
 'app\Modules\Institutional\Controllers\PublicWebsiteController.php',
 'app\Modules\Institutional\Controllers\WebsiteAdminController.php',
 'database\migrations\2026_10_31_190001_create_stsams_institutional_website_tables.php'
)
foreach ($rel in $phpFiles) {
 $file = Join-Path $backend $rel
 if (!(Test-Path -LiteralPath $file)) { throw "Missing required file: $rel" }
 & php -l $file
 if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed: $rel" }
}
foreach ($rel in @('public\institutional\saint-titus-official-logo.jpg','public\institutional\icons\favicon.ico','public\institutional\website.css')) {
 if (!(Test-Path -LiteralPath (Join-Path $backend $rel))) { throw "Missing brand asset: $rel" }
}
Push-Location $backend
try {
 & php artisan config:clear
 if ($LASTEXITCODE -ne 0) { throw 'Laravel config clear failed' }
 Write-Host 'Registered public site routes:'
 & php artisan route:list --name=institution.
 if ($LASTEXITCODE -ne 0) { throw 'Institutional routes not registered' }
 Write-Host 'Current migration status:'
 & php artisan migrate:status
 if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect migration status' }
} finally { Pop-Location }
if ($Live) {
 $base = 'http://127.0.0.1:8000'
 foreach ($path in @('/', '/about','/academics','/admissions-info','/campus-life','/news-events','/administration','/contact','/admissions')) {
  $resp = Invoke-WebRequest -Uri ($base + $path) -UseBasicParsing -MaximumRedirection 4
  if ($resp.StatusCode -ne 200) { throw "Unexpected HTTP status $($resp.StatusCode) at $path" }
  Write-Host "OK $path"
 }
}
Write-Host 'Website verification completed; this is not a production security audit.'
