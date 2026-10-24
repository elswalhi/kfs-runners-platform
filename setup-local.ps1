$ErrorActionPreference = 'Stop'
$Root = $PSScriptRoot
$Backend = Join-Path $Root 'backend'
$Starter = Join-Path $Root 'backend-starter'

function Require-Command([string]$Name, [string]$InstallHint) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "$Name was not found. $InstallHint"
    }
}
function Invoke-Checked([string]$Label, [scriptblock]$Command) {
    Write-Host "`n== $Label ==" -ForegroundColor Cyan
    & $Command
    if ($LASTEXITCODE -ne 0) { throw "$Label failed with exit code $LASTEXITCODE." }
}
function Set-EnvValue([string]$Path, [string]$Key, [string]$Value) {
    $lines = [System.Collections.Generic.List[string]]::new()
    foreach ($existingLine in [System.IO.File]::ReadAllLines($Path)) { $lines.Add($existingLine) }
    $pattern = '^\s*' + [regex]::Escape($Key) + '='
    $line = "$Key=`"$Value`""
    $found = $false
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match $pattern) { $lines[$i] = $line; $found = $true; break }
    }
    if (-not $found) { $lines.Add($line) }
    [System.IO.File]::WriteAllText($Path, ($lines -join [Environment]::NewLine) + [Environment]::NewLine, [System.Text.UTF8Encoding]::new($false))
}
function Copy-TreeContents([string]$Source, [string]$Destination) {
    if (-not (Test-Path $Destination)) { New-Item -ItemType Directory -Path $Destination -Force | Out-Null }
    Get-ChildItem -LiteralPath $Source -Force | ForEach-Object {
        $target = Join-Path $Destination $_.Name
        if ($_.PSIsContainer) {
            Copy-TreeContents $_.FullName $target
        } else {
            Copy-Item -LiteralPath $_.FullName -Destination $target -Force
        }
    }
}

Write-Host 'KFS Runners — local-first setup' -ForegroundColor Green
Write-Host 'This creates a local Laravel API backed by SQLite; it does not deploy or touch WordPress.'

Require-Command 'php' 'Install PHP 8.3+ and add php.exe to PATH.'
Require-Command 'composer' 'Install Composer and reopen PowerShell.'
Require-Command 'node' 'Install Node.js 20+ and reopen PowerShell.'
Require-Command 'npm' 'Install Node.js 20+ and reopen PowerShell.'

$phpVersionText = (& php -r 'echo PHP_VERSION;').Trim()
$phpVersion = [version]$phpVersionText
if ($phpVersion -lt [version]'8.3.0') { throw "PHP 8.3+ is required. Detected PHP $phpVersionText." }
$phpModules = (& php -m) -join "`n"
if ($phpModules -notmatch '(?im)^pdo_sqlite$') { throw 'PHP extension pdo_sqlite is required for local SQLite. Enable it in your local PHP configuration and rerun setup.' }
$nodeVersionText = (& node -v).Trim().TrimStart('v')
$nodeVersion = [version]$nodeVersionText
if ($nodeVersion -lt [version]'20.0.0') { throw "Node.js 20+ is recommended. Detected Node $nodeVersionText." }

if (-not (Test-Path (Join-Path $Starter 'routes/api.php'))) { throw "Backend starter files not found at $Starter." }
if (-not (Test-Path (Join-Path $Backend 'artisan'))) {
    if (Test-Path $Backend) {
        throw "The backend folder exists but is not a complete Laravel project. Rename/delete $Backend and rerun setup."
    }
    Invoke-Checked 'Create Laravel application' { composer create-project laravel/laravel $Backend '^13.0' --no-interaction }
}

Push-Location $Backend
try {
    if (-not (Test-Path 'vendor/laravel/sanctum')) {
        Invoke-Checked 'Install API authentication / Sanctum' { php artisan install:api --no-interaction }
    }

    Write-Host "`n== Apply KFS application files ==" -ForegroundColor Cyan
    Copy-TreeContents (Join-Path $Starter 'app') (Join-Path $Backend 'app')
    Copy-TreeContents (Join-Path $Starter 'database/migrations') (Join-Path $Backend 'database/migrations')
    Copy-TreeContents (Join-Path $Starter 'database/seeders') (Join-Path $Backend 'database/seeders')
    Copy-Item -LiteralPath (Join-Path $Starter 'routes/api.php') -Destination (Join-Path $Backend 'routes/api.php') -Force
    Copy-Item -LiteralPath (Join-Path $Starter 'config/cors.php') -Destination (Join-Path $Backend 'config/cors.php') -Force
    Copy-Item -LiteralPath (Join-Path $Starter 'config/easykash.php') -Destination (Join-Path $Backend 'config/easykash.php') -Force

    $bootstrapPath = Join-Path $Backend 'bootstrap/app.php'
    $bootstrap = Get-Content -LiteralPath $bootstrapPath -Raw
    if ($bootstrap -notmatch 'EnsureAdmin::class') {
        $pattern = '->withMiddleware\(function \(Middleware \$middleware\): void \{'
        if ($bootstrap -notmatch $pattern) { throw 'Could not find Laravel withMiddleware() in bootstrap/app.php. Please use a clean Laravel 13 project.' }
        $replacement = "->withMiddleware(function (Middleware `$middleware): void {`r`n        `$middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);"
        $needle = '->withMiddleware(function (Middleware $middleware): void {'
        $bootstrap = $bootstrap.Replace($needle, $replacement)
        [System.IO.File]::WriteAllText($bootstrapPath, $bootstrap, [System.Text.UTF8Encoding]::new($false))
    }

    $envPath = Join-Path $Backend '.env'
    if (-not (Test-Path $envPath)) { Copy-Item '.env.example' '.env' }
    if (-not (Test-Path 'database/database.sqlite')) { New-Item -ItemType File -Path 'database/database.sqlite' -Force | Out-Null }

    Set-EnvValue $envPath 'APP_NAME' 'KFS Runners API'
    Set-EnvValue $envPath 'APP_ENV' 'local'
    Set-EnvValue $envPath 'APP_DEBUG' 'true'
    Set-EnvValue $envPath 'APP_URL' 'http://127.0.0.1:8000'
    Set-EnvValue $envPath 'FRONTEND_URL' 'http://localhost:5173'
    Set-EnvValue $envPath 'CORS_ALLOWED_ORIGINS' 'http://localhost:5173,http://127.0.0.1:5173'
    Set-EnvValue $envPath 'DB_CONNECTION' 'sqlite'
    Set-EnvValue $envPath 'DB_DATABASE' 'database/database.sqlite'

    Write-Host ''
    $adminName = Read-Host 'Local admin display name [KFS Administrator]'
    if ([string]::IsNullOrWhiteSpace($adminName)) { $adminName = 'KFS Administrator' }
    $adminEmail = Read-Host 'Local admin email [admin@kfs.local]'
    if ([string]::IsNullOrWhiteSpace($adminEmail)) { $adminEmail = 'admin@kfs.local' }
    if ($adminEmail -notmatch '^[^\s@]+@[^\s@]+\.[^\s@]+$') { throw 'Please enter a valid admin email address.' }
    do {
        $securePassword = Read-Host 'Local admin password (at least 12 characters; avoid double quotes)' -AsSecureString
        $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
        try { $adminPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer) }
        if ($adminPassword.Contains('"') -or $adminPassword.Contains("`r") -or $adminPassword.Contains("`n")) { Write-Host 'Do not use a double quote or newline in this local setup password.' -ForegroundColor Yellow; $adminPassword = '' }
        if ($adminPassword.Length -lt 12) { Write-Host 'Password must be at least 12 characters.' -ForegroundColor Yellow }
    } while ($adminPassword.Length -lt 12)

    Set-EnvValue $envPath 'KFS_ADMIN_NAME' $adminName
    Set-EnvValue $envPath 'KFS_ADMIN_EMAIL' $adminEmail
    Set-EnvValue $envPath 'KFS_ADMIN_PASSWORD' $adminPassword

    $envContent = Get-Content -LiteralPath $envPath -Raw
    if ($envContent -notmatch '(?m)^APP_KEY=base64:.+') {
        Invoke-Checked 'Generate local application key' { php artisan key:generate --force }
    } else {
        Write-Host 'Existing APP_KEY found; preserving it so encrypted local data remains readable.'
    }
    Invoke-Checked 'Run SQLite migrations and seed race/pricing/admin data' { php artisan migrate --seed --force }
    Invoke-Checked 'List registered API routes' { php artisan route:list --path=api }
} finally {
    Pop-Location
}

Write-Host "`n== Configure React ==" -ForegroundColor Cyan
[System.IO.File]::WriteAllText((Join-Path $Root '.env.local'), 'VITE_API_BASE_URL=http://127.0.0.1:8000' + [Environment]::NewLine, [System.Text.UTF8Encoding]::new($false))
if (-not (Test-Path (Join-Path $Root 'node_modules')) -or -not (Test-Path (Join-Path $Root 'package-lock.json'))) {
    Invoke-Checked 'Install React dependencies' { npm.cmd --prefix $Root install --no-audit --no-fund }
} else {
    Write-Host 'node_modules already exists; skipping npm install.'
}
Invoke-Checked 'Build React frontend check' { npm.cmd --prefix $Root run build }

Write-Host "`nLocal setup complete." -ForegroundColor Green
Write-Host 'Run .\start-local.ps1 to start Laravel and React in separate windows.'
Write-Host 'Frontend: http://localhost:5173'
Write-Host 'Registration: http://localhost:5173/register'
Write-Host 'Admin login: http://localhost:5173/admin/login'
Write-Host "Admin email: $adminEmail"
Write-Host 'Admin password: the one you entered during setup (not printed for safety).'
Write-Host 'No live payment integration is enabled; local registrations are saved as pending.'
