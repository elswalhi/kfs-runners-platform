<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Race;
use App\Models\RaceKitPrice;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicRegistrationController extends Controller
{
    public function store(Request $request)
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
                'provider' => 'easycash',
                'amount' => $price->price,
                'currency' => $price->currency,
                'status' => 'pending',
            ]);
            return $registration;
        });

        // Payment initiation must be implemented from the actual EasyCash contract.
        // Do not mark this registration paid based on a browser redirect.
        return response()->json([
            'message' => 'Registration saved. Payment setup is not connected yet; no payment has been taken.',
            'data' => ['id' => $registration->id, 'reference' => $registration->registration_code, 'amount' => (float) $registration->amount, 'currency' => $registration->currency, 'payment_status' => 'pending'],
            'checkout_url' => null,
            'payment_integration_ready' => false,
        ], 201);
    }

    private function newCode(): string
    {
        do { $code = 'KFS-' . strtoupper(Str::random(8)); }
        while (Registration::query()->where('registration_code', $code)->exists());
        return $code;
    }
}
