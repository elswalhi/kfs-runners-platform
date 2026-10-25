# KFS Runners Laravel application

This is the Laravel 13 application. All site and administration screens use Blade templates. Browser-side JavaScript is limited to form interactions, payment status polling, and reading the Excel team roster; there is no React application or separate frontend service.

## Routes

- `GET /` — public event landing page.
- `GET, POST /register` — runner registration and EasyKash hosted checkout redirect.
- `GET /admin/login` — administrator sign-in.
- `GET /admin` — dashboard.
- `/admin/registrations`, `/admin/payments`, `/admin/races`, `/admin/pricing`, and `/admin/team-import` — protected operations pages.
- `POST /api/payments/easykash/callback` — signed EasyKash server callback.

The existing JSON API remains available for integrations, but the Blade screens render through Laravel web routes and Laravel session authentication.

## Local commands

From this directory:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Configure `KFS_ADMIN_EMAIL`, `KFS_ADMIN_PASSWORD`, `EASYKASH_API_KEY`, and `EASYKASH_HMAC_SECRET` in `.env`. Rotate credentials that have been shared in chat before production use.
