<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EasyKashCashApi
{
    public function configured(): bool
    {
        return filled(config('easykash.api_key'))
            && filled(config('easykash.hmac_secret'));
    }

    public function createCashPayment(Registration $registration): array
    {
        $expiryDuration = (int) config('easykash.expiry_duration', 48);
        if (! in_array($expiryDuration, [3, 6, 12, 48], true)) {
            throw new RuntimeException('EasyKash cash expiry must be 3, 6, 12, or 48 hours.');
        }

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(20)
            ->post(config('easykash.cash_api_url'), [
                'payerEmail' => $registration->email,
                'payerMobile' => $registration->phone,
                'payerName' => trim($registration->first_name . ' ' . $registration->last_name),
                'amount' => (float) $registration->amount,
                'expiryDuration' => $expiryDuration,
                'apiKey' => config('easykash.api_key'),
                'VoucherData' => 'KFS Runners ' . $registration->registration_code,
                'type' => 'in',
            ]);

        $data = $response->json();
        if (! $response->successful() || ! is_array($data)) {
            throw new RuntimeException('EasyKash did not create a cash payment reference.');
        }

        foreach (['voucher', 'expiryDate', 'provider', 'easykashRef'] as $required) {
            if (! isset($data[$required]) || ! is_scalar($data[$required]) || (string) $data[$required] === '') {
                throw new RuntimeException('EasyKash returned an incomplete cash payment response.');
            }
        }

        return [
            'voucher' => (string) $data['voucher'],
            'expiryDate' => (string) $data['expiryDate'],
            'provider' => (string) $data['provider'],
            'easykashRef' => (string) $data['easykashRef'],
        ];
    }

    public function verifiesCallback(array $payload): bool
    {
        $keys = [
            'ProductCode',
            'Amount',
            'ProductType',
            'PaymentMethod',
            'status',
            'easykashRef',
            'customerReference',
        ];

        foreach ([...$keys, 'signatureHash'] as $key) {
            if (! isset($payload[$key]) || ! is_scalar($payload[$key])) {
                return false;
            }
        }

        $signature = (string) $payload['signatureHash'];
        if (! preg_match('/\A[a-f0-9]{128}\z/i', $signature)) {
            return false;
        }

        $message = implode('', array_map(static fn (string $key): string => (string) $payload[$key], $keys));
        $expected = hash_hmac('sha512', $message, (string) config('easykash.hmac_secret'));

        return hash_equals($expected, strtolower($signature));
    }
}
