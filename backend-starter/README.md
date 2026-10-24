# Laravel API starter overlay (Laravel 13)

This folder contains the KFS Runners application layer, **not the Laravel framework/vendor directory**. It targets Laravel 13 / PHP 8.3+, then copy the contents of this folder into a clean Laravel project.

## Install

```bash
composer create-project laravel/laravel kfs-backend
cd kfs-backend
php artisan install:api
```

Copy all files from `backend-starter/` into the new `kfs-backend/` project, merging folders and replacing `routes/api.php` and `database/seeders/DatabaseSeeder.php` when prompted. Do not copy a `vendor` folder from anywhere. In `bootstrap/app.php`, add `use App\Http\Middleware\EnsureAdmin;` with the other imports and add `$middleware->alias(['admin' => EnsureAdmin::class]);` inside the existing `withMiddleware()` callback.

Configure `.env` with MySQL and set a strong admin password:

```env
APP_NAME="KFS Runners API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:5173
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kfs_runners
DB_USERNAME=root
DB_PASSWORD=
KFS_ADMIN_NAME="KFS Administrator"
KFS_ADMIN_EMAIL=admin@example.com
KFS_ADMIN_PASSWORD="replace-with-a-long-random-password"
EASYKASH_CASH_API_URL=https://back.easykash.net/api/cash-api/create
EASYKASH_API_KEY=your-easykash-api-key
EASYKASH_HMAC_SECRET=your-easykash-hmac-secret
EASYKASH_EXPIRY_DURATION=48
```

Then run:

```bash
php artisan migrate --seed
php artisan serve
```

Never deploy with `APP_DEBUG=true`, never reuse the example credentials, and use HTTPS in production. Store uploaded runner documents on Laravel's private local disk and provide access only through authenticated controller endpoints. Keep gateway secrets in server-side environment variables.

## API routes

- `POST /api/admin/login` — returns a Sanctum personal access token for an admin.
- `POST /api/admin/logout` — revoke current token.
- `GET /api/admin/me` — current admin.
- `GET /api/admin/dashboard` — registration/payment metrics and latest records.
- `GET /api/admin/registrations` — filter/search/paginate registrations.
- `GET /api/admin/registrations/{id}` — authorized runner details, with ID masked.
- `GET /api/admin/registrations/{id}/document` — secure authenticated download of the runner document.
- `PATCH /api/admin/registrations/{id}` — update operational status only.
- `GET /api/admin/payments` — payment attempts.
- `GET /api/admin/race-kit-prices` — prices for the dashboard.
- `PUT /api/admin/race-kit-prices/{id}` — update one price.
- `GET /api/races` — public race categories and active prices.
- `POST /api/registrations` — validate and create a pending registration.
- `GET /api/registrations/{reference}/payment-status` — return the registration's public payment status and cash voucher details.
- `POST /api/payments/easykash/callback` — verify EasyKash HMAC-SHA512 callbacks and update payment status.

## Payments status

The registration flow creates an EasyKash cash voucher from Laravel and displays it to the runner. Configure `EASYKASH_API_KEY` and `EASYKASH_HMAC_SECRET` in the server's `.env`; never put them in React or a committed file. The EasyKash account must have Cash API enabled, and its Integration Settings callback URL must point to `https://YOUR-API-HOST/api/payments/easykash/callback`. Payment status changes only after a signed callback passes HMAC-SHA512 verification and amount matching. A browser redirect or client request never marks a registration paid.
