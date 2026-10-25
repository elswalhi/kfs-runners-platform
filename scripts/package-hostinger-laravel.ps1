$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$Backend = Join-Path $Root 'backend'
$TargetZip = Join-Path $Root 'kfs-runners-hostinger-laravel.zip'
$TempBase = Join-Path $env:TEMP 'kfs-runners-hostinger-package'
$TempRoot = Join-Path $TempBase ([guid]::NewGuid().ToString('N'))
$AppDir = Join-Path $TempRoot 'kfs-app'
$WebDir = Join-Path $TempRoot 'public_html'

if (-not (Test-Path (Join-Path $Backend 'artisan'))) { throw "Laravel app was not found at $Backend." }
New-Item -ItemType Directory -Path $AppDir, $WebDir -Force | Out-Null

try {
    Get-ChildItem -LiteralPath $Backend -Force | Where-Object {
        $_.Name -notin @('.env', 'vendor', 'node_modules', '.git', 'storage') -and $_.Name -notlike '*.sqlite*'
    } | ForEach-Object { Copy-Item -LiteralPath $_.FullName -Destination $AppDir -Recurse -Force }
    Get-ChildItem -LiteralPath $AppDir -Filter '*.sqlite*' -Recurse -File -Force -ErrorAction SilentlyContinue | Remove-Item -Force

    $storagePaths = @(
        'storage/app/private',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache'
    )
    foreach ($relativePath in $storagePaths) { New-Item -ItemType Directory -Path (Join-Path $AppDir $relativePath) -Force | Out-Null }
    Get-ChildItem -LiteralPath (Join-Path $AppDir 'bootstrap\cache') -Filter '*.php' -File -ErrorAction SilentlyContinue | Remove-Item -Force

    $PublicSource = Join-Path $Backend 'public'
    Get-ChildItem -LiteralPath $PublicSource -Force | Where-Object { $_.Name -ne 'index.php' } | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $WebDir -Recurse -Force
    }
    Copy-Item -LiteralPath (Join-Path $Root 'deploy\hostinger\laravel-index.php') -Destination (Join-Path $WebDir 'index.php') -Force

    if (Test-Path -LiteralPath $TargetZip) { Remove-Item -LiteralPath $TargetZip -Force }
    Compress-Archive -Path (Get-ChildItem -LiteralPath $TempRoot -Force).FullName -DestinationPath $TargetZip -Force
    Write-Host "Laravel deployment package is ready: $TargetZip" -ForegroundColor Green
    Write-Host 'Upload and extract it so kfs-app/ and public_html/ sit beside each other.'
} finally {
    $resolvedBase = [System.IO.Path]::GetFullPath($TempBase) + [System.IO.Path]::DirectorySeparatorChar
    $resolvedTemp = [System.IO.Path]::GetFullPath($TempRoot)
    if (-not $resolvedTemp.StartsWith($resolvedBase, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw 'Refusing to remove a temporary path outside the package workspace.'
    }
    if (Test-Path -LiteralPath $resolvedTemp) { Remove-Item -LiteralPath $resolvedTemp -Recurse -Force }
}
