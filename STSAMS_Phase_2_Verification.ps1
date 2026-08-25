Set-Location "$PSScriptRoot\backend"
php artisan --version
composer show laravel/sanctum
composer show spatie/laravel-permission
php artisan migrate:status
php artisan test
Write-Host 'Verification complete.'
