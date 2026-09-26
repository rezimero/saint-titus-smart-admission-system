$ErrorActionPreference="Stop"
Write-Host "STSAMS Phase 2B Verification"

Set-Location "$PSScriptRoot\..\backend"

php artisan --version
composer show laravel/sanctum
composer show spatie/laravel-permission
php artisan migrate:status
php artisan route:list --path=api
php artisan test

Write-Host "Verification completed"
