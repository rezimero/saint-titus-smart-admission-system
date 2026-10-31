#Requires -Version 7.0
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $Repository,
    [switch] $Preview
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Read-Normalized([string] $Path) {
    return [System.IO.File]::ReadAllText($Path).Replace("`r`n", "`n")
}

function Replace-Unique([string] $Text, [string] $Before, [string] $After, [string] $Description) {
    $index = $Text.IndexOf($Before, [StringComparison]::Ordinal)
    if ($index -lt 0) { throw "Unrecognized $Description. No files changed; please review the local file before continuing." }
    $next = $Text.IndexOf($Before, $index + $Before.Length, [StringComparison]::Ordinal)
    if ($next -ge 0) { throw "Multiple occurrences in $Description. No files changed." }
    return $Text.Substring(0, $index) + $After + $Text.Substring($index + $Before.Length)
}

$root = (Resolve-Path -LiteralPath $Repository).Path
$backend = Join-Path $root 'backend'
$siteView = Join-Path $backend 'resources/views/institutional/layout.blade.php'
$portalView = Join-Path $backend 'resources/views/stsams/layout.blade.php'
$portalCss = Join-Path $backend 'public/stsams/portal.css'
$brandDestination = Join-Path $backend 'resources/views/institutional/partials/brand.blade.php'
$logo = Join-Path $backend 'public/institutional/saint-titus-official-logo.jpg'
$icon = Join-Path $backend 'public/institutional/icons/favicon.ico'
$scriptDir = Split-Path -Parent $PSCommandPath
$packageRoot = Split-Path -Parent $scriptDir
$brandSource = Join-Path $packageRoot 'Patch/backend/resources/views/institutional/partials/brand.blade.php'

foreach ($file in @($siteView, $portalView, $portalCss, $logo, $icon, $brandSource)) {
    if (-not (Test-Path -LiteralPath $file -PathType Leaf)) { throw "Required file missing: $file" }
}
if (-not (Test-Path -LiteralPath (Join-Path $root '.git'))) {
    throw "Repository .git directory not found at $root"
}

$site = Read-Normalized $siteView
$portal = Read-Normalized $portalView
$css = Read-Normalized $portalCss

$oldSiteBrand = @'
    <a class="brand" href="{{ route('institution.home') }}" aria-label="Saint Titus home">
      <img src="{{ asset('institutional/saint-titus-official-logo.jpg') }}" alt="Saint Titus College official logo" width="58" height="66">
      <span class="brand-name"><strong>SAINT TITUS</strong><small>COLLEGE OF HEALTH SCIENCES<br>AND TECHNOLOGY</small></span>
    </a>
'@
$oldPortalBrand = @'
        <a class="brand" href="{{ route('stsams.home') }}">
            <span class="brand-mark">ST</span>
            <span><strong>SAINT TITUS</strong><small>Smart Admissions Portal</small></span>
        </a>
'@
$siteInclude = "    @include('institutional.partials.brand')"
$portalInclude = "        @include('institutional.partials.brand')"

if ($site.Contains($siteInclude)) {
    Write-Host 'Institutional layout already uses shared brand.'
} else {
    $site = Replace-Unique $site $oldSiteBrand $siteInclude 'institutional website logo'
}
if ($portal.Contains($portalInclude)) {
    Write-Host 'Admissions layout already uses shared brand.'
} else {
    $portal = Replace-Unique $portal $oldPortalBrand $portalInclude 'admissions portal logo'
}

$faviconOld = @'
    <link rel="stylesheet" href="{{ asset('stsams/portal.css') }}">
'@
$faviconNew = @'
    <meta name="theme-color" content="#083856">
    <link rel="icon" href="{{ asset('institutional/icons/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('institutional/icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('institutional/icons/favicon-180.png') }}">
    <link rel="stylesheet" href="{{ asset('stsams/portal.css') }}">
'@
if (-not $portal.Contains("institutional/icons/favicon.ico")) {
    $portal = Replace-Unique $portal $faviconOld $faviconNew 'admissions favicon declaration'
}

$oldHead = @'
<body>
<header class="site-header">
'@
$newHead = @'
<body>
{{-- Shared institutional header identity; portal navigation remains admissions-specific. --}}
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="topline"><div class="shell topline-inner">
    <span><span class="tiny-dot" aria-hidden="true"></span> Benin City, Edo State, Nigeria</span>
    <div><a href="tel:+2348034418715">08034418715</a><span class="separator" aria-hidden="true">/</span><a href="mailto:{{ config('stsams_institution.email') }}">{{ config('stsams_institution.email') }}</a></div>
</div></div>
<header class="site-header">
'@
if (-not $portal.Contains('class="topline"')) {
    $portal = Replace-Unique $portal $oldHead $newHead 'admissions institutional contact bar'
}

$oldMain = '<main class="shell content">'
$newMain = '<main id="main-content" class="shell content">'
if (-not $portal.Contains($newMain)) {
    $portal = Replace-Unique $portal $oldMain $newMain 'admissions main landmark'
}

$oldPortalTitle = @'
    <title>@yield('title', 'Smart Admissions') · Saint Titus</title>
'@
$newPortalTitle = @'
    <title>@yield('title', 'Smart Admissions') | {{ config('stsams_institution.name') }}</title>
'@
if (-not $portal.Contains($newPortalTitle)) {
    $portal = Replace-Unique $portal $oldPortalTitle $newPortalTitle 'admissions page title'
}

$brandCss = @'

/* STSAMS-SHARED-INSTITUTIONAL-BRAND:v1 — matches the institutional header. */
.skip-link{position:absolute;top:-100px;left:20px;background:#fff;z-index:1000;padding:12px;color:#07283f}
.skip-link:focus{top:12px}
.topline{background:#07283f;color:#dbeaf1;font-size:.73rem}
.topline .shell,.site-header .shell{width:min(1240px,calc(100% - 48px))}
.topline-inner{min-height:37px;display:flex;align-items:center;justify-content:space-between;gap:20px}
.topline a{color:inherit}.topline a:hover{color:#e7b54c}
.topline .separator{padding:0 12px;opacity:.4}
.tiny-dot{display:inline-block;height:7px;width:7px;border-radius:50%;background:#e7b54c;margin-right:6px}
.site-header{border-bottom:1px solid #e5ecef}
.site-header .topbar{min-height:99px;gap:22px}
.site-header .brand{display:flex;align-items:center;gap:12px;flex:none;color:#112c40}
.site-header .brand:hover{text-decoration:none;color:#112c40}
.site-header .brand img{width:57px;height:65px;object-fit:contain;max-width:100%}
.site-header .brand-name{display:flex;flex-direction:column;gap:3px;line-height:1.12}
.site-header .brand-name strong{font-size:1.08rem;letter-spacing:.17em;color:#07283f}
.site-header .brand-name small{font-size:.63rem;letter-spacing:.085em;font-weight:700;color:#617587}
.site-header .brand:focus-visible{outline:3px solid #e7b54c;outline-offset:5px;border-radius:4px}
@media(max-width:820px){.site-header .brand img{width:46px;height:54px}}
@media(max-width:520px){.site-header .brand-name strong{font-size:.8rem}.site-header .brand-name small{font-size:.48rem}}
/* END STSAMS-SHARED-INSTITUTIONAL-BRAND:v1 */
'@
if (-not $css.Contains('STSAMS-SHARED-INSTITUTIONAL-BRAND:v1')) {
    $css += $brandCss
}

$changes = @(
    @{ Path = $siteView; Content = $site },
    @{ Path = $portalView; Content = $portal },
    @{ Path = $portalCss; Content = $css },
    @{ Path = $brandDestination; Content = (Read-Normalized $brandSource) }
)

$docFiles = @(
    @{ Source = (Join-Path $packageRoot 'Documentation/ADMISSIONS_INSTITUTIONAL_BRAND_HANDOVER.md'); Destination = (Join-Path $root 'documentation/ADMISSIONS_INSTITUTIONAL_BRAND_HANDOVER.md') },
    @{ Source = (Join-Path $packageRoot 'Documentation/IMPLEMENTATION_LOG_ADMISSIONS_BRANDING.md'); Destination = (Join-Path $root 'documentation/IMPLEMENTATION_LOG_ADMISSIONS_BRANDING.md') },
    @{ Source = $PSCommandPath; Destination = (Join-Path $root 'Setup/Install-STSAMS-Shared-Brand.ps1') },
    @{ Source = (Join-Path $packageRoot 'Verification/Verify-STSAMS-Shared-Brand.ps1'); Destination = (Join-Path $root 'Verification/Verify-STSAMS-Shared-Brand.ps1') }
)

Write-Host "Repository: $root"
foreach ($change in $changes) {
    $relative = [System.IO.Path]::GetRelativePath($root, $change.Path)
    Write-Host "UPDATE $relative"
}
foreach ($file in $docFiles) {
    $relative = [System.IO.Path]::GetRelativePath($root, $file.Destination)
    Write-Host "INSTALL $relative"
}
if ($Preview) { Write-Host 'Preview complete: no files or databases modified.'; return }

$backupRoot = Join-Path (Split-Path -Parent $root) ('STSAMS_Backups/shared_brand_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Path $backupRoot -Force | Out-Null
$utf8 = [System.Text.UTF8Encoding]::new($false)
foreach ($change in $changes) {
    $target = $change.Path
    $relative = [System.IO.Path]::GetRelativePath($root, $target)
    if (Test-Path -LiteralPath $target -PathType Leaf) {
        $backup = Join-Path $backupRoot $relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $backup) -Force | Out-Null
        Copy-Item -LiteralPath $target -Destination $backup -Force
    }
    New-Item -ItemType Directory -Path (Split-Path -Parent $target) -Force | Out-Null
    [System.IO.File]::WriteAllText($target, [string] $change.Content, $utf8)
}
foreach ($file in $docFiles) {
    if (-not (Test-Path -LiteralPath $file.Source -PathType Leaf)) { throw "Missing package file: $($file.Source)" }
    if (Test-Path -LiteralPath $file.Destination -PathType Leaf) {
        $relative = [System.IO.Path]::GetRelativePath($root, $file.Destination)
        $backup = Join-Path $backupRoot $relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $backup) -Force | Out-Null
        Copy-Item -LiteralPath $file.Destination -Destination $backup -Force
    }
    New-Item -ItemType Directory -Path (Split-Path -Parent $file.Destination) -Force | Out-Null
    Copy-Item -LiteralPath $file.Source -Destination $file.Destination -Force
}
Write-Host "Shared brand installed. Backup: $backupRoot"
Write-Host 'No migrations, seeding, or .env modifications were performed.'
