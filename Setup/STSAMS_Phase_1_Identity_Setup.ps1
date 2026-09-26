$ErrorActionPreference = 'Stop'

Write-Host 'STSAMS Phase 1 Identity Completion Setup'

Set-Location "$PSScriptRoot\..\backend"

composer install

php artisan migrate

php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SuperadminSeeder

php artisan optimize:clear

Write-Host 'Identity completion setup finished.'
