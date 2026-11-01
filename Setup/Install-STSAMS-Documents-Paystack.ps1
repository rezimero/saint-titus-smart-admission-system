param(
    [Parameter(Mandatory=$true)][string]$Repository,
    [switch]$Preview
)
$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path -LiteralPath $Repository).Path
$root = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$patch = Join-Path $root 'Patch'
if (!(Test-Path (Join-Path $repo 'backend\artisan'))) { throw 'Laravel backend/artisan not found.' }
if (!(Test-Path (Join-Path $repo 'backend\routes\stsams_portal.php'))) { throw 'Existing STSAMS admission portal not found.' }
if (!(Test-Path (Join-Path $repo 'backend\routes\api.php'))) { throw 'Sanctum API routes file not found.' }

Push-Location $repo
try {
    & git rev-parse --is-inside-work-tree | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Not a Git repository.' }
    $dirty = @(& git status --porcelain)
    if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect Git status.' }
    if ($dirty.Count -gt 0) {
        throw 'Repository contains uncommitted changes. Commit or back them up before applying this feature batch.'
    }
} finally { Pop-Location }

$items = @(Get-ChildItem -LiteralPath $patch -Recurse -File)
$special = @('Additive\backend\routes\api.php.snippet', 'Additive\backend\resources\views\stsams\layout.blade.php.insert')
foreach ($item in $items) {
    $rel = [IO.Path]::GetRelativePath($patch, $item.FullName)
    if ($rel.StartsWith('Additive' + [IO.Path]::DirectorySeparatorChar)) { continue }
    $target = Join-Path $repo $rel
    $operation = if (Test-Path -LiteralPath $target) { 'REPLACE' } else { 'CREATE' }
    Write-Host "$operation $rel"
}
Write-Host 'UPDATE backend\routes\api.php (append signed Paystack webhook route)'
Write-Host 'UPDATE backend\resources\views\stsams\layout.blade.php (document styling only)'
if ($Preview) { Write-Host 'Preview complete. No changes made.'; return }

$backup = Join-Path 'C:\Projects\STSAMS_Backups' ('documents_paystack_v1_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Force -Path $backup | Out-Null
$targets = @()
foreach ($item in $items) {
    $rel = [IO.Path]::GetRelativePath($patch, $item.FullName)
    if ($rel.StartsWith('Additive' + [IO.Path]::DirectorySeparatorChar)) { continue }
    $targets += $rel
}
$targets += 'backend\routes\api.php', 'backend\resources\views\stsams\layout.blade.php'
foreach ($rel in $targets) {
    $source = Join-Path $repo $rel
    if (Test-Path -LiteralPath $source) {
        $dest = Join-Path $backup $rel
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dest) | Out-Null
        Copy-Item -LiteralPath $source -Destination $dest -Force
    }
}
# All checks and backups are complete before writing.
foreach ($item in $items) {
    $rel = [IO.Path]::GetRelativePath($patch, $item.FullName)
    if ($rel.StartsWith('Additive' + [IO.Path]::DirectorySeparatorChar)) { continue }
    $dest = Join-Path $repo $rel
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dest) | Out-Null
    Copy-Item -LiteralPath $item.FullName -Destination $dest -Force
}
$api = Join-Path $repo 'backend\routes\api.php'
$apiText = [IO.File]::ReadAllText($api)
if (!$apiText.Contains('stsams.payments.webhook')) {
    $addition = [IO.File]::ReadAllText((Join-Path $patch 'Additive\backend\routes\api.php.snippet'))
    [IO.File]::AppendAllText($api, "`n" + $addition, [Text.UTF8Encoding]::new($false))
}
$layout = Join-Path $repo 'backend\resources\views\stsams\layout.blade.php'
$layoutText = [IO.File]::ReadAllText($layout)
if (!$layoutText.Contains('stsams/documents.css')) {
    if (!$layoutText.Contains('</head>')) { throw 'Cannot find </head> in admissions layout; restore from backup and review manually.' }
    $insert = [IO.File]::ReadAllText((Join-Path $patch 'Additive\backend\resources\views\stsams\layout.blade.php.insert'))
    $layoutText = $layoutText.Replace('</head>', '    ' + $insert + "`n" + '</head>')
    [IO.File]::WriteAllText($layout, $layoutText, [Text.UTF8Encoding]::new($false))
}
Write-Host "Installation complete. Backups: $backup"
Write-Host 'No database changes, Paystack charges, or Git commits were performed.'
Write-Host 'Next: verify syntax/routes, review and run php artisan migrate (after confirming main DB).'
