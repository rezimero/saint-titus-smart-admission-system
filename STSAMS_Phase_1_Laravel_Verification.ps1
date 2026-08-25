
$ErrorActionPreference = "Continue"

Write-Host "STSAMS Phase 1 Laravel Verification"

Set-Location backend

Write-Host "`nLaravel Version:"
php artisan --version

Write-Host "`nComposer Dependencies:"
composer show laravel/framework

Write-Host "`nEnvironment:"
if (Test-Path ".env") {
    Write-Host "[OK] .env exists"
} else {
    Write-Host "[MISSING] .env"
}

Write-Host "`nApplication Key:"
php artisan key:generate --show

Write-Host "`nMigration Status:"
php artisan migrate:status

Write-Host "`nVerification Complete"
