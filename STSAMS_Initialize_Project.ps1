
Write-Host "===================================="
Write-Host "STSAMS Project Initialization"
Write-Host "===================================="

$ErrorActionPreference = "Stop"

Write-Host "Checking Git..."
git --version

Write-Host "Checking PHP..."
php -v

Write-Host "Checking Composer..."
composer -V

Write-Host "Creating project folders..."

$folders = @(
    "backend",
    "frontend",
    "database",
    "documentation",
    "infrastructure",
    "tests"
)

foreach ($folder in $folders) {
    if (!(Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder | Out-Null
        Write-Host "Created $folder"
    }
}

Write-Host ""
Write-Host "Initializing Git repository..."

if (!(Test-Path ".git")) {
    git init
}

Write-Host ""
Write-Host "STSAMS foundation created."
Write-Host "Next step: initialize Laravel inside backend folder."
