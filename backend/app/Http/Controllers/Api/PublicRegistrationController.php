<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\RaceKitPrice;
use App\Models\Registration;
use App\Models\Team;
use App\Services\EasyKashCashApi;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Validation\Rule;

class PublicRegistrationController extends Controller
{
    public function store(Request $request, EasyKashCashApi $easyKash)
    {
        // Handle Group / Multi-Runner Registration (EasyKash cart cycle)
        if ($request->has('runners') && is_array($request->input('runners')) && count($request->input('runners')) > 1) {
            return $this->storeGroup($request, $easyKash);
        }

        // If single runner is sent inside runners array, extract to root
        if ($request->has('runners') && is_array($request->input('runners')) && count($request->input('runners')) === 1) {
            $first = $request->input('runners.0');
            $request->merge([
                'firstName' => $first['firstName'] ?? $request->input('firstName'),
                'lastName' => $first['lastName'] ?? $request->input('lastName'),
                'email' => $first['email'] ?? $request->input('email', $request->input('buyerEmail')),
                'phone' => $first['phone'] ?? $request->input('phone', $request->input('buyerPhone')),
                'birthDate' => $first['birthDate'] ?? $request->input('birthDate'),
                'gender' => $first['gender'] ?? $request->input('gender'),
                'idNumber' => $first['idNumber'] ?? $request->input('idNumber'),
                'race' => $first['race'] ?? $request->input('race'),
                'kit' => $first['kit'] ?? $request->input('kit'),
                'shirtSize' => $first['shirtSize'] ?? $request->input('shirtSize'),
            ]);
        }

        // Fallback for buyer contact info in solo registration
        if (! $request->filled('email') && $request->filled('buyerEmail')) {
            $request->merge(['email' => $request->input('buyerEmail')]);
        }
        if (! $request->filled('phone') && $request->filled('buyerPhone')) {
            $request->merge(['phone' => $request->input('buyerPhone')]);
        }
        if (! $request->filled('firstName') && $request->filled('buyerFirstName')) {
            $request->merge(['firstName' => $request->input('buyerFirstName')]);
        }
        if (! $request->filled('lastName') && $request->filled('buyerLastName')) {
            $request->merge(['lastName' => $request->input('buyerLastName')]);
        }

        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:32'],
            'birthDate' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'prefer_not_to_say'])],
            'idNumber' => ['required', 'string', 'max:100'],
            'race' => ['required', 'string', 'exists:races,slug'],
            'kit' => ['required', Rule::in(['full', 'basic'])],
            'shirtSize' => ['nullable', Rule::requiredIf($request->input('kit') === 'full'), Rule::in(['S', 'M', 'L', 'XL', 'XXL'])],
            'teamName' => ['nullable', 'string', 'max:150'],
            'promoCode' => ['nullable', 'string', 'max:64'],
            'terms' => ['accepted'],
            'idFile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $normalizedPhone = $this->normalizeEgyptianMobile($validated['phone']);
        if ($normalizedPhone === null) {
            return response()->json([
                'message' => 'Enter a valid Egyptian mobile number, such as 01012345678 or +20 1012345678.',
            ], 422);
        }
        $validated['phone'] = $normalizedPhone;

        if ($easyKash->normalizePayerName($validated['firstName'] . $validated['lastName']) === '') {
            return response()->json([
                'message' => 'The runner name must include letters so EasyKash can create a payment link.',
            ], 422);
        }

        $race = Race::query()->where('slug', $validated['race'])->where('is_active', true)->firstOrFail();
        $price = RaceKitPrice::query()->where('race_id', $race->id)->where('kit_type', $validated['kit'])->where('is_active', true)->first();
        if (! $price) {
            return response()->json(['message' => 'This race and kit option is currently unavailable.'], 422);
        }

        $originalAmount = (float) $price->price;
        $discountAmount = 0.00;
        $finalAmount = $originalAmount;
        $promo = null;

        if (! empty($validated['promoCode'])) {
            $cleanCode = strtoupper(trim($validated['promoCode']));
            $promo = PromoCode::query()->where('code', $cleanCode)->first();

            if (! $promo || ! $promo->isUsable() || ! $promo->isValidForRace($race->id)) {
                return response()->json([
                    'message' => 'The entered promo code is invalid, expired, or not applicable to this race.',
                ], 422);
            }

            $discountAmount = $promo->calculateDiscount($originalAmount);
            $finalAmount = max(0.00, round($originalAmount - $discountAmount, 2));
        }

        $documentPath = $request->file('idFile')?->store('runner-documents', 'local');

        // Handle 100% Free Registration (discount reduces price to 0)
        if ($finalAmount == 0.00) {
            $registration = DB::transaction(function () use ($validated, $race, $price, $documentPath, $promo, $originalAmount, $discountAmount) {
                $registration = Registration::query()->create([
                    'registration_code' => $this->newCode(),
                    'race_id' => $race->id,
                    'promo_code_id' => $promo?->id,
                    'promo_code' => $promo?->code,
                    'kit_type' => $validated['kit'],
                    'first_name' => $validated['firstName'],
                    'last_name' => $validated['lastName'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'birth_date' => $validated['birthDate'],
                    'gender' => $validated['gender'],
                    'id_number' => $validated['idNumber'],
                    'shirt_size' => $validated['kit'] === 'full' ? ($validated['shirtSize'] ?? null) : null,
                    'team_name' => $validated['teamName'] ?? null,
                    'document_path' => $documentPath,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'amount' => 0.00,
                    'discount_amount' => $discountAmount,
                    'original_amount' => $originalAmount,
                    'currency' => $price->currency,
                ]);

                Payment::query()->create([
                    'registration_id' => $registration->id,
                    'provider' => 'promo_code',
                    'amount' => 0.00,
                    'currency' => $price->currency,
                    'status' => 'paid',
                    'transaction_reference' => $registration->registration_code,
                    'verified_at' => now(),
                    'payload' => [
                        'promo_code' => $promo?->code,
                        'discount_amount' => $discountAmount,
                        'free_entry' => true,
                    ],
                ]);

                if ($promo) {
                    $promo->increment('times_used');
                }

                return $registration;
            });

            return response()->json([
                'message' => 'Registration complete! 100% discount applied via promo code.',
                'data' => [
                    'id' => $registration->id,
                    'reference' => $registration->registration_code,
                    'amount' => 0.00,
                    'currency' => $registration->currency,
                    'payment_status' => 'paid',
                ],
                'payment' => [
                    'reference' => $registration->registration_code,
                    'redirect_url' => null,
                    'status' => 'paid',
                ],
                'payment_integration_ready' => true,
            ], 201);
        }

        // For paid registrations, EasyKash requires amount > 1 EGP
        if ($finalAmount <= 1.00) {
            return response()->json([
                'message' => 'After discount, payable amount must be either 0 EGP or at least 1.01 EGP for payment gateway processing.',
            ], 422);
        }

        if (! $easyKash->configured()) {
            return response()->json(['message' => 'EasyKash payment credentials are not configured on the Laravel server.'], 503);
        }
        if (! in_array((int) config('easykash.cash_expiry', 48), [3, 6, 12, 48], true)) {
            return response()->json(['message' => 'EasyKash cash checkout expiry is not configured correctly.'], 503);
        }

        $registration = DB::transaction(function () use ($validated, $race, $price, $documentPath, $promo, $originalAmount, $discountAmount, $finalAmount) {
            $registration = Registration::query()->create([
                'registration_code' => $this->newCode(),
                'race_id' => $race->id,
                'promo_code_id' => $promo?->id,
                'promo_code' => $promo?->code,
                'kit_type' => $validated['kit'],
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'birth_date' => $validated['birthDate'],
                'gender' => $validated['gender'],
                'id_number' => $validated['idNumber'],
                'shirt_size' => $validated['kit'] === 'full' ? ($validated['shirtSize'] ?? null) : null,
                'team_name' => $validated['teamName'] ?? null,
                'document_path' => $documentPath,
                'status' => 'pending',
                'payment_status' => 'pending',
                'amount' => $finalAmount,
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'currency' => $price->currency,
            ]);

            Payment::query()->create([
                'registration_id' => $registration->id,
                'provider' => 'easykash',
                'amount' => $finalAmount,
                'currency' => $price->currency,
                'status' => 'pending',
                'transaction_reference' => $registration->registration_code,
            ]);

            if ($promo) {
                $promo->increment('times_used');
            }

            return $registration;
        });

        $payment = $registration->payments()->latest()->firstOrFail();
        try {
            $gatewayPayment = $easyKash->createPaymentLink($registration);
        } catch (ConnectionException $exception) {
            Log::warning('EasyKash payment initiation connection failed.', [
                'registration_reference' => $registration->registration_code,
            ]);

            return response()->json([
                'message' => 'We could not confirm whether EasyKash created a checkout link. Please contact support with your registration reference before trying again.',
                'data' => ['reference' => $registration->registration_code, 'payment_status' => 'pending'],
            ], 504);
        } catch (RuntimeException $exception) {
            Log::warning('EasyKash rejected or returned an invalid payment response.', [
                'registration_reference' => $registration->registration_code,
            ]);

            return response()->json([
                'message' => 'We could not confirm whether EasyKash created a checkout link. Your registration is saved. Please contact support with your reference before trying again.',
                'data' => ['reference' => $registration->registration_code, 'payment_status' => 'pending'],
            ], 502);
        }

        $payment->update(['payload' => ['checkout_url_created' => true]]);

        return response()->json([
            'message' => 'Registration saved. Continue to EasyKash to choose a payment method.',
            'data' => [
                'id' => $registration->id,
                'reference' => $registration->registration_code,
                'amount' => (float) $registration->amount,
                'discount_amount' => (float) $registration->discount_amount,
                'original_amount' => (float) $registration->original_amount,
                'currency' => $registration->currency,
                'payment_status' => 'pending',
            ],
            'payment' => [
                'reference' => $registration->registration_code,
                'redirect_url' => $gatewayPayment['redirectUrl'],
                'status' => 'pending',
            ],
            'payment_integration_ready' => true,
        ], 201);
    }

    public function storeGroup(Request $request, EasyKashCashApi $easyKash)
    {
        $validated = $request->validate([
            'buyerFirstName' => ['required', 'string', 'max:100'],
            'buyerLastName' => ['required', 'string', 'max:100'],
            'buyerEmail' => ['required', 'email', 'max:190'],
            'buyerPhone' => ['required', 'string', 'max:32'],
            'runners' => ['required', 'array', 'min:2', 'max:20'],
            'runners.*.firstName' => ['required', 'string', 'max:100'],
            'runners.*.lastName' => ['required', 'string', 'max:100'],
            'runners.*.email' => ['nullable', 'email', 'max:190'],
            'runners.*.phone' => ['nullable', 'string', 'max:32'],
            'runners.*.birthDate' => ['required', 'date', 'before:today'],
            'runners.*.gender' => ['required', Rule::in(['male', 'female', 'prefer_not_to_say'])],
            'runners.*.idNumber' => ['required', 'string', 'max:100'],
            'runners.*.race' => ['required', 'string', 'exists:races,slug'],
            'runners.*.kit' => ['required', Rule::in(['full', 'basic'])],
            'runners.*.shirtSize' => ['nullable', Rule::in(['S', 'M', 'L', 'XL', 'XXL'])],
            'teamName' => ['nullable', 'string', 'max:150'],
            'promoCode' => ['nullable', 'string', 'max:64'],
            'terms' => ['accepted'],
        ]);

        $normalizedPhone = $this->normalizeEgyptianMobile($validated['buyerPhone']);
        if ($normalizedPhone === null) {
            return response()->json([
                'message' => 'Enter a valid Egyptian mobile number for primary buyer (e.g. 01012345678).',
            ], 422);
        }
        $validated['buyerPhone'] = $normalizedPhone;

        $buyerName = trim($validated['buyerFirstName'] . ' ' . $validated['buyerLastName']);
        if ($easyKash->normalizePayerName($buyerName) === '') {
            return response()->json([
                'message' => 'The buyer name must include letters so EasyKash can create a payment link.',
            ], 422);
        }

        // Calculate total amount across all runners
        $originalAmount = 0.00;
        $runnerItems = [];

        foreach ($validated['runners'] as $index => $runner) {
            $race = Race::query()->where('slug', $runner['race'])->where('is_active', true)->first();
            if (! $race) {
                return response()->json(['message' => "Race for runner #" . ($index + 1) . " is unavailable."], 422);
            }

            $price = RaceKitPrice::query()->where('race_id', $race->id)->where('kit_type', $runner['kit'])->where('is_active', true)->first();
            if (! $price) {
                return response()->json(['message' => "Package for runner #" . ($index + 1) . " is unavailable."], 422);
            }

            if ($runner['kit'] === 'full' && empty($runner['shirtSize'])) {
                return response()->json(['message' => "Please select a T-shirt size for runner #" . ($index + 1) . "."], 422);
            }

            $runnerPrice = (float) $price->price;
            $originalAmount += $runnerPrice;

            $runnerItems[] = [
                'runner' => $runner,
                'race' => $race,
                'price' => $price,
                'amount' => $runnerPrice,
            ];
        }

        $discountAmount = 0.00;
        $finalAmount = $originalAmount;
        $promo = null;

        if (! empty($validated['promoCode'])) {
            $cleanCode = strtoupper(trim($validated['promoCode']));
            $promo = PromoCode::query()->where('code', $cleanCode)->first();

            if (! $promo || ! $promo->isUsable()) {
                return response()->json([
                    'message' => 'The entered promo code is invalid or expired.',
                ], 422);
            }

            $discountAmount = $promo->calculateDiscount($originalAmount);
            $finalAmount = max(0.00, round($originalAmount - $discountAmount, 2));
        }

        // Database transaction: Create Team, Registrations, Payment
        $team = DB::transaction(function () use ($validated, $buyerName, $runnerItems, $originalAmount, $discountAmount, $finalAmount, $promo) {
            $isPaid = ($finalAmount == 0.00);

            $team = Team::query()->create([
                'name' => $validated['teamName'] ?: ($buyerName . ' Group (' . count($runnerItems) . ' Runners)'),
                'contact_name' => $buyerName,
                'email' => $validated['buyerEmail'],
                'phone' => $validated['buyerPhone'],
                'registration_code' => 'KFS-GRP-' . strtoupper(Str::random(8)),
                'status' => $isPaid ? 'confirmed' : 'pending',
            ]);

            $discountPerRunner = count($runnerItems) > 0 ? round($discountAmount / count($runnerItems), 2) : 0.00;

            foreach ($runnerItems as $item) {
                $r = $item['runner'];
                $runnerPhone = !empty($r['phone']) ? ($this->normalizeEgyptianMobile($r['phone']) ?? $validated['buyerPhone']) : $validated['buyerPhone'];
                $runnerEmail = !empty($r['email']) ? $r['email'] : $validated['buyerEmail'];
                $rDiscount = min($item['amount'], $discountPerRunner);
                $rFinalAmount = max(0.00, round($item['amount'] - $rDiscount, 2));

                Registration::query()->create([
                    'registration_code' => $this->newCode(),
                    'team_id' => $team->id,
                    'race_id' => $item['race']->id,
                    'promo_code_id' => $promo?->id,
                    'promo_code' => $promo?->code,
                    'kit_type' => $r['kit'],
                    'first_name' => $r['firstName'],
                    'last_name' => $r['lastName'],
                    'email' => $runnerEmail,
                    'phone' => $runnerPhone,
                    'birth_date' => $r['birthDate'],
                    'gender' => $r['gender'],
                    'id_number' => $r['idNumber'],
                    'shirt_size' => $r['kit'] === 'full' ? ($r['shirtSize'] ?? null) : null,
                    'team_name' => $team->name,
                    'status' => $isPaid ? 'confirmed' : 'pending',
                    'payment_status' => $isPaid ? 'paid' : 'pending',
                    'amount' => $rFinalAmount,
                    'discount_amount' => $rDiscount,
                    'original_amount' => $item['amount'],
                    'currency' => $item['price']->currency,
                ]);
            }

            Payment::query()->create([
                'team_id' => $team->id,
                'provider' => $isPaid ? 'promo_code' : 'easykash',
                'amount' => $finalAmount,
                'currency' => 'EGP',
                'status' => $isPaid ? 'paid' : 'pending',
                'transaction_reference' => $team->registration_code,
                'verified_at' => $isPaid ? now() : null,
                'payload' => [
                    'order_type' => 'group_registration',
                    'runner_count' => count($runnerItems),
                    'original_amount' => $originalAmount,
                    'discount_amount' => $discountAmount,
                    'promo_code' => $promo?->code,
                ],
            ]);

            if ($promo) {
                $promo->increment('times_used');
            }

            return $team;
        });

        // 100% Free Group Entry
        if ($finalAmount == 0.00) {
            return response()->json([
                'message' => 'Group registration complete! 100% discount applied.',
                'data' => [
                    'id' => $team->id,
                    'reference' => $team->registration_code,
                    'amount' => 0.00,
                    'currency' => 'EGP',
                    'payment_status' => 'paid',
                ],
                'payment' => [
                    'reference' => $team->registration_code,
                    'redirect_url' => null,
                    'status' => 'paid',
                ],
                'payment_integration_ready' => true,
            ], 201);
        }

        // Gateway checkout for group
        if (! $easyKash->configured()) {
            return response()->json(['message' => 'EasyKash payment credentials are not configured on the Laravel server.'], 503);
        }

        try {
            $gatewayPayment = $easyKash->createTeamPaymentLink($team, $finalAmount, 'EGP');
        } catch (\Throwable $exception) {
            Log::warning('EasyKash rejected team payment link creation.', [
                'team_reference' => $team->registration_code,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not create an EasyKash payment link. Please contact support.',
                'data' => ['reference' => $team->registration_code, 'payment_status' => 'pending'],
            ], 502);
        }

        return response()->json([
            'message' => 'Group registration saved. Continue to EasyKash to pay.',
            'data' => [
                'id' => $team->id,
                'reference' => $team->registration_code,
                'amount' => $finalAmount,
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'currency' => 'EGP',
                'payment_status' => 'pending',
                'runner_count' => count($validated['runners']),
            ],
            'payment' => [
                'reference' => $team->registration_code,
                'redirect_url' => $gatewayPayment['redirectUrl'],
                'status' => 'pending',
            ],
            'payment_integration_ready' => true,
        ], 201);
    }

    private function newCode(): string
    {
        do {
            $code = 'KFS-' . strtoupper(Str::random(8));
        } while (Registration::query()->where('registration_code', $code)->exists());
        return $code;
    }

    private function normalizeEgyptianMobile(string $phone): ?string
    {
        $phone = strtr(trim($phone), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (preg_match('/\A20(0?1[0125]\d{8})\z/', $digits, $matches)) {
            $digits = str_starts_with($matches[1], '0') ? $matches[1] : '0' . $matches[1];
        } elseif (preg_match('/\A1[0125]\d{8}\z/', $digits)) {
            $digits = '0' . $digits;
        }

        return preg_match('/\A01[0125]\d{8}\z/', $digits) ? $digits : null;
    }
}
