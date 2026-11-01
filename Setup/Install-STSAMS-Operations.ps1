#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory)][string]$Repository,[switch]$Preview)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Read-Normal([string]$path) {
    return [IO.File]::ReadAllText($path).Replace("`r`n", "`n")
}
function Hash-Text([string]$text) {
    $data = [Text.Encoding]::UTF8.GetBytes($text)
    return [Convert]::ToHexString([Security.Cryptography.SHA256]::HashData($data))
}
function Add-Edit([string]$rel,[string]$contents,[string]$source) {
    $target = Join-Path $root $rel
    $script:edits.Add([pscustomobject]@{ Relative=$rel; Target=$target; Content=$contents; Source=$source })
}
$root = (Resolve-Path -LiteralPath $Repository).Path
$pkg = Split-Path -Parent $PSScriptRoot
$patch = Join-Path $pkg 'Patch'
$baselines = Get-Content -LiteralPath (Join-Path $pkg 'BASELINE_SHA256.json') -Raw | ConvertFrom-Json -AsHashtable
if (-not (Test-Path (Join-Path $root '.git'))) { throw 'STSAMS Git repository not found. No changes made.' }
if (-not (Test-Path (Join-Path $root 'backend/database/migrations/2026_11_01_000002_add_staff_self_service_and_credential_lifecycle.php'))) {
    throw 'The staff-profile module must already be installed. No changes made.'
}
$edits = [System.Collections.Generic.List[object]]::new()
foreach ($f in Get-ChildItem -LiteralPath $patch -File -Recurse) {
    $rel = [IO.Path]::GetRelativePath($patch,$f.FullName)
    Add-Edit $rel $null $f.FullName
}

# All integration edits are computed and checked prior to ANY write.
$routes = 'backend/routes/web.php'
$routePath = Join-Path $root $routes
$routeText = Read-Normal $routePath
$line = "require __DIR__.'/stsams_operations.php';"
if (-not $routeText.Contains($line)) { $routeText = $routeText.TrimEnd("`n") + "`n" + $line + "`n" }
Add-Edit $routes $routeText $null

$provider = 'backend/app/Providers/StsamsAuthorizationServiceProvider.php'
$providerText = Read-Normal (Join-Path $root $provider)
$launchLine = "        `$this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\StsamsPublicLaunchGate::class);"
if (-not $providerText.Contains($launchLine)) {
    $anchor = '        Gate::before('
    if (-not $providerText.Contains($anchor)) { throw 'Authorization provider anchor missing; no changes made.' }
    $providerText = $providerText.Replace($anchor, $launchLine + "`n" + $anchor)
}
Add-Edit $provider $providerText $null

$institution = 'backend/routes/stsams_institution.php'
$siteRoutes = Read-Normal (Join-Path $root $institution)
$siteLine = "    Route::post('/content/{content}/publish', [WebsiteAdminController::class, 'publishContent'])->name('content.publish');"
if (-not $siteRoutes.Contains($siteLine)) {
    $anchor = "    Route::post('/content', [WebsiteAdminController::class, 'saveContent'])->name('content');"
    if (-not $siteRoutes.Contains($anchor)) { throw 'Institutional route anchor missing; no changes made.' }
    $siteRoutes = $siteRoutes.Replace($anchor, $anchor + "`n" + $siteLine)
}
Add-Edit $institution $siteRoutes $null

foreach ($f in @('Setup/Install-STSAMS-Operations.ps1','Verification/Verify-STSAMS-Operations.ps1',
    'Verification/Verify-STSAMS-QServers-Readiness.ps1','Verification/Test-STSAMS-Operations-MySQL.ps1','BASELINE_SHA256.json')) {
    Add-Edit $f $null (Join-Path $pkg $f)
}

# Refuse to overwrite unexpected local edits, even when the path is untracked by Git.
foreach ($entry in $edits) {
    if (-not [string]::IsNullOrWhiteSpace($entry.Source) -and -not (Test-Path -LiteralPath $entry.Source -PathType Leaf)) {
        throw "Missing package source: $($entry.Source). No changes made."
    }
    if (-not [string]::IsNullOrWhiteSpace($entry.Source)) {
        $name = $entry.Relative.Replace('\','/')
        $expectedHash = $baselines[$name]
        if (Test-Path -LiteralPath $entry.Target -PathType Leaf) {
            $currentHash = Hash-Text (Read-Normal $entry.Target)
            $patchHash = Hash-Text (Read-Normal $entry.Source)
            if ($currentHash -ne $patchHash -and $currentHash -ne $expectedHash) {
                throw "Local file differs from expected baseline: $name. No changes made. Back up and review manually."
            }
        } elseif ($expectedHash) {
            throw "Existing STSAMS file is missing: $name. No changes made."
        }
    }
}
Write-Host "Repository: $root"
Write-Host "Prepared changes: $($edits.Count)"
foreach ($entry in $edits) {
    $kind = if (Test-Path -LiteralPath $entry.Target) { 'UPDATE' } else { 'CREATE' }
    Write-Host "$kind $($entry.Relative)"
}
if ($Preview) { Write-Host 'Preview complete. No files, MySQL records, or Git branches changed.'; return }

$backup = Join-Path (Split-Path -Parent $root) ('STSAMS_Backups/operations_website_v1_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($entry in $edits) {
    if (Test-Path -LiteralPath $entry.Target -PathType Leaf) {
        $dest = Join-Path $backup $entry.Relative
        New-Item -ItemType Directory -Path (Split-Path $dest -Parent) -Force | Out-Null
        Copy-Item -LiteralPath $entry.Target -Destination $dest -Force
    }
}
$utf8 = [Text.UTF8Encoding]::new($false)
foreach ($entry in $edits) {
    New-Item -ItemType Directory -Path (Split-Path $entry.Target -Parent) -Force | Out-Null
    if (-not [string]::IsNullOrWhiteSpace($entry.Source)) { Copy-Item -LiteralPath $entry.Source -Destination $entry.Target -Force }
    else { [IO.File]::WriteAllText($entry.Target,$entry.Content,$utf8) }
}
Write-Host "Installation complete. External backups: $backup"
Write-Host 'No migrations, role seeders, live payments, or Git pushes were run.'
