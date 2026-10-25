<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\RaceKitPrice;
use App\Models\Registration;
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
