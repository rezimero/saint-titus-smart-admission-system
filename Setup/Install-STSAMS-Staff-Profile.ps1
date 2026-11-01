#Requires -Version 7.0
[CmdletBinding()]
param(
    [Parameter(Mandatory=$true)][string] $Repository,
    [switch] $Preview
)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Read-Lf([string]$path) {
    return [System.IO.File]::ReadAllText($path).Replace("`r`n", "`n")
}
function Replace-ExactlyOnce([string]$text, [string]$old, [string]$replacement, [string]$label) {
    if ($text.Contains($replacement)) { return $text }
    $pos = $text.IndexOf($old, [System.StringComparison]::Ordinal)
    if ($pos -lt 0) { throw "Source version mismatch in ${label}. No repository files changed." }
    if ($text.IndexOf($old, $pos + $old.Length, [System.StringComparison]::Ordinal) -ge 0) {
        throw "Ambiguous repeated integration anchor in ${label}. No repository files changed."
    }
    return $text.Substring(0,$pos) + $replacement + $text.Substring($pos + $old.Length)
}

$root = (Resolve-Path -LiteralPath $Repository -ErrorAction Stop).Path
$pkg = (Split-Path -Parent $PSScriptRoot)
$patch = Join-Path $pkg 'Patch'
if (-not (Test-Path -LiteralPath (Join-Path $root '.git'))) { throw 'STSAMS Git repository not found.' }
$required = @(
  'backend/app/Modules/Administration/Services/StaffAccessService.php',
  'backend/app/Providers/StsamsAuthorizationServiceProvider.php',
  'backend/routes/web.php',
  'backend/resources/views/stsams/layout.blade.php',
  'backend/resources/views/institutional/layout.blade.php',
  'backend/public/stsams/portal.css',
  'backend/database/migrations/2026_11_01_000001_create_stsams_staff_access_audits_table.php'
)
foreach ($r in $required) { if (-not (Test-Path -LiteralPath (Join-Path $root $r))) { throw "Required existing STSAMS source missing: $r" } }
$changes = [System.Collections.Generic.List[object]]::new()
function Add-Change([string]$relative, [string]$content, [string]$source) {
    $target = Join-Path $root $relative
    $changes.Add([pscustomobject]@{Relative=$relative; Target=$target; Content=$content; Source=$source})
}

# All integration edits are calculated and validated in memory before ANY write.
$servicePath = Join-Path $root 'backend/app/Modules/Administration/Services/StaffAccessService.php'
$service = Read-Lf $servicePath
$service = Replace-ExactlyOnce $service "            DB::table('staff_profiles')->insert([" "            `$user->forceFill(['must_change_password' => true])->save();`n            DB::table('staff_profiles')->insert([" 'staff creation service'
Add-Change 'backend/app/Modules/Administration/Services/StaffAccessService.php' $service $null

$providerPath = Join-Path $root 'backend/app/Providers/StsamsAuthorizationServiceProvider.php'
$provider = Read-Lf $providerPath
$insertProvider = @'
        // Apply to all session-authenticated web workspaces, including website editors.
        $this->app['router']->pushMiddlewareToGroup('web', \Illuminate\Session\Middleware\AuthenticateSession::class);
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\RequireStaffPasswordChange::class);
        Gate::before(
'@
$provider = Replace-ExactlyOnce $provider '        Gate::before(' $insertProvider.TrimEnd("`r","`n") 'STSAMS authorization provider'
Add-Change 'backend/app/Providers/StsamsAuthorizationServiceProvider.php' $provider $null

$webPath = Join-Path $root 'backend/routes/web.php'
$web = Read-Lf $webPath
if (-not $web.Contains("require __DIR__.'/stsams_staff_profile.php';")) {
    $web = $web.TrimEnd("`r","`n") + "`nrequire __DIR__.'/stsams_staff_profile.php';`n"
}
Add-Change 'backend/routes/web.php' $web $null

$portalPath = Join-Path $root 'backend/resources/views/stsams/layout.blade.php'
$portal = Read-Lf $portalPath
$afterAuth = @'
            @auth
                <span class="stsams-user-badge" title="{{ auth()->user()->email }}">Signed in: <strong>{{ auth()->user()->name }}</strong> <small>({{ auth()->user()->getRoleNames()->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->join(', ') }})</small></span>
                @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'admission_officer', 'staff', 'website_editor']))
                  <a href="{{ route('stsams.account.show') }}">My profile</a>
                @endif
'@
$portal = Replace-ExactlyOnce $portal '            @auth' $afterAuth.TrimEnd("`r","`n") 'admissions user identity navigation'
Add-Change 'backend/resources/views/stsams/layout.blade.php' $portal $null

$institutionPath = Join-Path $root 'backend/resources/views/institutional/layout.blade.php'
$institution = Read-Lf $institutionPath
$identity = @'
  @auth
    @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'admission_officer', 'staff', 'website_editor']))
      <div class="shell" style="display:flex;justify-content:flex-end;align-items:center;gap:16px;padding:8px 0;font-size:.84rem;color:#28485d;flex-wrap:wrap">
        <span>Signed in: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->getRoleNames()->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->join(', ') }})</span>
        <a href="{{ route('stsams.account.show') }}">My profile & security</a>
      </div>
    @endif
  @endauth
</header>
'@
$institution = Replace-ExactlyOnce $institution '</header>' $identity.TrimEnd("`r","`n") 'institutional editor identity navigation'
Add-Change 'backend/resources/views/institutional/layout.blade.php' $institution $null

$cssPath = Join-Path $root 'backend/public/stsams/portal.css'
$css = Read-Lf $cssPath
if (-not $css.Contains('STSAMS-STAFF-PROFILE-BRAND:v1')) {
    $css += "`n/* STSAMS-STAFF-PROFILE-BRAND:v1 */`n.stsams-user-badge{display:inline-flex;align-items:center;gap:5px;font-size:.74rem;color:#28485d;max-width:250px;flex-wrap:wrap}.stsams-user-badge strong{font-weight:700}.stsams-user-badge small{color:#647b8d}.site-header nav{flex-wrap:wrap}@media(max-width:700px){.stsams-user-badge{width:100%;max-width:none}}`n"
}
Add-Change 'backend/public/stsams/portal.css' $css $null

foreach ($source in Get-ChildItem -LiteralPath $patch -File -Recurse) {
    $relative = [System.IO.Path]::GetRelativePath($patch, $source.FullName)
    Add-Change $relative $null $source.FullName
}
foreach ($source in Get-ChildItem -LiteralPath (Join-Path $pkg 'Documentation') -File) {
    Add-Change ("documentation/" + $source.Name) $null $source.FullName
}
foreach ($entry in @('Setup/Install-STSAMS-Staff-Profile.ps1','Verification/Verify-STSAMS-Staff-Profile.ps1')) {
    Add-Change $entry $null (Join-Path $pkg $entry)
}
# Reject overwriting a package-created file with unexpected local edits.
foreach ($entry in $changes) {
    if (-not [string]::IsNullOrWhiteSpace($entry.Source)) {
        if (-not (Test-Path -LiteralPath $entry.Source -PathType Leaf)) { throw "Missing package source: $($entry.Source)" }
        if (Test-Path -LiteralPath $entry.Target -PathType Leaf) {
            $a = (Get-FileHash -LiteralPath $entry.Source -Algorithm SHA256).Hash
            $b = (Get-FileHash -LiteralPath $entry.Target -Algorithm SHA256).Hash
            if ($a -ne $b) { throw "File already exists and differs from patch: $($entry.Relative). No files changed." }
        }
    }
}
Write-Host "Repository: $root"
foreach ($entry in $changes) {
    $action = if (Test-Path -LiteralPath $entry.Target) {'UPDATE'} else {'CREATE'}
    Write-Host "$action $($entry.Relative)"
}
if ($Preview) { Write-Host 'Preview complete. No repository/database files changed.'; return }

$backup = Join-Path (Split-Path -Parent $root) ('STSAMS_Backups/staff_self_service_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($entry in $changes) {
    if (Test-Path -LiteralPath $entry.Target -PathType Leaf) {
        $dest = Join-Path $backup $entry.Relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $dest) -Force | Out-Null
        Copy-Item -LiteralPath $entry.Target -Destination $dest -Force
    }
}
$utf8 = [System.Text.UTF8Encoding]::new($false)
foreach ($entry in $changes) {
    New-Item -ItemType Directory -Path (Split-Path -Parent $entry.Target) -Force | Out-Null
    if (-not [string]::IsNullOrWhiteSpace($entry.Source)) { Copy-Item -LiteralPath $entry.Source -Destination $entry.Target -Force }
    else { [System.IO.File]::WriteAllText($entry.Target, $entry.Content, $utf8) }
}
Write-Host "Installation complete. External backups: $backup"
Write-Host 'No database migration, roles, payment, environment, or Git operations were performed.'
