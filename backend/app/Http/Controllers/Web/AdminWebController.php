<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\Admin\TeamImportController;
use App\Mail\RunnerQrMail;
use App\Models\AgeGroup;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\RaceKitPrice;
use App\Models\Registration;
use App\Models\Team;
use App\Services\EasyKashCashApi;
use App\Services\QrCodeService;
use App\Support\SiteMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class AdminWebController
{
    public function dashboard()
    {
        $total = Registration::query()->count();
        $paid = Registration::query()->where('payment_status', 'paid')->count();
        $pending = Registration::query()->where('payment_status', 'pending')->count();
        $failed = Registration::query()->whereIn('payment_status', ['failed', 'refunded'])->count();
        $revenue = Registration::query()->where('payment_status', 'paid')->sum('amount');
        $recent = Registration::query()->with('race')->latest()->limit(8)->get();
        $ageGroups = AgeGroup::query()->orderBy('min_age')->get();
        $raceBreakdown = Race::query()->withCount('registrations')->orderBy('sort_order')->get();
        $teamCount = Team::query()->count();
        $paymentCount = Payment::query()->count();
        $trend = collect(range(6, 0))->map(function (int $daysAgo) {
            $day = Carbon::today()->subDays($daysAgo);
            return ['label' => $day->format('D'), 'value' => (float) Registration::query()->where('payment_status', 'paid')->whereDate('created_at', $day)->sum('amount')];
        });

        $defaultEvent = Event::query()->where('is_default', true)->first();
        $raceDate = $defaultEvent?->starts_at?->format('Y-m-d') ?? AppSetting::get('race_date', '2026-11-20');
        $ageDate = Carbon::parse($raceDate);
        $recent->each(function (Registration $registration) use ($ageGroups, $ageDate) {
            $age = $registration->ageOn($ageDate);
            $registration->setAttribute('age_on_race_day', $age);
            $registration->setAttribute('age_group_name', $ageGroups->first(fn (AgeGroup $group) => $group->containsAge($age))?->name);
        });
        $raceTime = $defaultEvent?->starts_at?->format('H:i') ?? AppSetting::get('race_time', '07:00');
        $countdownEnabled = $defaultEvent?->countdown_enabled ?? (bool) AppSetting::get('countdown_enabled', true);

        return view('admin.dashboard', compact('total', 'paid', 'pending', 'failed', 'revenue', 'recent', 'raceBreakdown', 'trend', 'raceDate', 'raceTime', 'countdownEnabled', 'teamCount', 'paymentCount'));
    }

    public function registrations(Request $request)
    {
        $registrations = $this->registrationQuery($request)->paginate(25)->withQueryString();
        $races = Race::query()->with('event')->orderBy('event_id')->orderBy('sort_order')->get();
        $events = Event::query()->orderByDesc('starts_at')->orderByDesc('event_year')->get();
        $ageGroups = AgeGroup::query()->orderBy('sort_order')->orderBy('min_age')->get();
        $raceDate = Carbon::parse(AppSetting::get('race_date', '2026-11-20'));
        $registrations->getCollection()->each(function (Registration $registration) use ($ageGroups, $raceDate) {
            $age = $registration->ageOn($registration->race?->event?->starts_at ?? $raceDate);
            $registration->setAttribute('age_on_race_day', $age);
            $registration->setAttribute('age_group_name', $ageGroups->first(fn (AgeGroup $group) => $group->containsAge($age))?->name);
        });
        $paidRunnerCount = Registration::query()->where('payment_status', 'paid')->count();

        return view('admin.registrations', compact('registrations', 'races', 'events', 'ageGroups', 'raceDate', 'paidRunnerCount'));
    }

    public function exportRegistrations(Request $request)
    {
        $rows = $this->registrationQuery($request)->cursor();
        $ageGroups = AgeGroup::query()->orderBy('min_age')->get();
        $raceDate = Carbon::parse(AppSetting::get('race_date', '2026-11-20'));

        return response()->streamDownload(function () use ($rows, $ageGroups, $raceDate) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['reference', 'race_number', 'event', 'age_on_race_day', 'age_group', 'first_name', 'last_name', 'email', 'phone', 'race', 'kit_type', 'shirt_size', 'amount', 'currency', 'registration_status', 'payment_status', 'created_at']);
            foreach ($rows as $registration) {
                $age = $registration->ageOn($registration->race?->event?->starts_at ?? $raceDate);
                $ageGroup = $ageGroups->first(fn (AgeGroup $group) => $group->containsAge($age))?->name;
                $values = [$registration->registration_code, $registration->bib_number, $registration->race?->event?->title, $age, $ageGroup, $registration->first_name, $registration->last_name, $registration->email, $registration->phone, $registration->race?->name, $registration->kit_type, $registration->shirt_size, $registration->amount, $registration->currency, $registration->status, $registration->payment_status, $registration->created_at?->toISOString()];
                fputcsv($output, array_map(static fn ($value) => is_string($value) && preg_match('/\A[=+@-]/', $value) ? "'" . $value : $value, $values));
            }
            fclose($output);
        }, 'kfs-registrations.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportOverview()
    {
        $rows = [
            ['metric', 'value'],
            ['Total registrations', Registration::query()->count()],
            ['Paid registrations', Registration::query()->where('payment_status', 'paid')->count()],
            ['Pending payments', Registration::query()->where('payment_status', 'pending')->count()],
            ['Failed or refunded payments', Registration::query()->whereIn('payment_status', ['failed', 'refunded'])->count()],
            ['Confirmed revenue EGP', Registration::query()->where('payment_status', 'paid')->sum('amount')],
        ];

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            foreach ($rows as $row) fputcsv($output, $row);
            fclose($output);
        }, 'kfs-dashboard-overview.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function clearAllRegistrations(Request $request)
    {
        $request->validate([
            'confirmation_phrase' => ['required', Rule::in(['DELETE ALL'])],
        ]);

        $cleared = DB::transaction(function () {
            $races = Race::query()->orderBy('id')->lockForUpdate()->get();
            $documentPaths = Registration::query()->whereNotNull('document_path')->pluck('document_path')->all();
            $registrationCount = Registration::query()->count();
            $teamCount = Team::query()->count();
            $paymentCount = Payment::query()->count();

            Payment::query()->delete();
            Registration::query()->delete();
            Team::query()->delete();
            PromoCode::query()->update(['times_used' => 0]);

            foreach ($races as $race) {
                $race->update(['next_bib_number' => $race->bib_number_start]);
            }

            return [
                'documents' => $documentPaths,
                'registrations' => $registrationCount,
                'teams' => $teamCount,
                'payments' => $paymentCount,
            ];
        }, 3);

        Storage::disk('local')->delete($cleared['documents']);

        Log::warning('Admin cleared all KFS registration data.', [
            'admin_id' => $request->user()->id,
            'registrations' => $cleared['registrations'],
            'teams' => $cleared['teams'],
            'payments' => $cleared['payments'],
        ]);

        return back()->with('success', __('Removed :registrations registrations, :teams teams, and :payments payment records. Bib sequences reset.', [
            'registrations' => $cleared['registrations'],
            'teams' => $cleared['teams'],
            'payments' => $cleared['payments'],
        ]));
    }

    public function registration(Registration $registration)
    {
        $registration->load(['race', 'team', 'payments', 'team.payments']);
        $raceDate = Carbon::parse(AppSetting::get('race_date', '2026-11-20'));
        $runnerAge = $registration->ageOn($raceDate);
        $ageGroupName = AgeGroup::query()->where('min_age', '<=', $runnerAge)->where('max_age', '>=', $runnerAge)->value('name');
        $payments = $registration->payments->merge($registration->team?->payments ?? collect())->unique('id')->sortByDesc('created_at');
        $idNumber = $registration->decryptedIdNumber();
        $idNumberDisplay = $idNumber ?? '—';
        $idNumberUnavailable = $idNumber === null && filled($registration->getRawOriginal('id_number'));
        $qrDataUri = null;
        $qrPayload = null;
        if ($registration->payment_status === 'paid' && $registration->bib_number) {
            $qrDataUri = QrCodeService::generateRunnerQr($registration, 260);
            $qrPayload = QrCodeService::buildRunnerPayload($registration);
        }

        return view('admin.registration', compact('registration', 'payments', 'idNumberDisplay', 'idNumberUnavailable', 'qrDataUri', 'qrPayload', 'raceDate', 'runnerAge', 'ageGroupName'));
    }

    public function sendRegistrationQr(Registration $registration)
    {
        if (! filter_var($registration->email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(__('This runner does not have a valid email address.'));
        }

        if ($registration->payment_status !== 'paid' || ! $registration->bib_number) {
            return back()->withErrors(__('The kit pickup QR can only be emailed after payment is confirmed.'));
        }

        if (config('mail.default') === 'log') {
            return back()->withErrors(__('Email delivery is not configured. Set up SMTP before sending runner QR codes.'));
        }

        try {
            if (! $this->sendRunnerQrReminder($registration)) {
                return back()->withErrors(__('No QR fields are enabled. Select at least one field in Runner QR Settings first.'));
            }
        } catch (Throwable $exception) {
            Log::warning('Could not email the runner kit pickup QR.', [
                'registration_reference' => $registration->registration_code,
                'exception' => $exception::class,
            ]);

            return back()->withErrors(__('Could not send the runner QR email. Check the mail settings and try again.'));
        }

        return back()->with('success', __('Runner QR reminder sent to :email.', ['email' => $registration->email]));
    }

    public function sendQrRemindersToPaidRunners()
    {
        if (config('mail.default') === 'log') {
            return back()->withErrors(__('Email delivery is not configured. Set up SMTP before sending runner QR codes.'));
        }

        $paidRunnerCount = Registration::query()->where('payment_status', 'paid')->count();
        if ($paidRunnerCount === 0) {
            return back()->with('success', __('There are no paid runners to remind.'));
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        Registration::query()
            ->with(['race', 'team'])
            ->where('payment_status', 'paid')
            ->orderBy('id')
            ->chunkById(25, function ($registrations) use (&$sent, &$skipped, &$failed) {
                foreach ($registrations as $registration) {
                    if (! $registration->bib_number || ! filter_var($registration->email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }

                    try {
                        if ($this->sendRunnerQrReminder($registration)) {
                            $sent++;
                        } else {
                            $skipped++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::warning('Could not send a bulk runner QR reminder.', [
                            'registration_reference' => $registration->registration_code,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            });

        return back()->with('success', __('QR reminders sent: :sent; skipped: :skipped; failed: :failed.', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
        ]));
    }

    private function sendRunnerQrReminder(Registration $registration): bool
    {
        $registration = Registration::query()
            ->with(['race.event', 'team'])
            ->whereKey($registration->getKey())
            ->where('payment_status', 'paid')
            ->whereNotNull('bib_number')
            ->first();

        if (! $registration) {
            return false;
        }

        $qrPng = QrCodeService::generateRunnerQrPng($registration, 360);
        if ($qrPng === null) {
            return false;
        }

        Mail::to($registration->email)->send(new RunnerQrMail([
            'locale' => app()->getLocale(),
            'reference' => $registration->registration_code,
            'race_number' => $registration->bib_number,
            'name' => trim($registration->first_name . ' ' . $registration->last_name),
            'race' => $registration->race?->name ?? '—',
            'distance' => $registration->race?->distance_label ?? '—',
            'kit' => $registration->kit_type,
            'shirt_size' => $registration->shirt_size,
            'team' => $registration->team?->name ?? $registration->team_name,
            'event' => $registration->race?->event?->translated('title'),
        ], $qrPng));

        return true;
    }

    public function updateRegistration(Request $request, Registration $registration)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])],
            'bib_number' => [
                Rule::prohibitedIf($registration->payment_status !== 'paid'),
                Rule::requiredIf($registration->payment_status === 'paid'),
                'nullable',
                'integer',
                'min:1',
                'max:999999999',
                Rule::unique('registrations', 'bib_number')
                    ->where(fn ($query) => $query->where('race_id', $registration->race_id))
                    ->ignore($registration->id),
            ],
        ]);

        DB::transaction(function () use ($data, $registration) {
            $race = Race::query()->whereKey($registration->race_id)->lockForUpdate()->firstOrFail();
            $registration->update($data);

            if ((int) $registration->bib_number >= (int) $race->next_bib_number) {
                $race->update(['next_bib_number' => (int) $registration->bib_number + 1]);
            }
        });

        return back()->with('success', 'Registration status saved. Payment status remains controlled by EasyKash callback.');
    }

    public function registrationDocument(Registration $registration)
    {
        abort_unless($registration->document_path && Storage::disk('local')->exists($registration->document_path), 404);

        return Storage::disk('local')->download($registration->document_path, 'runner-document-' . $registration->registration_code);
    }

    public function payments()
    {
        $payments = Payment::query()->with(['registration.race', 'team'])->latest()->paginate(50);
        $paidTotal = Payment::query()->whereIn('status', ['paid', 'succeeded'])->sum('amount');
        $needsAttention = Payment::query()->whereIn('status', ['pending', 'failed'])->count();

        return view('admin.payments', compact('payments', 'paidTotal', 'needsAttention'));
    }

    public function races()
    {
        $races = Race::query()->with('kitPrices')->withCount('registrations')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.races', compact('races'));
    }

    public function storeRace(Request $request)
    {
        $defaultEventId = Event::query()->where('is_default', true)->value('id');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')->where('event_id', $defaultEventId)],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'bib_number_start' => ['required', 'integer', 'min:1', 'max:999999999'],
            'full_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'basic_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
        ]);
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($data, $isActive, $defaultEventId) {
            $race = Race::query()->create([
                'event_id' => $defaultEventId,
                'name' => trim($data['name']), 'slug' => strtolower($data['slug']),
                'distance_label' => trim($data['distance_label']), 'category_type' => $data['category_type'],
                'sort_order' => $data['sort_order'], 'is_active' => $isActive,
                'bib_number_start' => $data['bib_number_start'],
                'next_bib_number' => $data['bib_number_start'],
            ]);
            foreach (['full' => $data['full_price'], 'basic' => $data['basic_price']] as $kit => $price) {
                $race->kitPrices()->create(['kit_type' => $kit, 'price' => $price, 'currency' => 'EGP', 'is_active' => true]);
            }
        });

        return back()->with('success', 'Race and Full / Basic kit prices created.');
    }

    public function updateRace(Request $request, Race $race)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')->where('event_id', $race->event_id)->ignore($race->id)],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'bib_number_start' => ['required', 'integer', 'min:1', 'max:999999999'],
        ]);
        $isActive = $request->boolean('is_active');
        if ($isActive) {
            $activeKits = $race->kitPrices()->where('is_active', true)->pluck('kit_type')->unique();
            if (! $activeKits->contains('full') || ! $activeKits->contains('basic')) {
                return back()->withErrors(['is_active' => 'Add an active Full and Basic price before activating this race.'])->withInput();
            }
        }
        $nextBibNumber = max(
            (int) $race->next_bib_number,
            (int) $data['bib_number_start'],
            ((int) $race->registrations()->max('bib_number')) + 1,
        );
        $race->update([
            ...$data,
            'name' => trim($data['name']),
            'slug' => strtolower($data['slug']),
            'distance_label' => trim($data['distance_label']),
            'is_active' => $isActive,
            'next_bib_number' => $nextBibNumber,
        ]);

        return back()->with('success', 'Race details saved.');
    }

    public function deleteRace(Race $race)
    {
        if ($race->registrations()->exists()) return back()->withErrors(['race' => 'A race with registrations cannot be deleted. Deactivate it instead.']);
        $race->delete();

        return back()->with('success', 'Race deleted.');
    }

    public function pricing()
    {
        $races = Race::query()->with(['event', 'kitPrices'])->orderBy('event_id')->orderBy('sort_order')->get();
        $prices = RaceKitPrice::query()->with('race')->orderBy('race_id')->orderBy('kit_type')->get();

        return view('admin.pricing', compact('races', 'prices'));
    }

    public function storePrice(Request $request)
    {
        $data = $request->validate([
            'race_id' => ['required', 'integer', 'exists:races,id'],
            'kit_type' => ['required', Rule::in(['full', 'basic'])],
            'price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'currency' => ['required', 'string', 'size:3'],
        ]);
        if (RaceKitPrice::query()->where('race_id', $data['race_id'])->where('kit_type', $data['kit_type'])->exists()) {
            return back()->withErrors(['price' => 'This race already has a price for that kit. Edit the existing price instead.'])->withInput();
        }
        $race = Race::query()->findOrFail($data['race_id']);
        $race->kitPrices()->create([...$data, 'currency' => strtoupper($data['currency']), 'is_active' => true]);

        return back()->with('success', 'Race-kit price added.');
    }

    public function updatePrice(Request $request, RaceKitPrice $raceKitPrice)
    {
        $data = $request->validate(['price' => ['required', 'numeric', 'gt:1', 'max:1000000'], 'is_active' => ['nullable', 'boolean']]);
        $active = $request->boolean('is_active');
        if ($raceKitPrice->race->is_active && $raceKitPrice->is_active && ! $active && ! $raceKitPrice->race->kitPrices()->where('is_active', true)->where('kit_type', $raceKitPrice->kit_type)->where('id', '!=', $raceKitPrice->id)->exists()) {
            return back()->withErrors(['price' => 'An active race must keep Full and Basic kit prices active.']);
        }
        $raceKitPrice->update(['price' => $data['price'], 'is_active' => $active]);

        return back()->with('success', 'Race-kit price saved.');
    }

    public function deletePrice(RaceKitPrice $raceKitPrice)
    {
        if ($raceKitPrice->race->registrations()->where('kit_type', $raceKitPrice->kit_type)->exists()) {
            return back()->withErrors(['price' => 'This price is used by registrations. Deactivate it instead of deleting it.']);
        }
        if ($raceKitPrice->race->is_active && $raceKitPrice->is_active && ! $raceKitPrice->race->kitPrices()->where('is_active', true)->where('kit_type', $raceKitPrice->kit_type)->where('id', '!=', $raceKitPrice->id)->exists()) {
            return back()->withErrors(['price' => 'An active race must keep Full and Basic kit prices active.']);
        }
        $raceKitPrice->delete();

        return back()->with('success', 'Race-kit price deleted.');
    }

    public function promoCodes()
    {
        $promoCodes = PromoCode::query()->with('race')->withCount('registrations')->latest()->get();
        $races = Race::query()->orderBy('sort_order')->get();

        $totalCodes = $promoCodes->count();
        $activeCodes = $promoCodes->filter->isUsable()->count();
        $totalRedemptions = (int) $promoCodes->sum('times_used');
        $totalDiscountGiven = (float) Registration::query()->where('payment_status', 'paid')->sum('discount_amount');

        return view('admin.promo-codes', compact('promoCodes', 'races', 'totalCodes', 'activeCodes', 'totalRedemptions', 'totalDiscountGiven'));
    }

    public function storePromoCode(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('promo_codes', 'code')],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'race_id' => ['nullable', 'integer', 'exists:races,id'],
        ]);

        if ($data['discount_type'] === 'percentage' && (float) $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100%.'])->withInput();
        }

        PromoCode::query()->create([
            'code' => strtoupper(trim($data['code'])),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_uses' => $data['max_uses'] ?: null,
            'valid_from' => $data['valid_from'] ?: null,
            'valid_until' => $data['valid_until'] ?: null,
            'race_id' => $data['race_id'] ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Promo code created successfully.');
    }

    public function updatePromoCode(Request $request, PromoCode $promoCode)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code', '')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('promo_codes', 'code')->ignore($promoCode->id)],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'race_id' => ['nullable', 'integer', 'exists:races,id'],
        ]);

        if ($data['discount_type'] === 'percentage' && (float) $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100%.'])->withInput();
        }

        if (isset($data['max_uses']) && (int) $data['max_uses'] < $promoCode->times_used) {
            return back()->withErrors(['max_uses' => 'The redemption limit cannot be lower than the number of times this code has already been used.'])->withInput();
        }

        $promoCode->update([
            'code' => $data['code'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_uses' => $data['max_uses'] ?: null,
            'valid_from' => $data['valid_from'] ?: null,
            'valid_until' => $data['valid_until'] ?: null,
            'race_id' => $data['race_id'] ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Promo code updated successfully.');
    }

    public function togglePromoCode(PromoCode $promoCode)
    {
        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        return back()->with('success', 'Promo code status updated.');
    }

    public function deletePromoCode(PromoCode $promoCode)
    {
        $promoCode->delete();

        return back()->with('success', 'Promo code deleted.');
    }

    public function localization()
    {
        $defaultLocale = AppSetting::get('default_locale', config('app.locale', 'en'));
        $showPublicSwitcher = (bool) AppSetting::get('show_public_switcher', true);
        $showAdminSwitcher = (bool) AppSetting::get('show_admin_switcher', true);
        $arabicFont = AppSetting::get('arabic_font', 'Cairo');

        $enPath = base_path('lang/en.json');
        $arPath = base_path('lang/ar.json');

        $enStrings = file_exists($enPath) ? json_decode(file_get_contents($enPath), true) ?: [] : [];
        $arStrings = file_exists($arPath) ? json_decode(file_get_contents($arPath), true) ?: [] : [];

        $allKeys = array_unique(array_merge(array_keys($enStrings), array_keys($arStrings)));
        sort($allKeys);

        $translations = collect($allKeys)->map(function ($key) use ($enStrings, $arStrings) {
            return [
                'key' => $key,
                'en' => $enStrings[$key] ?? $key,
                'ar' => $arStrings[$key] ?? '',
            ];
        });

        $totalStrings = count($allKeys);
        $translatedCount = count(array_filter($arStrings));

        return view('admin.localization', compact(
            'defaultLocale',
            'showPublicSwitcher',
            'showAdminSwitcher',
            'arabicFont',
            'translations',
            'totalStrings',
            'translatedCount'
        ));
    }

    public function updateLocalization(Request $request)
    {
        $data = $request->validate([
            'default_locale' => ['required', Rule::in(['en', 'ar'])],
            'arabic_font' => ['nullable', 'string', 'max:50'],
        ]);

        AppSetting::set('default_locale', $data['default_locale']);
        AppSetting::set('show_public_switcher', $request->boolean('show_public_switcher'));
        AppSetting::set('show_admin_switcher', $request->boolean('show_admin_switcher'));
        AppSetting::set('arabic_font', $data['arabic_font'] ?: 'Cairo');

        return back()->with('success', 'Localization settings saved.');
    }

    public function updateTranslations(Request $request)
    {
        $inputs = $request->input('translations', []);
        if (! is_array($inputs)) {
            return back()->withErrors(['translations' => 'Invalid translations format.']);
        }

        $arPath = base_path('lang/ar.json');
        $enPath = base_path('lang/en.json');

        $currentAr = file_exists($arPath) ? json_decode(file_get_contents($arPath), true) ?: [] : [];
        $currentEn = file_exists($enPath) ? json_decode(file_get_contents($enPath), true) ?: [] : [];

        foreach ($inputs as $key => $values) {
            if (isset($values['ar'])) {
                $currentAr[$key] = trim($values['ar']);
            }
            if (isset($values['en'])) {
                $currentEn[$key] = trim($values['en']);
            }
        }

        file_put_contents($arPath, json_encode($currentAr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($enPath, json_encode($currentEn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return back()->with('success', 'Translations dictionary updated successfully.');
    }

    public function eventSettings()
    {
        $event = Event::query()->where('is_default', true)->first();
        if ($event) {
            return redirect()->to(route('admin.events.configure', $event) . '#registration');
        }

        $raceDate = AppSetting::get('race_date', '2026-11-20');
        $raceTime = AppSetting::get('race_time', '07:00');
        $countdownEnabled = (bool) AppSetting::get('countdown_enabled', true);

        return view('admin.event-settings', compact('raceDate', 'raceTime', 'countdownEnabled'));
    }

    public function updateEventSettings(Request $request)
    {
        $data = $request->validate([
            'race_date' => ['required', 'date_format:Y-m-d'],
            'race_time' => ['required', 'string', 'max:10'],
            'countdown_enabled' => ['nullable'],
        ]);

        $event = Event::query()->where('is_default', true)->first();
        if ($event) {
            $event->update([
                'starts_at' => Carbon::parse($data['race_date'] . ' ' . $data['race_time']),
                'countdown_enabled' => $request->boolean('countdown_enabled'),
            ]);
        } else {
            AppSetting::set('race_date', $data['race_date']);
            AppSetting::set('race_time', $data['race_time']);
            AppSetting::set('countdown_enabled', $request->boolean('countdown_enabled'));
        }
        AppSetting::set('site_theme', 'original');

        return back()->with('success', __('Race date and countdown updated successfully!'));
    }

    public function qrSettings()
    {
        $event = Event::query()->where('is_default', true)->first();
        if ($event) {
            return redirect()->to(route('admin.events.configure', $event) . '#qr');
        }

        $qrFields = QrCodeService::runnerFields();
        $selectedQrFields = AppSetting::get('runner_qr_fields', QrCodeService::defaultRunnerFields());
        if (! is_array($selectedQrFields)) {
            $selectedQrFields = QrCodeService::defaultRunnerFields();
        }
        $selectedQrFields = array_values(array_intersect(array_keys($qrFields), $selectedQrFields));

        return view('admin.qr-settings', compact('qrFields', 'selectedQrFields'));
    }

    public function updateQrSettings(Request $request)
    {
        $qrFields = QrCodeService::runnerFields();
        $data = $request->validate([
            'qr_fields' => ['required', 'array', 'min:1'],
            'qr_fields.*' => ['required', 'string', Rule::in(array_keys($qrFields))],
        ]);

        $event = Event::query()->where('is_default', true)->first();
        if ($event) {
            $event->update(['qr_fields' => array_values(array_unique($data['qr_fields']))]);
        } else {
            AppSetting::set('runner_qr_fields', array_values(array_unique($data['qr_fields'])));
        }

        return back()->with('success', __('Runner QR settings saved successfully.'));
    }

    public function siteMedia()
    {
        $pages = SiteMedia::pages(Race::query()->orderBy('sort_order')->get());

        foreach ($pages as $pageKey => &$page) {
            foreach ($page['slots'] as $slotKey => &$slot) {
                $path = SiteMedia::path($pageKey, $slotKey);
                $slot['is_custom'] = $path && Storage::disk('public')->exists($path);
                $slot['image_url'] = SiteMedia::url($pageKey, $slotKey, asset($slot['fallback']));
            }
            unset($slot);
        }
        unset($page);

        $heroVideos = SiteMedia::videoSlots();
        foreach ($heroVideos as $pageKey => &$video) {
            $path = SiteMedia::videoPath($pageKey);
            $video['is_custom'] = $path && Storage::disk('public')->exists($path);
            $video['video_url'] = SiteMedia::videoUrl($pageKey);
            $video['image_url'] = SiteMedia::url($pageKey, $video['image_slot'], asset('images/kfs-runners-hero.png'));
        }
        unset($video);

        return view('admin.site-media', compact('pages', 'heroVideos'));
    }

    public function updateSiteMedia(Request $request, string $page, string $slot)
    {
        abort_unless(SiteMedia::slot($page, $slot), 404);

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $disk = Storage::disk('public');
        $newPath = $data['image']->store('site-media/' . $page, 'public');
        if (! $newPath) {
            return back()->withErrors(['image' => __('The image could not be saved. Please check the storage permissions and try again.')]);
        }

        $media = AppSetting::get('site_media', []);
        $media = is_array($media) ? $media : [];
        $oldPath = SiteMedia::path($page, $slot);
        $media[$page][$slot] = $newPath;
        AppSetting::set('site_media', $media);

        if ($oldPath && $oldPath !== $newPath) {
            $disk->delete($oldPath);
        }

        return back()->with('success', __('Image updated for :page.', ['page' => SiteMedia::pages()[$page]['label'] ?? $page]));
    }

    public function deleteSiteMedia(string $page, string $slot)
    {
        abort_unless(SiteMedia::slot($page, $slot), 404);

        $media = AppSetting::get('site_media', []);
        $media = is_array($media) ? $media : [];
        $oldPath = SiteMedia::path($page, $slot);
        unset($media[$page][$slot]);
        if (empty($media[$page])) {
            unset($media[$page]);
        }

        AppSetting::set('site_media', $media);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', __('Default image restored.'));
    }

    public function updateSiteVideo(Request $request, string $page, string $slot)
    {
        abort_unless(SiteMedia::videoSlot($page, $slot), 404);

        $data = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm', 'max:20480'],
        ]);

        $disk = Storage::disk('public');
        $newPath = $data['video']->store('site-media/' . $page . '/videos', 'public');
        if (! $newPath) {
            return back()->withErrors(['video' => __('The video could not be saved. Please check the storage permissions and try again.')]);
        }

        $videos = AppSetting::get('site_videos', []);
        $videos = is_array($videos) ? $videos : [];
        $oldPath = SiteMedia::videoPath($page, $slot);
        $videos[$page][$slot] = $newPath;
        AppSetting::set('site_videos', $videos);

        if ($oldPath && $oldPath !== $newPath) {
            $disk->delete($oldPath);
        }

        return back()->with('success', __('Hero video updated for :page.', ['page' => SiteMedia::videoSlots()[$page]['label'] ?? $page]));
    }

    public function deleteSiteVideo(string $page, string $slot)
    {
        abort_unless(SiteMedia::videoSlot($page, $slot), 404);

        $videos = AppSetting::get('site_videos', []);
        $videos = is_array($videos) ? $videos : [];
        $oldPath = SiteMedia::videoPath($page, $slot);
        unset($videos[$page][$slot]);
        if (empty($videos[$page])) {
            unset($videos[$page]);
        }

        AppSetting::set('site_videos', $videos);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', __('Hero video removed. The default hero image is active again.'));
    }

    public function teamImport()
    {
        $events = Event::query()->where('registration_enabled', true)->where('payments_enabled', true)->orderBy('starts_at')->get();
        $selectedEvent = $events->firstWhere('id', request()->integer('event'))
            ?? $events->firstWhere('is_default', true)
            ?? $events->first();
        $races = $selectedEvent
            ? $selectedEvent->races()->where('is_active', true)->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])->orderBy('sort_order')->get()
            : collect();

        return view('admin.team-import', compact('events', 'selectedEvent', 'races'));
    }

    public function importTeam(Request $request, TeamImportController $importer, EasyKashCashApi $easyKash)
    {
        $response = $importer->store($request, $easyKash);

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            $reference = data_get($payload, 'payment.reference') ?: data_get($payload, 'data.reference');
            if ($reference && ($response->getStatusCode() === 201 || filled(data_get($payload, 'data.checkout_url')))) {
                $request->session()->put('kfs_last_payment_reference', $reference);
            }
        }

        return $response instanceof JsonResponse
            ? $response
            : response()->json(['message' => 'The team import could not be completed.'], 500);
    }

    private function registrationQuery(Request $request)
    {
        $query = Registration::query()->with(['race.event', 'team'])->latest();
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($builder) => $builder
                ->where('registration_code', 'like', "%{$search}%")
                ->orWhere('bib_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('promo_code', 'like', "%{$search}%"));
        }
        if ($request->filled('payment_status') && $request->query('payment_status') !== 'all') {
            $query->where('payment_status', $request->query('payment_status'));
        }
        if ($request->filled('event_id')) {
            if (! Event::query()->whereKey($request->integer('event_id'))->exists()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('race', fn ($raceQuery) => $raceQuery->where('event_id', $request->integer('event_id')));
            }
        }
        if ($request->filled('race_id')) $query->where('race_id', $request->integer('race_id'));
        if ($request->filled('age_group_id') && ($ageGroup = AgeGroup::query()->find($request->integer('age_group_id')))) {
            $fallbackRaceDate = Carbon::parse(AppSetting::get('race_date', '2026-11-20'));
            $raceIdsByDate = Race::query()
                ->with('event:id,starts_at')
                ->when($request->filled('event_id'), fn ($raceQuery) => $raceQuery->where('event_id', $request->integer('event_id')))
                ->when($request->filled('race_id'), fn ($raceQuery) => $raceQuery->whereKey($request->integer('race_id')))
                ->get()
                ->groupBy(fn (Race $race) => ($race->event?->starts_at ?? $fallbackRaceDate)->toDateString())
                ->map(fn ($races) => $races->pluck('id')->all());

            $query->where(function ($ageQuery) use ($raceIdsByDate, $ageGroup, $fallbackRaceDate) {
                foreach ($raceIdsByDate->isEmpty() ? [$fallbackRaceDate->toDateString() => []] : $raceIdsByDate as $date => $raceIds) {
                    $raceDate = Carbon::parse($date);
                    $oldestBirthDate = $raceDate->copy()->subYears($ageGroup->max_age + 1)->toDateString();
                    $youngestBirthDate = $raceDate->copy()->subYears($ageGroup->min_age)->toDateString();
                    $ageQuery->orWhere(function ($groupQuery) use ($raceIds, $oldestBirthDate, $youngestBirthDate) {
                        if ($raceIds !== []) {
                            $groupQuery->whereIn('race_id', $raceIds);
                        }
                        $groupQuery->whereDate('birth_date', '>', $oldestBirthDate)
                            ->whereDate('birth_date', '<=', $youngestBirthDate);
                    });
                }
            });
        }

        return $query;
    }
}
