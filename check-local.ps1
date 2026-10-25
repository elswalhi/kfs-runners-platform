$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000'
foreach ($path in @('/', '/register', '/admin/login', '/up')) {
    Write-Host "Checking $path ..." -ForegroundColor Cyan
    $response = Invoke-WebRequest -Uri "$base$path" -Method Get
    if ($response.StatusCode -ne 200) { throw "$path returned HTTP $($response.StatusCode)." }
}
$races = Invoke-RestMethod -Uri "$base/api/races" -Method Get
if (-not ($races.data -is [System.Array]) -or $races.data.Count -lt 1) { throw 'No active race records were returned. Run php artisan migrate --seed from backend/.' }
Write-Host 'Website, registration, admin login, health endpoint, and race data are reachable.' -ForegroundColor Green
