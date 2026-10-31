#requires -Version 7.0
[CmdletBinding()]
param(
    [string]$Repository = 'C:\Projects\saint-titus-smart-admission-system',
    [string]$TestDatabase = 'stsams_test'
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# RefreshDatabase calls migrate:fresh when necessary. Never allow it near a normal DB.
if ($TestDatabase -cnotmatch '^[A-Za-z][A-Za-z0-9_]*_(test|testing)$') {
    throw 'TestDatabase must end in _test or _testing and contain only letters, digits and underscores.'
}
$backend = Join-Path $Repository 'backend'
if (-not (Test-Path (Join-Path $backend 'artisan') -PathType Leaf)) {
    throw "Laravel artisan was not found under $backend"
}
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'PHP is not on PATH.' }
$envPath = Join-Path $backend '.env'
if (-not (Test-Path $envPath -PathType Leaf)) { throw 'Backend .env file not found. Refusing to run tests.' }
$mainDbLine = @(Get-Content $envPath | Where-Object { $_ -match '^\s*DB_DATABASE\s*=' } | Select-Object -Last 1)
if ($mainDbLine.Count -ne 1) { throw 'Cannot determine primary DB_DATABASE from backend/.env. Refusing tests.' }
$primaryDb = (($mainDbLine[0] -split '=', 2)[1]).Trim().Trim('"').Trim("'")
if ([string]::IsNullOrWhiteSpace($primaryDb)) { throw 'Primary DB_DATABASE is empty. Refusing tests.' }
if ($primaryDb -ieq $TestDatabase) { throw 'Test database is the same as the primary database. Refusing tests.' }

& php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) { throw 'pdo_mysql must be enabled in the PHP CLI.' }
if (Test-Path (Join-Path $backend 'bootstrap/cache/config.php') -PathType Leaf) {
    throw 'Cached Laravel configuration detected. Inspect and run php artisan config:clear before isolated tests.'
}
$phpFiles = @(
    'database/seeders/RolePermissionSeeder.php',
    'database/seeders/SuperadminSeeder.php',
    'database/seeders/DatabaseSeeder.php',
    'app/Modules/Identity/Services/RegistrationService.php',
    'tests/Feature/Identity/AuthorizationSeederTest.php',
    'tests/Feature/Identity/SuperadminSeederTest.php',
    'tests/Feature/Identity/AuthBoundaryTest.php'
)
foreach ($relative in $phpFiles) {
    $fullPath = Join-Path $backend $relative
    if (-not (Test-Path $fullPath -PathType Leaf)) { throw "Missing required PHP file: $relative" }
    & php -l $fullPath
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax error: $relative" }
}

# A disposable PHPUnit config lives alongside the original so relative paths remain correct.
$xmlSource = Join-Path $backend 'phpunit.xml'
if (-not (Test-Path $xmlSource -PathType Leaf)) { throw 'Missing backend/phpunit.xml.' }
$temporaryXml = Join-Path $backend ('.stsams_mysql_testing_' + [guid]::NewGuid().ToString('N') + '.xml')
$temporaryProbe = Join-Path ([System.IO.Path]::GetTempPath()) ('stsams_mysql_probe_' + [guid]::NewGuid().ToString('N') + '.php')
$originalEnv = @{}
$keys = @(
    'APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'CACHE_STORE',
    'SESSION_DRIVER', 'QUEUE_CONNECTION', 'MAIL_MAILER',
    'STSAMS_BACKEND_PATH', 'STSAMS_TEST_DATABASE', 'STSAMS_PRIMARY_DATABASE'
)
foreach ($key in $keys) { $originalEnv[$key] = [Environment]::GetEnvironmentVariable($key, 'Process') }

$probeCode = @'
<?php
$root = getenv('STSAMS_BACKEND_PATH');
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$default = config('database.default');
$actual = \Illuminate\Support\Facades\DB::connection()->selectOne('SELECT DATABASE() AS db_name')->db_name;
$expected = getenv('STSAMS_TEST_DATABASE');
$primary = getenv('STSAMS_PRIMARY_DATABASE');
$env = config('app.env');
if ($default !== 'mysql' || $env !== 'testing' || $actual !== $expected || strcasecmp($actual, $primary) === 0) {
    fwrite(STDERR, "UNSAFE test connection configuration; refusing automated migrations.\n");
    exit(8);
}
fwrite(STDOUT, "Safe MySQL test database verified: " . $actual . " (APP_ENV=testing)\n");
'@

Push-Location $backend
try {
    & php artisan route:list --path=api
    if ($LASTEXITCODE -ne 0) { throw 'Laravel API route inspection failed.' }

    $env:APP_ENV = 'testing'
    $env:DB_CONNECTION = 'mysql'
    $env:DB_DATABASE = $TestDatabase
    $env:DB_URL = ''
    $env:CACHE_STORE = 'array'
    $env:SESSION_DRIVER = 'array'
    $env:QUEUE_CONNECTION = 'sync'
    $env:MAIL_MAILER = 'array'
    $env:STSAMS_BACKEND_PATH = $backend
    $env:STSAMS_TEST_DATABASE = $TestDatabase
    $env:STSAMS_PRIMARY_DATABASE = $primaryDb

    # Override Laravel's stock PHPUnit SQLite settings without changing phpunit.xml.
    [xml]$config = Get-Content -LiteralPath $xmlSource -Raw
    $phpNode = $config.SelectSingleNode('/phpunit/php')
    if ($null -eq $phpNode) {
        $phpNode = $config.CreateElement('php')
        [void]$config.DocumentElement.AppendChild($phpNode)
    }
    $settings = [ordered]@{
        APP_ENV = 'testing'
        DB_CONNECTION = 'mysql'
        DB_DATABASE = $TestDatabase
        DB_URL = ''
        CACHE_STORE = 'array'
        SESSION_DRIVER = 'array'
        QUEUE_CONNECTION = 'sync'
        MAIL_MAILER = 'array'
    }
    foreach ($name in $settings.Keys) {
        $existing = @($phpNode.SelectNodes("*[@name='$name']"))
        foreach ($element in $existing) { [void]$phpNode.RemoveChild($element) }
        $element = $config.CreateElement('env')
        $element.SetAttribute('name', $name)
        $element.SetAttribute('value', [string]$settings[$name])
        $element.SetAttribute('force', 'true')
        [void]$phpNode.AppendChild($element)
    }
    $config.Save($temporaryXml)
    [System.IO.File]::WriteAllText($temporaryProbe, $probeCode, [System.Text.UTF8Encoding]::new($false))

    Write-Host "Checking dedicated MySQL testing schema '$TestDatabase' (read-only)..." -ForegroundColor Cyan
    & php $temporaryProbe
    if ($LASTEXITCODE -ne 0) {
        throw "MySQL test database preflight failed. Create '$TestDatabase' on the configured MySQL server, grant your development DB account access, then rerun. No migrations or tests were attempted."
    }

    Write-Host 'Executing STSAMS authorization tests on the separate MySQL TEST schema...' -ForegroundColor Cyan
    & php artisan test --configuration=$temporaryXml '--filter=AuthorizationSeederTest|SuperadminSeederTest|AuthBoundaryTest' --stop-on-failure
    if ($LASTEXITCODE -ne 0) { throw 'Authorization tests failed; production database was not intentionally targeted.' }
    Write-Host 'STSAMS Phase 2 MySQL authorization verification PASSED.' -ForegroundColor Green
} finally {
    foreach ($key in $keys) {
        $value = $originalEnv[$key]
        if ($null -eq $value) {
            [Environment]::SetEnvironmentVariable($key, $null, 'Process')
        } else {
            [Environment]::SetEnvironmentVariable($key, $value, 'Process')
        }
    }
    Remove-Item -LiteralPath $temporaryXml -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $temporaryProbe -ErrorAction SilentlyContinue
    Pop-Location
}
