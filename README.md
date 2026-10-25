# KFS Runners

KFS Runners is a Laravel application with Blade-rendered public pages and an admin dashboard. It does not use React or a separate frontend server. Laravel serves the home page, runner registration, EasyKash return/status page, admin login, dashboard, race/pricing management, and Excel team imports.

## Application

The Laravel app lives in [`backend/`](backend/). It requires PHP 8.3+, Composer, and MySQL in production. Local setup uses SQLite.

```powershell
.\setup-local.ps1
.\start-local.ps1
```

Open:

- Website: `http://127.0.0.1:8000`
- Registration: `http://127.0.0.1:8000/register`
- Admin: `http://127.0.0.1:8000/admin/login`

The setup script creates a local administrator and seeds the race categories and prices. Keep administrator credentials and EasyKash credentials in the Laravel `.env` file. Never commit secrets.

## Payment flow

Laravel creates an EasyKash hosted checkout link and redirects the runner to it. EasyKash returns the browser to `/register`; payment is confirmed only after Laravel verifies the provider's signed callback at `/api/payments/easykash/callback`.

## Hostinger

See [`HOSTINGER_DEPLOYMENT.md`](HOSTINGER_DEPLOYMENT.md) for the single-site Shared Hosting deployment steps and package command.
