<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\PublicRegistrationController;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Race;
use App\Models\Registration;
use App\Services\BibNumberService;
use App\Services\EasyKashCashApi;
use App\Services\RegistrationEmailService;
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
        $currentEvent = Event::query()->where('is_default', true)->first()
            ?? Event::query()->where('is_current', true)->orderByDesc('starts_at')->first();
        $racesQuery = $currentEvent
            ? $currentEvent->races()
            : Race::query()->whereNull('event_id');
        $races = $racesQuery
            ->where('is_active', true)
            ->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $raceDate = $currentEvent?->starts_at?->format('Y-m-d') ?? AppSetting::get('race_date', '2026-11-20');
        $raceTime = $currentEvent?->starts_at?->format('H:i') ?? AppSetting::get('race_time', '07:00');
        $raceDateFormatted = \Illuminate\Support\Carbon::parse($raceDate)->format('l, d F Y');
        $raceDateTime = \Illuminate\Support\Carbon::parse($raceDate . ' ' . $raceTime, 'Africa/Cairo')->toIso8601String();
        $countdownEnabled = $currentEvent?->countdown_enabled ?? (bool) AppSetting::get('countdown_enabled', true);
        $eventLocation = $currentEvent?->translated('location') ?: __('Kafr El-Sheikh, Egypt');

        return view('site.event', compact('currentEvent', 'eventLocation', 'races', 'raceDate', 'raceTime', 'raceDateFormatted', 'raceDateTime', 'countdownEnabled'));
    }

    public function register(Request $request)
    {
        $sessionReference = session()->pull('kfs_last_payment_reference');
        $returnReference = $request->query('customerReference')
            ?: $request->query('reference')
            ?: $sessionReference;

        $event = $request->filled('event')
            ? Event::query()->where('slug', $request->query('event'))->first()
            : null;

        if (! $event && filled($returnReference)) {
            $event = Registration::query()
                ->with('race.event')
                ->where('registration_code', $returnReference)
                ->first()?->race?->event;
        }

        $event ??= Event::query()->where('is_default', true)->first()
            ?? Event::query()->where('is_current', true)->where('registration_enabled', true)->orderByDesc('starts_at')->first();

        return $this->renderRegistration($request, $event, $returnReference);
    }

    public function registerForEvent(Request $request, Event $event)
    {
        $returnReference = $request->query('customerReference')
            ?: $request->query('reference')
            ?: session()->pull('kfs_last_payment_reference');

        abort_unless($event->registration_enabled || filled($returnReference), 404);

        return $this->renderRegistration($request, $event, $returnReference);
    }

    private function renderRegistration(Request $request, ?Event $event, ?string $returnReference)
    {
        $racesQuery = $event
            ? $event->races()
            : Race::query()->whereNull('event_id');

        $races = $racesQuery
            ->where('is_active', true)
            ->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        if (! $event && $races->isEmpty()) {
            $races = Race::query()->where('is_active', true)
                ->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('sort_order')->get();
        }

        $eventName = $event?->translated('title');
        $registrationOpen = $event?->registration_enabled ?? true;

        return view('site.register', compact('event', 'eventName', 'registrationOpen', 'races', 'returnReference'));
    }

    public function storeRegistration(Request $request, PublicRegistrationController $registrations, EasyKashCashApi $easyKash, RegistrationEmailService $registrationEmails, BibNumberService $bibNumbers)
    {
        $response = $registrations->store($request, $easyKash, $registrationEmails, $bibNumbers);

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

        return redirect()->route('register', array_filter(['event' => $request->input('event')]))
            ->withInput($request->except(['idFile', 'terms']))
            ->with('payment_error', data_get($payload, 'message', 'We could not start payment. Please contact the race team.'))
            ->with('payment_reference', data_get($payload, 'data.reference'));
    }
}
