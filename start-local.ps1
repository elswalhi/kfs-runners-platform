$ErrorActionPreference = 'Stop'
$Backend = Join-Path $PSScriptRoot 'backend'
if (-not (Test-Path (Join-Path $Backend 'artisan'))) { throw 'Laravel app not found. Run .\setup-local.ps1 first.' }
if (-not (Test-Path (Join-Path $Backend '.env'))) { throw 'Local environment file is missing. Run .\setup-local.ps1 first.' }
Write-Host 'Starting KFS Runners Laravel + Blade at http://127.0.0.1:8000' -ForegroundColor Green
Push-Location $Backend
try { php artisan serve --host=127.0.0.1 --port=8000 } finally { Pop-Location }
