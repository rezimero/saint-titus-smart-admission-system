#Requires -Version 7.0
[CmdletBinding()]
param([Parameter(Mandatory = $true)][string] $Repository)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$root = (Resolve-Path -LiteralPath $Repository).Path
$backend = Join-Path $root 'backend'
$site = Join-Path $backend 'resources/views/institutional/layout.blade.php'
$portal = Join-Path $backend 'resources/views/stsams/layout.blade.php'
$shared = Join-Path $backend 'resources/views/institutional/partials/brand.blade.php'
$css = Join-Path $backend 'public/stsams/portal.css'
$logo = Join-Path $backend 'public/institutional/saint-titus-official-logo.jpg'
$icon = Join-Path $backend 'public/institutional/icons/favicon.ico'

foreach ($file in @($site, $portal, $shared, $css, $logo, $icon)) {
    if (-not (Test-Path -LiteralPath $file -PathType Leaf)) { throw "Required branding asset missing: $file" }
}
$siteText = Get-Content -LiteralPath $site -Raw
$portalText = Get-Content -LiteralPath $portal -Raw
$sharedText = Get-Content -LiteralPath $shared -Raw
$cssText = Get-Content -LiteralPath $css -Raw

foreach ($item in @(@{Label = 'institutional website'; Text = $siteText}, @{Label = 'admissions portal'; Text = $portalText})) {
    if (-not $item.Text.Contains("@include('institutional.partials.brand')")) {
        throw "Shared brand partial is not included in $($item.Label)."
    }
}
if (-not $sharedText.Contains("route('institution.home')")) { throw 'The logo must link to the institutional homepage route.' }
if (-not $sharedText.Contains('institutional/saint-titus-official-logo.jpg')) { throw 'The brand partial does not use the official institutional logo.' }
if (-not $portalText.Contains('institutional/icons/favicon.ico')) { throw 'Admissions favicon does not match the institutional website.' }
if (-not $cssText.Contains('STSAMS-SHARED-INSTITUTIONAL-BRAND:v1')) { throw 'Shared brand styling missing.' }
if ($portalText.Contains('class="brand-mark"')) { throw 'Old ST placeholder brand still present.' }
Write-Host 'Brand markup, institutional logo, homepage link and favicon: PASS'

Push-Location $backend
try {
    & php artisan route:list --name=institution.home
    if ($LASTEXITCODE -ne 0) { throw 'Institutional homepage route is unavailable.' }
    & php artisan route:list --name=stsams.home
    if ($LASTEXITCODE -ne 0) { throw 'Admissions homepage route is unavailable.' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Laravel failed to compile Blade templates.' }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'Laravel could not clear compiled views.' }
} finally {
    Pop-Location
}
Write-Host 'STSAMS shared brand verification passed. Open / and /admissions in the browser.'
