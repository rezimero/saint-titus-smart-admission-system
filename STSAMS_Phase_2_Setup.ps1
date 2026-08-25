$ErrorActionPreference = 'Stop'
Write-Host 'STSAMS Phase 2 Identity Security Core Setup'
Set-Location "$PSScriptRoot\backend"
composer require laravel/sanctum
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
php artisan migrate
Write-Host 'Phase 2 setup complete.'
