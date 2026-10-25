$ErrorActionPreference = 'Stop'
$Backend = Join-Path $PSScriptRoot 'backend'

function Require-Command([string]$Name, [string]$InstallHint) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) { throw "$Name was not found. $InstallHint" }
}
function Invoke-Checked([string]$Label, [scriptblock]$Command) {
    Write-Host "`n== $Label ==" -ForegroundColor Cyan
    & $Command
    if ($LASTEXITCODE -ne 0) { throw "$Label failed with exit code $LASTEXITCODE." }
}
function Set-EnvValue([string]$Path, [string]$Key, [string]$Value) {
    $lines = [System.Collections.Generic.List[string]]::new()
    foreach ($line in [System.IO.File]::ReadAllLines($Path)) { $lines.Add($line) }
    $pattern = '^\s*' + [regex]::Escape($Key) + '='
    $replacement = "$Key=`"$Value`""
    $found = $false
    for ($index = 0; $index -lt $lines.Count; $index++) {
        if ($lines[$index] -match $pattern) { $lines[$index] = $replacement; $found = $true; break }
    }
    if (-not $found) { $lines.Add($replacement) }
    [System.IO.File]::WriteAllText($Path, ($lines -join [Environment]::NewLine) + [Environment]::NewLine, [System.Text.UTF8Encoding]::new($false))
}

Write-Host 'KFS Runners — Laravel + Blade local setup' -ForegroundColor Green
Require-Command 'php' 'Install PHP 8.3+ and add php.exe to PATH.'
Require-Command 'composer' 'Install Composer and reopen PowerShell.'
$versionText = (& php -r 'echo PHP_VERSION;').Trim()
if ([version]$versionText -lt [version]'8.3.0') { throw "PHP 8.3+ is required. Detected PHP $versionText." }
$phpModules = (& php -m) -join "`n"
if ($phpModules -notmatch '(?im)^pdo_sqlite$') { throw 'PHP extension pdo_sqlite is required for local SQLite.' }

Push-Location $Backend
try {
    if (-not (Test-Path 'vendor/laravel/framework')) { Invoke-Checked 'Install Laravel dependencies' { composer install } }
    if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
    if (-not (Test-Path 'database/database.sqlite')) { New-Item -ItemType File -Path 'database/database.sqlite' -Force | Out-Null }

    Set-EnvValue '.env' 'APP_NAME' 'KFS Runners'
    Set-EnvValue '.env' 'APP_ENV' 'local'
    Set-EnvValue '.env' 'APP_DEBUG' 'true'
    Set-EnvValue '.env' 'APP_URL' 'http://127.0.0.1:8000'
    Set-EnvValue '.env' 'CORS_ALLOWED_ORIGINS' ''
    Set-EnvValue '.env' 'DB_CONNECTION' 'sqlite'
    Set-EnvValue '.env' 'DB_DATABASE' 'database/database.sqlite'
    Set-EnvValue '.env' 'EASYKASH_REDIRECT_URL' 'http://127.0.0.1:8000/register'

    $adminName = Read-Host 'Local admin display name [KFS Administrator]'
    if ([string]::IsNullOrWhiteSpace($adminName)) { $adminName = 'KFS Administrator' }
    $adminEmail = Read-Host 'Local admin email [admin@kfs.local]'
    if ([string]::IsNullOrWhiteSpace($adminEmail)) { $adminEmail = 'admin@kfs.local' }
    if ($adminEmail -notmatch '^[^\s@]+@[^\s@]+\.[^\s@]+$') { throw 'Please enter a valid admin email address.' }
    do {
        $securePassword = Read-Host 'Local admin password (at least 12 characters)' -AsSecureString
        $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
        try { $adminPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
        if ($adminPassword.Contains('"') -or $adminPassword.Contains("`r") -or $adminPassword.Contains("`n")) { Write-Host 'Do not use a double quote or newline.' -ForegroundColor Yellow; $adminPassword = '' }
        if ($adminPassword.Length -lt 12) { Write-Host 'Password must be at least 12 characters.' -ForegroundColor Yellow }
    } while ($adminPassword.Length -lt 12)
    Set-EnvValue '.env' 'KFS_ADMIN_NAME' $adminName
    Set-EnvValue '.env' 'KFS_ADMIN_EMAIL' $adminEmail
    Set-EnvValue '.env' 'KFS_ADMIN_PASSWORD' $adminPassword

    if ((Get-Content '.env' -Raw) -notmatch '(?m)^APP_KEY=base64:.+') { Invoke-Checked 'Generate application key' { php artisan key:generate --force } }
    Invoke-Checked 'Create database tables, race pricing, and local admin' { php artisan migrate --seed --force }
    Invoke-Checked 'Confirm Laravel routes are available' { php artisan route:list --except-vendor }
} finally { Pop-Location }

Write-Host "`nLocal setup complete." -ForegroundColor Green
Write-Host 'Start the app with .\start-local.ps1'
Write-Host 'Website:     http://127.0.0.1:8000'
Write-Host 'Registration: http://127.0.0.1:8000/register'
Write-Host 'Admin login:  http://127.0.0.1:8000/admin/login'
Write-Host "Admin email: $adminEmail"
Write-Host 'Admin password is the one you entered; it is not printed for safety.'
