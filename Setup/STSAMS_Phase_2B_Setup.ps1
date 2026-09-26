$ErrorActionPreference="Stop"
Write-Host "STSAMS Phase 2B Identity Core Setup"

Set-Location "$PSScriptRoot\..\backend"

composer install
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SuperadminSeeder
php artisan optimize:clear

Write-Host "STSAMS Phase 2B setup completed"
