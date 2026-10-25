<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\Admin\TeamImportController;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\RaceKitPrice;
use App\Models\Registration;
use App\Services\EasyKashCashApi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        $raceBreakdown = Race::query()->withCount('registrations')->orderBy('sort_order')->get();
        $trend = collect(range(6, 0))->map(function (int $daysAgo) {
            $day = Carbon::today()->subDays($daysAgo);
            return ['label' => $day->format('D'), 'value' => (float) Registration::query()->where('payment_status', 'paid')->whereDate('created_at', $day)->sum('amount')];
        });

        return view('admin.dashboard', compact('total', 'paid', 'pending', 'failed', 'revenue', 'recent', 'raceBreakdown', 'trend'));
    }

    public function registrations(Request $request)
    {
        $registrations = $this->registrationQuery($request)->paginate(25)->withQueryString();
        $races = Race::query()->orderBy('sort_order')->get();

        return view('admin.registrations', compact('registrations', 'races'));
    }

    public function exportRegistrations(Request $request)
    {
        $rows = $this->registrationQuery($request)->cursor();

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['reference', 'first_name', 'last_name', 'email', 'phone', 'race', 'kit_type', 'shirt_size', 'amount', 'currency', 'registration_status', 'payment_status', 'created_at']);
            foreach ($rows as $registration) {
                $values = [$registration->registration_code, $registration->first_name, $registration->last_name, $registration->email, $registration->phone, $registration->race?->name, $registration->kit_type, $registration->shirt_size, $registration->amount, $registration->currency, $registration->status, $registration->payment_status, $registration->created_at?->toISOString()];
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

    public function registration(Registration $registration)
    {
        $registration->load(['race', 'team', 'payments', 'team.payments']);
        $payments = $registration->payments->merge($registration->team?->payments ?? collect())->unique('id')->sortByDesc('created_at');
        $idNumber = $registration->id_number;
        $maskedId = $idNumber ? '••••••' . substr((string) $idNumber, -4) : '—';

        return view('admin.registration', compact('registration', 'payments', 'maskedId'));
    }

    public function updateRegistration(Request $request, Registration $registration)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])]]);
        $registration->update($data);

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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'full_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'basic_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
        ]);
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($data, $isActive) {
            $race = Race::query()->create([
                'name' => trim($data['name']), 'slug' => strtolower($data['slug']),
                'distance_label' => trim($data['distance_label']), 'category_type' => $data['category_type'],
                'sort_order' => $data['sort_order'], 'is_active' => $isActive,
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
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')->ignore($race->id)],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        $isActive = $request->boolean('is_active');
        if ($isActive) {
            $activeKits = $race->kitPrices()->where('is_active', true)->pluck('kit_type')->unique();
            if (! $activeKits->contains('full') || ! $activeKits->contains('basic')) {
                return back()->withErrors(['is_active' => 'Add an active Full and Basic price before activating this race.'])->withInput();
            }
        }
        $race->update([...$data, 'name' => trim($data['name']), 'slug' => strtolower($data['slug']), 'distance_label' => trim($data['distance_label']), 'is_active' => $isActive]);

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
        $races = Race::query()->with('kitPrices')->orderBy('sort_order')->get();
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
        $races = Race::query()->where('is_active', true)->orderBy('sort_order')->get();

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

    public function teamImport()
    {
        $races = Race::query()->where('is_active', true)->with(['kitPrices' => fn ($query) => $query->where('is_active', true)])->orderBy('sort_order')->get();

        return view('admin.team-import', compact('races'));
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
        $query = Registration::query()->with(['race', 'team'])->latest();
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($builder) => $builder
                ->where('registration_code', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('promo_code', 'like', "%{$search}%"));
        }
        if ($request->filled('payment_status') && $request->query('payment_status') !== 'all') {
            $query->where('payment_status', $request->query('payment_status'));
        }
        if ($request->filled('race_id')) $query->where('race_id', $request->integer('race_id'));

        return $query;
    }
}
