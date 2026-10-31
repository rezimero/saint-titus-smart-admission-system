#requires -Version 7.0
[CmdletBinding()]
param(
  [Parameter(Mandatory=$true)][string]$Repository,
  [switch]$Preview
)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$repo = (Resolve-Path -LiteralPath $Repository).Path
$source = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\Patch\backend')).Path
$backend = Join-Path $repo 'backend'
$webRoute = Join-Path $backend 'routes\web.php'
if (!(Test-Path (Join-Path $backend 'artisan'))) { throw 'Invalid STSAMS repository: backend/artisan was not found.' }
if (!(Test-Path $webRoute)) { throw 'Invalid STSAMS repository: backend/routes/web.php missing.' }
if (!(Test-Path (Join-Path $backend 'routes\stsams_portal.php'))) { throw 'Existing STSAMS admission portal route file was not found. Install admission core before this batch.' }
if ($repo -eq $source) { throw 'Refusing to use package source as destination.' }
$files = @(Get-ChildItem -LiteralPath $source -File -Recurse)
$rootRegex = "(?ms)^\s*Route::get\('\/'\s*,\s*function\s*\(\s*\)\s*\{\s*return\s+view\('welcome'\);\s*\}\s*\);\s*"
$viewRootRegex = "(?m)^\s*Route::view\('\/'\s*,\s*'welcome'\s*\);\s*"
$raw = Get-Content -LiteralPath $webRoute -Raw
$includeText = "require __DIR__.'/stsams_institution.php';"
if ($raw.Contains($includeText)) {
  $newWeb = $raw
  Write-Host 'Institutional site include already exists; no additional include needed.'
} else {
  $cleaned = [regex]::Replace($raw, $rootRegex, '')
  $cleaned = [regex]::Replace($cleaned, $viewRootRegex, '')
  if ($cleaned -match '(?m)Route::(get|view|match|any)\(\s*[\x27\x22]/[\x27\x22]') {
    throw "A custom root '/' route exists in routes/web.php. Installer stopped without changes; manual integration review is needed."
  }
  $newWeb = $cleaned.TrimEnd() + "`r`n`r`n// Saint Titus institutional website; admissions routes remain unchanged.`r`n" + $includeText + "`r`n"
}
Write-Host "STSAMS institutional website installer"
Write-Host "Repository: $repo"
Write-Host "Package files: $($files.Count)"
foreach ($file in $files) {
  $rel = [System.IO.Path]::GetRelativePath($source, $file.FullName)
  $dest = Join-Path $backend $rel
  $verb = if (Test-Path -LiteralPath $dest) { 'REPLACE' } else { 'CREATE ' }
  Write-Host "$verb backend\$rel"
}
if ($raw -ne $newWeb) { Write-Host 'UPDATE backend\routes\web.php (remove default welcome route and include institutional routes)' }
if ($Preview) { Write-Host 'Preview complete. No files or databases modified.'; return }
$backup = Join-Path (Split-Path $repo -Parent) ("STSAMS_Backups\institutional_website_v1_" + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($file in $files) {
  $rel = [System.IO.Path]::GetRelativePath($source, $file.FullName)
  $dest = Join-Path $backend $rel
  if (Test-Path -LiteralPath $dest) {
    $back = Join-Path $backup (Join-Path 'backend' $rel)
    New-Item -ItemType Directory -Path (Split-Path $back -Parent) -Force | Out-Null
    Copy-Item -LiteralPath $dest -Destination $back -Force
  }
}
if ($raw -ne $newWeb) {
  $routeBack = Join-Path $backup 'backend\routes\web.php'
  New-Item -ItemType Directory -Path (Split-Path $routeBack -Parent) -Force | Out-Null
  Copy-Item -LiteralPath $webRoute -Destination $routeBack -Force
}
foreach ($file in $files) {
  $rel = [System.IO.Path]::GetRelativePath($source, $file.FullName)
  $dest = Join-Path $backend $rel
  New-Item -ItemType Directory -Path (Split-Path $dest -Parent) -Force | Out-Null
  Copy-Item -LiteralPath $file.FullName -Destination $dest -Force
}
if ($raw -ne $newWeb) {
  [System.IO.File]::WriteAllText($webRoute, $newWeb, [System.Text.UTF8Encoding]::new($false))
}
$docsDir = Join-Path $repo 'documentation'
New-Item -ItemType Directory -Path $docsDir -Force | Out-Null
foreach ($doc in Get-ChildItem -LiteralPath (Join-Path $PSScriptRoot '..\Documentation') -File) {
  $dest = Join-Path $docsDir $doc.Name
  if (Test-Path $dest) { Copy-Item $dest (Join-Path $backup $doc.Name) -Force }
  Copy-Item -LiteralPath $doc.FullName -Destination $dest -Force
}
foreach ($group in @('Setup', 'Verification')) {
  $outDir = Join-Path $repo $group
  New-Item -ItemType Directory -Path $outDir -Force | Out-Null
  $srcDir = Join-Path $PSScriptRoot ("..\$group")
  foreach ($script in Get-ChildItem -LiteralPath $srcDir -Filter '*.ps1' -File) {
    $dest = Join-Path $outDir $script.Name
    if (Test-Path $dest) { Copy-Item $dest (Join-Path $backup $script.Name) -Force }
    Copy-Item -LiteralPath $script.FullName -Destination $dest -Force
  }
}
Write-Host "Installation complete. Backup directory: $backup"
Write-Host 'No .env, admission portal files, existing tables, or Git history were modified.'
Write-Host 'Run Verification\Verify-STSAMS-Institutional-Website.ps1, then php artisan migrate from backend after reviewing the status.'
