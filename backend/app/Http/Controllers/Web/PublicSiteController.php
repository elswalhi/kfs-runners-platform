<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\PublicRegistrationController;
use App\Models\AppSetting;
use App\Models\Race;
use App\Services\EasyKashCashApi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class PublicSiteController
{
    public function home()
    {
        return view('site.home');
    }

    public function event()
    {
        $races = Race::query()
            ->where('is_active', true)
            ->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $raceDate = AppSetting::get('race_date', '2026-11-20');
        $raceTime = AppSetting::get('race_time', '07:00');
        $raceDateFormatted = \Illuminate\Support\Carbon::parse($raceDate)->format('l, d F Y');
        $raceDateTime = \Illuminate\Support\Carbon::parse($raceDate . ' ' . $raceTime, 'Africa/Cairo')->toIso8601String();
        $countdownEnabled = (bool) AppSetting::get('countdown_enabled', true);

        return view('site.event', compact('races', 'raceDate', 'raceTime', 'raceDateFormatted', 'raceDateTime', 'countdownEnabled'));
    }

    public function register()
    {
        $races = Race::query()
            ->where('is_active', true)
            ->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $sessionReference = session()->pull('kfs_last_payment_reference');
        $returnReference = request()->query('customerReference')
            ?: request()->query('reference')
            ?: $sessionReference;

        return view('site.register', compact('races', 'returnReference'));
    }

    public function storeRegistration(Request $request, PublicRegistrationController $registrations, EasyKashCashApi $easyKash)
    {
        $response = $registrations->store($request, $easyKash);

        if (! $response instanceof JsonResponse) {
            return back()->withInput()->with('payment_error', 'We could not start the registration. Please try again.');
        }

        $payload = $response->getData(true);
        if ($response->getStatusCode() === 201) {
            $reference = data_get($payload, 'payment.reference') ?: data_get($payload, 'data.reference');
            if ($reference) {
                $request->session()->put('kfs_last_payment_reference', $reference);
            }

            if (filled(data_get($payload, 'payment.redirect_url'))) {
                return redirect()->away(data_get($payload, 'payment.redirect_url'));
            }

            if (data_get($payload, 'data.payment_status') === 'paid') {
                return redirect()->route('register', [
                    'customerReference' => $reference,
                    'status' => 'PAID',
                ]);
            }
        }

        return redirect()->route('register')
            ->withInput($request->except(['idFile', 'terms']))
            ->with('payment_error', data_get($payload, 'message', 'We could not start payment. Please contact the race team.'))
            ->with('payment_reference', data_get($payload, 'data.reference'));
    }
}
