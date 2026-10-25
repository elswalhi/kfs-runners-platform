<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'ar'];

        if ($request->has('lang') && in_array($request->query('lang'), $supported, true)) {
            $lang = (string) $request->query('lang');
            session(['locale' => $lang]);
        }

        $sessionLocale = session('locale');
        if (! in_array($sessionLocale, $supported, true)) {
            try {
                $sessionLocale = AppSetting::get('default_locale', config('app.locale', 'en'));
            } catch (\Throwable) {
                $sessionLocale = config('app.locale', 'en');
            }
        }

        if (! in_array($sessionLocale, $supported, true)) {
            $sessionLocale = 'en';
        }

        app()->setLocale($sessionLocale);
        view()->share('currentLocale', $sessionLocale);
        view()->share('isRtl', $sessionLocale === 'ar');

        return $next($request);
    }
}
