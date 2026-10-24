<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Race;
use App\Models\RaceKitPrice;
use App\Models\Registration;
use App\Models\Team;
use App\Services\EasyKashCashApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class TeamImportController extends Controller
{
    public function store(Request $request, EasyKashCashApi $easyKash)
    {
        $data = $request->validate([
            'request_key' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/i'],
            'team' => ['required', 'array'],
            'team.name' => ['required', 'string', 'max:150'],
            'team.contact_name' => ['required', 'string', 'max:150'],
            'team.email' => ['required', 'email', 'max:190'],
            'team.phone' => ['required', 'string', 'max:32'],
            'terms' => ['accepted'],
            'members' => ['required', 'array', 'min:2', 'max:200'],
            'members.*.row_number' => ['sometimes', 'integer', 'min:2'],
            'members.*.first_name' => ['required', 'string', 'max:100'],
            'members.*.last_name' => ['required', 'string', 'max:100'],
            'members.*.email' => ['required', 'email', 'max:190'],
            'members.*.phone' => ['required', 'string', 'max:32'],
            'members.*.birth_date' => ['required', 'date', 'before:today'],
            'members.*.gender' => ['required', Rule::in(['male', 'female', 'prefer_not_to_say'])],
            'members.*.id_number' => ['required', 'string', 'max:100'],
            'members.*.race_slug' => ['required', 'string', 'exists:races,slug'],
            'members.*.kit_type' => ['required', Rule::in(['full', 'basic'])],
            'members.*.shirt_size' => ['nullable', Rule::in(['S', 'M', 'L', 'XL', 'XXL'])],
        ]);

        $teamPhone = $this->normalizeEgyptianMobile($data['team']['phone']);
        if ($teamPhone === null) {
            return response()->json(['message' => 'Enter a valid Egyptian mobile number for the team contact.'], 422);
        }
        if ($easyKash->normalizePayerName($data['team']['contact_name']) === '') {
            return response()->json(['message' => 'The team contact name must include letters so EasyKash can create a payment link.'], 422);
        }

        $prepared = [];
        $rowErrors = [];
        $totalCents = 0;
        $currencies = [];

        foreach ($data['members'] as $index => $member) {
            $rowNumber = $member['row_number'] ?? ($index + 2);
            if ($member['kit_type'] === 'full' && empty($member['shirt_size'])) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => 'Shirt size is required for a Full Kit.'];
                continue;
            }
            if ($member['kit_type'] === 'basic') {
                $member['shirt_size'] = null;
            }

            $memberPhone = $this->normalizeEgyptianMobile($member['phone']);
            if ($memberPhone === null) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => 'Enter a valid Egyptian mobile number.'];
                continue;
            }

            $race = Race::query()->where('slug', $member['race_slug'])->where('is_active', true)->first();
            $price = $race
                ? RaceKitPrice::query()->where('race_id', $race->id)->where('kit_type', $member['kit_type'])->where('is_active', true)->first()
                : null;
            if (! $race || ! $price) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => 'The selected race and kit are not currently available.'];
                continue;
            }
            if ((float) $price->price <= 1) {
                $rowErrors[] = ['row' => $rowNumber, 'message' => 'The selected race-kit price must be greater than 1 EGP.'];
                continue;
            }

            $currencies[$price->currency] = true;
            $totalCents += (int) round((float) $price->price * 100);
            $prepared[] = [
                'member' => [...$member, 'phone' => $memberPhone],
                'race' => $race,
                'price' => $price,
            ];
        }

        if ($rowErrors) {
            return response()->json([
                'message' => 'Fix the highlighted spreadsheet rows and import again. No team or registrations were saved.',
                'row_errors' => $rowErrors,
            ], 422);
        }
        if (count($currencies) !== 1) {
            return response()->json(['message' => 'All team members must use the same payment currency.'], 422);
        }
        if (! $easyKash->configured()) {
            return response()->json(['message' => 'EasyKash payment credentials are not configured on the Laravel server. No team registrations were saved.'], 503);
        }
        if (! in_array((int) config('easykash.cash_expiry', 48), [3, 6, 12, 48], true)) {
            return response()->json(['message' => 'EasyKash cash checkout expiry is not configured correctly. No team registrations were saved.'], 503);
        }
        if ($totalCents <= 100 || $totalCents > 9999999999) {
            return response()->json(['message' => 'The team total is outside the supported EasyKash payment range.'], 422);
        }

        $registrationCode = 'KFS-T-' . strtoupper(substr(strtolower($data['request_key']), 0, 24));
        $existingTeam = Team::query()->withCount('registrations')->with('payments')->where('registration_code', $registrationCode)->first();
        if ($existingTeam) {
            $existingPayment = $existingTeam->payments->sortByDesc('created_at')->first();
            return response()->json([
                'message' => 'This roster was already imported. Do not submit it again; contact the race team with the existing reference.',
                'data' => [
                    'reference' => $registrationCode,
                    'team_name' => $existingTeam->name,
                    'member_count' => $existingTeam->registrations_count,
                    'amount' => $existingPayment ? (float) $existingPayment->amount : 0,
                    'currency' => $existingPayment?->currency,
                    'payment_status' => $existingPayment?->status ?? 'pending',
                    'checkout_url' => $existingPayment ? ($existingPayment->payload['checkout_url'] ?? null) : null,
                ],
            ], 409);
        }

        $team = DB::transaction(function () use ($data, $prepared, $registrationCode, $teamPhone, $totalCents, $currencies) {
            $team = Team::query()->create([
                'name' => trim($data['team']['name']),
                'contact_name' => trim($data['team']['contact_name']),
                'email' => strtolower(trim($data['team']['email'])),
                'phone' => $teamPhone,
                'registration_code' => $registrationCode,
                'status' => 'pending',
            ]);

            foreach ($prepared as $entry) {
                $member = $entry['member'];
                $registration = Registration::query()->create([
                    'registration_code' => $this->newRegistrationCode(),
                    'race_id' => $entry['race']->id,
                    'team_id' => $team->id,
                    'kit_type' => $member['kit_type'],
                    'first_name' => trim($member['first_name']),
                    'last_name' => trim($member['last_name']),
                    'email' => strtolower(trim($member['email'])),
                    'phone' => $member['phone'],
                    'birth_date' => $member['birth_date'],
                    'gender' => $member['gender'],
                    'id_number' => trim($member['id_number']),
                    'shirt_size' => $member['shirt_size'] ?? null,
                    'team_name' => $team->name,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'amount' => $entry['price']->price,
                    'currency' => $entry['price']->currency,
                ]);
            }

            Payment::query()->create([
                'team_id' => $team->id,
                'provider' => 'easykash',
                'amount' => number_format($totalCents / 100, 2, '.', ''),
                'currency' => array_key_first($currencies),
                'status' => 'pending',
                'transaction_reference' => $team->registration_code,
            ]);

            return $team;
        });

        $payment = $team->payments()->latest()->firstOrFail();
        try {
            $gatewayPayment = $easyKash->createTeamPaymentLink($team, (float) $payment->amount, $payment->currency);
        } catch (ConnectionException) {
            Log::warning('EasyKash team payment initiation connection failed.', ['team_reference' => $team->registration_code]);
            return response()->json([
                'message' => 'The team registration was saved, but EasyKash did not confirm a checkout link. Do not re-import this roster. Contact support with the reference before trying again.',
                'data' => ['reference' => $team->registration_code, 'team_name' => $team->name, 'payment_status' => 'pending', 'member_count' => count($prepared), 'amount' => (float) $payment->amount, 'currency' => $payment->currency],
            ], 504);
        } catch (RuntimeException) {
            Log::warning('EasyKash team checkout response could not be confirmed.', ['team_reference' => $team->registration_code]);
            return response()->json([
                'message' => 'The team registration was saved, but EasyKash did not confirm a checkout link. Do not re-import this roster. Contact support with the reference before trying again.',
                'data' => ['reference' => $team->registration_code, 'team_name' => $team->name, 'payment_status' => 'pending', 'member_count' => count($prepared), 'amount' => (float) $payment->amount, 'currency' => $payment->currency],
            ], 502);
        }

        $payment->update(['payload' => ['checkout_url_created' => true, 'checkout_url' => $gatewayPayment['redirectUrl']]]);

        return response()->json([
            'message' => 'Team roster saved. Share the EasyKash link with the team contact to complete one payment for all members.',
            'data' => [
                'reference' => $team->registration_code,
                'team_name' => $team->name,
                'member_count' => count($prepared),
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'payment_status' => 'pending',
            ],
            'payment' => ['reference' => $team->registration_code, 'redirect_url' => $gatewayPayment['redirectUrl'], 'status' => 'pending'],
        ], 201);
    }

    private function newRegistrationCode(): string
    {
        do {
            $code = 'KFS-' . strtoupper(Str::random(8));
        } while (Registration::query()->where('registration_code', $code)->exists());

        return $code;
    }

    private function normalizeEgyptianMobile(string $phone): ?string
    {
        $phone = strtr(trim($phone), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
        if (preg_match('/\A20(0?1[0125]\d{8})\z/', $digits, $matches)) $digits = str_starts_with($matches[1], '0') ? $matches[1] : '0' . $matches[1];
        elseif (preg_match('/\A1[0125]\d{8}\z/', $digits)) $digits = '0' . $digits;

        return preg_match('/\A01[0125]\d{8}\z/', $digits) ? $digits : null;
    }
}
