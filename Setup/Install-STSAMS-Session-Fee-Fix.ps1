param(
    [Parameter(Mandatory=$true)][string]$Repository,
    [switch]$Preview
)
$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path -LiteralPath $Repository).Path
$root = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
if (-not (Test-Path -LiteralPath (Join-Path $repo 'backend\artisan'))) { throw 'Expected STSAMS Laravel backend missing.' }
$files = @(
    'backend\app\Modules\Admissions\Controllers\CatalogueController.php',
    'backend\resources\views\stsams\admin\catalogue.blade.php',
    'backend\tests\Feature\Admissions\AdmissionSessionFeeTest.php',
    'documentation\ADR_006_Initial_Admission_Fee_Integrity.md',
    'documentation\IMPLEMENTATION_LOG_ADMISSION_FEE_SETUP_FIX.md',
    'Setup\Install-STSAMS-Session-Fee-Fix.ps1',
    'Verification\Verify-STSAMS-Session-Fee-Fix.ps1'
)
$normalize = {
    param([string]$path)
    return ([IO.File]::ReadAllText($path) -replace "`r`n", "`n")
}
# Check every existing source BEFORE writing anything. Never erase local edits.
foreach ($rel in $files) {
    $current = Join-Path $repo $rel
    $baseline = Join-Path $root ('Baseline\' + $rel)
    $replacement = Join-Path $root ('Patch\' + $rel)
    if ($rel.StartsWith('Setup\') -or $rel.StartsWith('Verification\')) {
        $replacement = Join-Path $root $rel
    }
    if (-not (Test-Path -LiteralPath $replacement)) { throw "Missing patch file: $rel" }
    if (Test-Path -LiteralPath $baseline) {
        if (-not (Test-Path -LiteralPath $current)) { throw "Expected file missing: $rel" }
        $existingText = & $normalize $current
        $baseText = & $normalize $baseline
        $newText = & $normalize $replacement
        if (-not [string]::Equals($existingText, $baseText, [StringComparison]::Ordinal) -and
            -not [string]::Equals($existingText, $newText, [StringComparison]::Ordinal)) {
            throw "Modified source differs from expected baseline: $rel. Do not overwrite manually; inspect your local changes first."
        }
        Write-Host "UPDATE $rel"
    } elseif (Test-Path -LiteralPath $current) {
        $existingText = & $normalize $current
        $newText = & $normalize $replacement
        if (-not [string]::Equals($existingText, $newText, [StringComparison]::Ordinal)) {
            throw "New file already exists with different contents: $rel"
        }
        Write-Host "EXISTS $rel"
    } else { Write-Host "CREATE $rel" }
}
if ($Preview) { Write-Host 'Preview finished. No files or MySQL data changed.'; return }

$backup = Join-Path 'C:\Projects\STSAMS_Backups' ('session_fee_fix_' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($rel in $files) {
    $current = Join-Path $repo $rel
    $replacement = Join-Path $root ('Patch\' + $rel)
    if ($rel.StartsWith('Setup\') -or $rel.StartsWith('Verification\')) { $replacement = Join-Path $root $rel }
    if (Test-Path -LiteralPath $current) {
        $back = Join-Path $backup $rel
        New-Item -ItemType Directory -Path (Split-Path -Parent $back) -Force | Out-Null
        Copy-Item -LiteralPath $current -Destination $back -Force
    }
    New-Item -ItemType Directory -Path (Split-Path -Parent $current) -Force | Out-Null
    Copy-Item -LiteralPath $replacement -Destination $current -Force
}
Write-Host "Admission fee fix installed. Backups at: $backup"
Write-Host 'No migrations, database edits, Paystack calls, or Git operations were executed.'
