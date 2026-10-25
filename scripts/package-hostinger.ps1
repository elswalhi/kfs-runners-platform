param(
    [string]$ApiBaseUrl = "https://api-runners.elatiky.com"
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Write-Host "1. Building frontend for $ApiBaseUrl..." -ForegroundColor Cyan
& powershell.exe -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "build-hostinger-frontend.ps1") -ApiBaseUrl $ApiBaseUrl

$frontendZip = Join-Path $root "kfs-runners-hostinger-frontend.zip"
if (Test-Path $frontendZip) { Remove-Item $frontendZip -Force }

Write-Host "2. Creating $frontendZip..." -ForegroundColor Cyan
$frontendItems = (Get-ChildItem -Path (Join-Path $root "dist") -Force).FullName
Compress-Archive -Path $frontendItems -DestinationPath $frontendZip -Force

Write-Host "3. Preparing API package structure..." -ForegroundColor Cyan
$apiTemp = Join-Path $env:TEMP ("kfs-api-pack-" + [guid]::NewGuid().ToString('N'))
$kfsApiDir = Join-Path $apiTemp "kfs-api"
$publicHtmlDir = Join-Path $apiTemp "public_html"

New-Item -ItemType Directory -Path $kfsApiDir -Force | Out-Null
New-Item -ItemType Directory -Path $publicHtmlDir -Force | Out-Null

# Copy backend files to kfs-api excluding vendor, .env, and local sqlite files
$backendRoot = Join-Path $root "backend"
Get-ChildItem -Path $backendRoot -Force | Where-Object {
    $_.Name -notin @('.env', 'vendor', 'node_modules', '.git') -and $_.Name -notlike "*.sqlite*"
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $kfsApiDir -Recurse -Force
}

# Ensure storage directories exist in kfs-api
$storageDirs = @(
    "storage/app/private",
    "storage/framework/cache/data",
    "storage/framework/sessions",
    "storage/framework/views",
    "storage/logs",
    "bootstrap/cache"
)
foreach ($dir in $storageDirs) {
    $targetDir = Join-Path $kfsApiDir $dir
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }
}

# Clean any cache files inside bootstrap/cache
Get-ChildItem -Path (Join-Path $kfsApiDir "bootstrap\cache") -Filter "*.php" -File -ErrorAction SilentlyContinue | Remove-Item -Force

# Populate public_html for the API subdomain
Copy-Item -Path (Join-Path $root "deploy\hostinger\api-index.php") -Destination (Join-Path $publicHtmlDir "index.php") -Force
Copy-Item -Path (Join-Path $backendRoot "public\.htaccess") -Destination (Join-Path $publicHtmlDir ".htaccess") -Force
if (Test-Path (Join-Path $backendRoot "public\robots.txt")) {
    Copy-Item -Path (Join-Path $backendRoot "public\robots.txt") -Destination (Join-Path $publicHtmlDir "robots.txt") -Force
}
if (Test-Path (Join-Path $backendRoot "public\favicon.ico")) {
    Copy-Item -Path (Join-Path $backendRoot "public\favicon.ico") -Destination (Join-Path $publicHtmlDir "favicon.ico") -Force
}

$apiZip = Join-Path $root "kfs-runners-hostinger-api.zip"
if (Test-Path $apiZip) { Remove-Item $apiZip -Force }

Write-Host "4. Creating $apiZip..." -ForegroundColor Cyan
$apiItems = (Get-ChildItem -Path $apiTemp -Force).FullName
Compress-Archive -Path $apiItems -DestinationPath $apiZip -Force

Remove-Item $apiTemp -Recurse -Force

Write-Host "Deployment packages ready!" -ForegroundColor Green
Write-Host " - Frontend: $frontendZip"
Write-Host " - Backend:  $apiZip"
