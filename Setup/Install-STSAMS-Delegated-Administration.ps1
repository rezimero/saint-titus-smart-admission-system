#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory)][string]$Repository, [switch]$Preview)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Normalize([string]$path) { [System.IO.File]::ReadAllText($path).Replace("`r`n", "`n") }
function Replace-One([string]$inputText, [string]$before, [string]$after, [string]$label) {
    if ($inputText.Contains($after)) { return $inputText }
    $one = $inputText.IndexOf($before, [StringComparison]::Ordinal)
    if ($one -lt 0) { throw "Unexpected local version of ${label}: expected integration anchor not found. No files changed." }
    if ($inputText.IndexOf($before, $one + $before.Length, [StringComparison]::Ordinal) -ge 0) {
        throw "Repeated integration anchor in $label. No files changed."
    }
    return $inputText.Substring(0,$one)+$after+$inputText.Substring($one+$before.Length)
}

$root = (Resolve-Path -LiteralPath $Repository).Path
$packageRoot = Split-Path -Parent $PSScriptRoot
$patchRoot = Join-Path $packageRoot 'Patch'
if (-not (Test-Path (Join-Path $root '.git'))) { throw 'Git repository missing.' }
if (-not (Test-Path (Join-Path $root 'backend/database/migrations/2026_10_31_200001_add_admission_document_and_payment_foundation.php'))) {
    throw 'Install the Documents/Paystack v1 feature first. No changes made.'
}

# Validate every patch and every target BEFORE writing to repository.
$entries = [System.Collections.Generic.List[object]]::new()
foreach ($f in Get-ChildItem -LiteralPath $patchRoot -Recurse -File) {
    $rel = [System.IO.Path]::GetRelativePath($patchRoot,$f.FullName)
    $entries.Add([pscustomobject]@{ Relative=$rel; Destination=(Join-Path $root $rel); Content=$null; Source=$f.FullName })
}

$providers = Join-Path $root 'backend/bootstrap/providers.php'
$web = Join-Path $root 'backend/routes/web.php'
$siteAdmin = Join-Path $root 'backend/app/Modules/Institutional/Controllers/WebsiteAdminController.php'
$auth = Join-Path $root 'backend/app/Modules/Admissions/Controllers/PortalAuthController.php'
$layout = Join-Path $root 'backend/resources/views/stsams/layout.blade.php'
foreach ($file in @($providers,$web,$siteAdmin,$auth,$layout)) {
    if (-not (Test-Path -LiteralPath $file -PathType Leaf)) { throw "Missing required existing file: $file" }
}
$providerText = Normalize $providers
$providerText = Replace-One $providerText '];' "    App\Providers\StsamsAuthorizationServiceProvider::class,`n];" 'provider registration'
$webText = Normalize $web
if (-not $webText.Contains("stsams_staff_access.php")) {
    $webText += "`nrequire __DIR__.'/stsams_staff_access.php';`n"
}
$siteText = Normalize $siteAdmin
$siteText = Replace-One $siteText "`$request->user()->hasAnyRole(['superadmin', 'admin'])" "`$request->user()->can('website.manage')" 'institutional website editor authorization'
$authText = Normalize $auth
$oldAuth = "if (`$user->can('applications.review') || `$user->can('applications.approve') || `$user->can('applications.reject')) {"
$newAuth = @'
if ($user->hasRole('superadmin')) {
            return redirect()->route('stsams.staff-management.index');
        }
        if ($user->canAny(['applications.view_all', 'applications.review', 'applications.offer_admission', 'applications.reject'])) {
'@
$authText = Replace-One $authText $oldAuth $newAuth.TrimEnd() 'portal dashboard administrator routing'
$oldAuthApplicant = "        if (`$user->hasRole('applicant')) {"
$newAuthApplicant = @'
        if ($user->can('settings.manage')) {
            return redirect()->route('stsams.admin.catalogue');
        }
        if ($user->can('website.manage')) {
            return redirect()->route('institution.admin.index');
        }
        if ($user->hasRole('applicant')) {
'@
$authText = Replace-One $authText $oldAuthApplicant $newAuthApplicant.TrimEnd() 'portal dashboard content editor routing'
$layoutText = Normalize $layout
$existingLink = "route('stsams.staff-management.index')"
if (-not $layoutText.Contains($existingLink)) {
    $anchor = "@if(auth()->user()->can('settings.manage'))"
    $insert = "@if(auth()->user()->hasRole('superadmin'))<a href=`"{{ route('stsams.staff-management.index') }}`">Staff access</a>@endif`n                $anchor"
    $layoutText = Replace-One $layoutText $anchor $insert 'portal staff navigation'
}
foreach ($item in @(
    @{Path=$providers;Content=$providerText},
    @{Path=$web;Content=$webText},
    @{Path=$siteAdmin;Content=$siteText},
    @{Path=$auth;Content=$authText},
    @{Path=$layout;Content=$layoutText}
)) {
    $entries.Add([pscustomobject]@{ Relative=[System.IO.Path]::GetRelativePath($root,$item.Path);Destination=$item.Path;Content=$item.Content;Source=$null })
}
# Save docs, PowerShell entrypoints under the project for ongoing handover.
foreach ($name in @('ADR_007_Central_Administration_Delegated_Offers.md','IMPLEMENTATION_LOG_STAFF_AUTHORIZATION.md','STAFF_AUTHORIZATION_HANDOVER.md')) {
    $src = Join-Path $packageRoot "Documentation/$name"
    $rel = "documentation/$name"
    $entries.Add([pscustomobject]@{Relative=$rel;Destination=(Join-Path $root $rel);Content=$null;Source=$src})
}
foreach ($name in @('Setup/Install-STSAMS-Delegated-Administration.ps1','Verification/Verify-STSAMS-Delegated-Administration.ps1')) {
    $src = Join-Path $packageRoot $name
    $entries.Add([pscustomobject]@{Relative=$name;Destination=(Join-Path $root $name);Content=$null;Source=$src})
}
foreach ($entry in $entries) {
    if ($null -ne $entry.Source -and -not (Test-Path -LiteralPath $entry.Source -PathType Leaf)) {
        throw "Package source missing: $($entry.Source)"
    }
}
Write-Host "Repository: $root"
Write-Host "Files prepared: $($entries.Count)"
foreach ($entry in $entries) {
    $verb = if (Test-Path -LiteralPath $entry.Destination) {'UPDATE'} else {'CREATE'}
    Write-Host "$verb $($entry.Relative)"
}
if ($Preview) { Write-Host 'Preview only. No repository/database changes.'; return }
$backupRoot = Join-Path (Split-Path -Parent $root) ('STSAMS_Backups/delegated_admin_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Path $backupRoot -Force | Out-Null
foreach ($entry in $entries) {
    if (Test-Path -LiteralPath $entry.Destination) {
        $backup = Join-Path $backupRoot $entry.Relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $backup) -Force | Out-Null
        Copy-Item -LiteralPath $entry.Destination -Destination $backup -Force
    }
}
$encoding = [System.Text.UTF8Encoding]::new($false)
foreach ($entry in $entries) {
    New-Item -ItemType Directory -Path (Split-Path -Parent $entry.Destination) -Force | Out-Null
    if ($null -ne $entry.Source) {
        Copy-Item -LiteralPath $entry.Source -Destination $entry.Destination -Force
    } else {
        [System.IO.File]::WriteAllText($entry.Destination,$entry.Content,$encoding)
    }
}
Write-Host "Installation complete. Backup: $backupRoot"
Write-Host 'NO migrations, seeders, user accounts, Paystack charges or Git commands were run.'
Write-Host 'Run Verification/Verify-STSAMS-Delegated-Administration.ps1 next.'
