# Run locally

The complete KFS Runners app is served by Laravel Blade; a React/Vite dev server is no longer needed.

## Requirements

- PHP 8.3 or newer with `pdo_sqlite`
- Composer

## First-time setup

From the repository folder, run:

```powershell
.\setup-local.ps1
```

This installs Composer packages if needed, creates a local SQLite database, asks for an admin email/password, and runs the Laravel migrations and seeders.

## Start the site

```powershell
.\start-local.ps1
```

Then open `http://127.0.0.1:8000`. Use `/register` for runner registration and `/admin/login` for the dashboard. `php artisan serve` runs in the same PowerShell window; press Ctrl+C to stop it.

## Check local routes

With the server running, execute:

```powershell
.\check-local.ps1
```
