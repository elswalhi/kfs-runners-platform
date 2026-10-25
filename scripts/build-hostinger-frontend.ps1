param(
    [Parameter(Mandatory = $true)]
    [string] $ApiBaseUrl
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = Split-Path -Parent $PSScriptRoot

try {
    $parsedApiUrl = [Uri] $ApiBaseUrl
} catch {
    throw 'ApiBaseUrl must be an absolute HTTPS URL, for example https://api.example.com.'
}

if (-not $parsedApiUrl.IsAbsoluteUri -or $parsedApiUrl.Scheme -ne 'https' -or $parsedApiUrl.AbsolutePath.TrimEnd('/') -ne '' -or $parsedApiUrl.Query -or $parsedApiUrl.Fragment) {
    throw 'ApiBaseUrl must be the HTTPS origin only, without /api, a path, query, or fragment.'
}

$previousApiBaseUrl = $env:VITE_API_BASE_URL

try {
    Push-Location $repositoryRoot

    $viteCommand = Join-Path $repositoryRoot 'node_modules\.bin\vite.cmd'
    if (-not (Test-Path -LiteralPath $viteCommand)) {
        Write-Host 'Installing locked frontend dependencies...'
        & npm.cmd ci
        if ($LASTEXITCODE -ne 0) { throw 'npm ci failed. Stop any running Vite server and retry.' }
    } else {
        Write-Host 'Using the installed frontend dependencies.'
    }

    $env:VITE_API_BASE_URL = $parsedApiUrl.GetLeftPart([UriPartial]::Authority)
    Write-Host ('Building the React app for API ' + $env:VITE_API_BASE_URL)
    & npm.cmd run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed.' }

    Copy-Item -LiteralPath (Join-Path $repositoryRoot 'deploy\hostinger\spa.htaccess') -Destination (Join-Path $repositoryRoot 'dist\.htaccess') -Force
    Write-Host ('Ready to upload: ' + (Join-Path $repositoryRoot 'dist'))
    Write-Host 'Upload the contents of dist/ to the main website public_html folder.'
} finally {
    Pop-Location
    if ($null -eq $previousApiBaseUrl) {
        Remove-Item Env:VITE_API_BASE_URL -ErrorAction SilentlyContinue
    } else {
        $env:VITE_API_BASE_URL = $previousApiBaseUrl
    }
}
