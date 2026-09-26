$ErrorActionPreference = 'Stop'

Write-Host 'STSAMS Phase 1 Identity Production Completion Verification'

Set-Location "$PSScriptRoot\..\backend"

php artisan --version
php artisan route:list --path=api
php artisan migrate:status
php artisan test

Write-Host 'Verification complete.'
