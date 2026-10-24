<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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
            'terms' => ['accepted'],
            'idFile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $race = Race::query()->where('slug', $validated['race'])->where('is_active', true)->firstOrFail();
        $price = RaceKitPrice::query()->where('race_id', $race->id)->where('kit_type', $validated['kit'])->where('is_active', true)->first();
        if (!$price) return response()->json(['message' => 'This race and kit option is currently unavailable.'], 422);
        if (! $easyKash->configured()) {
            return response()->json(['message' => 'EasyKash payment credentials are not configured on the Laravel server.'], 503);
        }
        if (! in_array((int) config('easykash.expiry_duration', 48), [3, 6, 12, 48], true)) {
            return response()->json(['message' => 'EasyKash cash voucher expiry is not configured correctly.'], 503);
        }

        $documentPath = $request->file('idFile')?->store('runner-documents', 'local');
        $registration = DB::transaction(function () use ($validated, $race, $price, $documentPath) {
            $registration = Registration::query()->create([
                'registration_code' => $this->newCode(),
                'race_id' => $race->id,
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
                'amount' => $price->price,
                'currency' => $price->currency,
            ]);
            Payment::query()->create([
                'registration_id' => $registration->id,
                'provider' => 'easykash',
                'amount' => $price->price,
                'currency' => $price->currency,
                'status' => 'pending',
                'transaction_reference' => $registration->registration_code,
            ]);
            return $registration;
        });

        $payment = $registration->payments()->latest()->firstOrFail();
        try {
            $gatewayPayment = $easyKash->createCashPayment($registration);
        } catch (ConnectionException $exception) {
            Log::warning('EasyKash payment initiation connection failed.', [
                'registration_reference' => $registration->registration_code,
            ]);

            return response()->json([
                'message' => 'We could not confirm whether EasyKash created a voucher. Please contact support with your registration reference before trying again.',
                'data' => ['reference' => $registration->registration_code, 'payment_status' => 'pending'],
            ], 504);
        } catch (RuntimeException $exception) {
            // A rejected, malformed, or intermediary response does not prove that
            // EasyKash did not create a voucher. Keep the payment reconcilable by callback.
            Log::warning('EasyKash rejected or returned an invalid payment response.', [
                'registration_reference' => $registration->registration_code,
            ]);

            return response()->json([
                'message' => 'We could not confirm whether EasyKash created a cash voucher. Your registration is saved. Please contact support with your reference before trying again.',
                'data' => ['reference' => $registration->registration_code, 'payment_status' => 'pending'],
            ], 502);
        }

        $payment->update([
            'gateway_reference' => $gatewayPayment['easykashRef'],
            'payload' => [
                'voucher' => $gatewayPayment['voucher'],
                'expiryDate' => $gatewayPayment['expiryDate'],
                'provider' => $gatewayPayment['provider'],
            ],
        ]);

        return response()->json([
            'message' => 'Registration saved. Use the cash voucher below to complete payment.',
            'data' => [
                'id' => $registration->id,
                'reference' => $registration->registration_code,
                'amount' => (float) $registration->amount,
                'currency' => $registration->currency,
                'payment_status' => 'pending',
            ],
            'payment' => [
                'reference' => $registration->registration_code,
                'provider' => $gatewayPayment['provider'],
                'voucher' => $gatewayPayment['voucher'],
                'expiry_date' => $gatewayPayment['expiryDate'],
                'status' => 'pending',
                'amount' => (float) $registration->amount,
                'currency' => $registration->currency,
            ],
            'payment_integration_ready' => true,
        ], 201);
    }

    private function newCode(): string
    {
        do { $code = 'KFS-' . strtoupper(Str::random(8)); }
        while (Registration::query()->where('registration_code', $code)->exists());
        return $code;
    }
}
