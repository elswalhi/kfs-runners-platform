# Local-first quick start (Windows)

1. Install PHP 8.3+ (`pdo_sqlite` enabled), Composer 2 and Node.js 20+.
2. Extract this ZIP to `C:\Projects\kfs-runners-platform`.
3. Open PowerShell in that folder.
4. Run `Set-ExecutionPolicy -Scope Process Bypass` if your current shell blocks scripts.
5. Run `.\setup-local.ps1` or double-click `setup-local.bat`.
6. Enter your local admin name, email and password when prompted.
7. After the setup finishes, run `.\start-local.ps1` or double-click `start-local.bat`.
8. Open `http://localhost:5173/admin/login` and sign in with the credentials you chose.

The setup builds a complete Laravel app under `backend/`, uses `backend/database/database.sqlite` for local data, loads race/kit seed prices, and configures React to use the local API at `http://127.0.0.1:8000`. It doesn't connect to or modify WordPress. Laravel creates an EasyKash hosted checkout link when `EASYKASH_API_KEY` and `EASYKASH_HMAC_SECRET` are configured in `backend/.env`; EasyKash displays the payment methods enabled for the merchant account. For production, set `EASYKASH_REDIRECT_URL` to the public React registration URL, deploy Laravel to a public HTTPS host, then set the EasyKash Integration Settings callback URL to `https://YOUR-API-HOST/api/payments/easykash/callback`. Payment success is shown after Laravel verifies EasyKash's signed callback.

If setup stops, copy the first actual error from the PowerShell window. Most common causes are an outdated PHP version, missing `pdo_sqlite`, unavailable Composer/npm network access, or a failed Laravel scaffold/install step.
