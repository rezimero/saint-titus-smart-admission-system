$ErrorActionPreference = 'Stop'

Write-Host 'STSAMS Phase 1 Identity Production Completion v2 Setup'

Set-Location "$PSScriptRoot\..\backend"

composer dump-autoload

php artisan optimize:clear

Write-Host 'Setup preparation complete.'
