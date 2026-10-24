$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000'
Write-Host 'Checking Laravel health endpoint...' -ForegroundColor Cyan
$health = Invoke-WebRequest -Uri "$base/up" -Method Get
if ($health.StatusCode -ne 200) { throw "Health check failed: HTTP $($health.StatusCode)" }
Write-Host 'Health: OK' -ForegroundColor Green
Write-Host 'Checking public race/pricing endpoint...'
$races = Invoke-RestMethod -Uri "$base/api/races" -Method Get
if (-not ($races.data -is [System.Array]) -or $races.data.Count -lt 1) {
    throw 'The races endpoint returned no race data. Run php artisan migrate --seed from the backend folder.'
}
Write-Host "Races returned: $($races.data.Count)" -ForegroundColor Green
foreach ($race in $races.data) {
    $kits = ($race.prices | ForEach-Object { "$($_.kit_type): EGP $($_.price)" }) -join ', '
    Write-Host " - $($race.distance_label) $($race.name): $kits"
}
Write-Host 'Local API smoke check passed.' -ForegroundColor Green
