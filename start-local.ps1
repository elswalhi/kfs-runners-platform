$ErrorActionPreference = 'Stop'
$Root = $PSScriptRoot
$Backend = Join-Path $Root 'backend'
if (-not (Test-Path (Join-Path $Backend 'artisan'))) {
    throw 'Laravel backend is not set up yet. Run .\setup-local.ps1 first.'
}
if (-not (Test-Path (Join-Path $Root 'node_modules'))) {
    throw 'React dependencies are missing. Run .\setup-local.ps1 first.'
}

$quotedBackend = "'" + $Backend.Replace("'", "''") + "'"
$quotedRoot = "'" + $Root.Replace("'", "''") + "'"
$backendCommand = "Set-Location -LiteralPath $quotedBackend; php artisan serve --host=127.0.0.1 --port=8000"
$frontendCommand = "Set-Location -LiteralPath $quotedRoot; npm.cmd run dev -- --host 127.0.0.1"
$backendEncoded = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($backendCommand))
$frontendEncoded = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($frontendCommand))

Start-Process -FilePath 'powershell.exe' -ArgumentList "-NoExit -ExecutionPolicy Bypass -EncodedCommand $backendEncoded" -WorkingDirectory $Backend
Start-Sleep -Seconds 2
Start-Process -FilePath 'powershell.exe' -ArgumentList "-NoExit -ExecutionPolicy Bypass -EncodedCommand $frontendEncoded" -WorkingDirectory $Root
Start-Sleep -Seconds 2
Write-Host 'Local development servers requested.' -ForegroundColor Green
Write-Host 'React:       http://localhost:5173'
Write-Host 'Register:    http://localhost:5173/register'
Write-Host 'Admin login: http://localhost:5173/admin/login'
Write-Host 'Laravel API: http://127.0.0.1:8000/api/races'
Write-Host 'Health:      http://127.0.0.1:8000/up'
