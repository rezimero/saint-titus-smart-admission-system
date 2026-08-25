
$ErrorActionPreference = "Stop"

Write-Host "======================================"
Write-Host "STSAMS Phase 1 Laravel Backend Setup"
Write-Host "======================================"

$backend = Join-Path (Get-Location) "backend"

if (!(Test-Path $backend)) {
    New-Item -ItemType Directory -Path $backend | Out-Null
}

Set-Location $backend

Write-Host "Checking Laravel requirements..."
php -v
composer -V

if (!(Test-Path "artisan")) {
    Write-Host "Creating Laravel application..."
    composer create-project laravel/laravel .
}
else {
    Write-Host "Laravel application already exists."
}

Write-Host "Generating application key..."
php artisan key:generate

Write-Host "Creating storage link..."
php artisan storage:link

Write-Host "Laravel backend initialization completed."
