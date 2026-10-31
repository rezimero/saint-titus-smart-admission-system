#requires -Version 7.0
[CmdletBinding()]
param(
    [switch]$ProvisionSuperadmin
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$repo = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$backend = Join-Path $repo 'backend'
if (-not (Test-Path (Join-Path $backend 'artisan') -PathType Leaf)) {
    throw "Laravel backend not found at $backend"
}
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'PHP must be available on PATH.'
}

# Only activate a local development environment with this script.
$envFile = Join-Path $backend '.env'
if (-not (Test-Path $envFile -PathType Leaf)) { throw 'Missing backend/.env.' }
$envLine = Get-Content $envFile | Where-Object { $_ -match '^\s*APP_ENV\s*=' } | Select-Object -Last 1
if (-not $envLine) { throw 'APP_ENV is not declared in backend/.env; refusing to seed.' }
$appEnv = ([string](($envLine -split '=', 2) | Select-Object -Last 1)).Trim().Trim('"').Trim("'")
if ($appEnv -ne 'local') {
    throw "Refusing to seed APP_ENV=$appEnv. This package's activation script is for local development."
}
$processAppEnv = [Environment]::GetEnvironmentVariable('APP_ENV', 'Process')
if ($processAppEnv -and $processAppEnv -ne 'local') {
    throw "Process APP_ENV=$processAppEnv differs from local. Refusing to seed."
}
if (Test-Path (Join-Path $backend 'bootstrap/cache/config.php') -PathType Leaf) {
    throw 'Configuration is cached. Inspect configuration and run php artisan config:clear before activation.'
}

Push-Location $backend
try {
    Write-Host 'Review the connection and migration status (read-only):' -ForegroundColor Cyan
    & php artisan migrate:status
    if ($LASTEXITCODE -ne 0) { throw 'Migration status failed.' }

    Write-Host 'Seeding canonical roles/permissions (no administrator is created)...' -ForegroundColor Cyan
    & php artisan db:seed --class=RolePermissionSeeder --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Authorization seeding failed.' }

    & php artisan permission:show
    if ($LASTEXITCODE -ne 0) { throw 'Permission inspection failed.' }

    if ($ProvisionSuperadmin) {
        $email = (Read-Host 'New superadmin email').Trim()
        $name = (Read-Host 'New superadmin display name').Trim()
        $secure = Read-Host 'New strong superadmin password (minimum 16 characters)' -AsSecureString
        $password = [System.Net.NetworkCredential]::new('', $secure).Password
        try {
            $env:STSAMS_SUPERADMIN_EMAIL = $email
            $env:STSAMS_SUPERADMIN_NAME = $name
            $env:STSAMS_SUPERADMIN_PASSWORD = $password
            Write-Host 'Provisioning initial superadmin without exposing credentials...' -ForegroundColor Cyan
            & php artisan db:seed --class=SuperadminSeeder --no-interaction
            if ($LASTEXITCODE -ne 0) { throw 'Superadmin provisioning failed. Review the error before retrying.' }
        } finally {
            Remove-Item Env:STSAMS_SUPERADMIN_EMAIL -ErrorAction SilentlyContinue
            Remove-Item Env:STSAMS_SUPERADMIN_NAME -ErrorAction SilentlyContinue
            Remove-Item Env:STSAMS_SUPERADMIN_PASSWORD -ErrorAction SilentlyContinue
            Remove-Variable password, secure -ErrorAction SilentlyContinue
        }
    } else {
        Write-Host 'Superadmin was NOT provisioned. Run with -ProvisionSuperadmin after tests pass.' -ForegroundColor Yellow
    }
    Write-Host 'STSAMS authorization seeding completed.' -ForegroundColor Green
} finally {
    Pop-Location
}
