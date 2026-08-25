
Write-Host "STSAMS Environment Verification"

Write-Host "`nPHP:"
php -v

Write-Host "`nComposer:"
composer -V

Write-Host "`nNode:"
node -v

Write-Host "`nGit:"
git --version

Write-Host "`nFolder Check:"
$folders = @("backend","frontend","database","documentation","infrastructure","tests")

foreach ($folder in $folders) {
    if (Test-Path $folder) {
        Write-Host "[OK] $folder exists"
    }
    else {
        Write-Host "[MISSING] $folder"
    }
}

Write-Host "`nVerification complete."
