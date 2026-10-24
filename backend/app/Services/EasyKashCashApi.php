<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $phone = preg_replace('/\D+/', '', $registration->phone);
        $response = Http::asJson()
            ->acceptJson()
            ->timeout(20)
            ->post(config('easykash.cash_api_url'), [
                'payerEmail' => $registration->email,
                'payerMobile' => $phone,
                'payerName' => $this->normalizePayerName($registration->first_name . $registration->last_name),
                'amount' => (float) $registration->amount,
                'expiryDuration' => $expiryDuration,
                'apiKey' => config('easykash.api_key'),
                'VoucherData' => 'KFS Runners ' . $registration->registration_code,
                'type' => 'in',
            ]);

        $responseData = $response->json();
        if (! $response->successful() || ! is_array($responseData)) {
            $errorMessage = $this->providerErrorMessage($responseData);
            Log::warning('EasyKash cash API returned an unsuccessful response.', [
                'http_status' => $response->status(),
                'response_fields' => $this->responseFieldNames($responseData),
                'provider_message' => $this->safeDiagnosticMessage($errorMessage),
            ]);
            throw new RuntimeException('EasyKash did not create a cash payment reference.');
        }

        $data = is_array($responseData['data'] ?? null) ? $responseData['data'] : $responseData;
        $missing = array_values(array_filter(
            ['voucher', 'expiryDate', 'provider', 'easykashRef'],
            static fn (string $field): bool => ! isset($data[$field]) || ! is_scalar($data[$field]) || (string) $data[$field] === '',
        ));
        if ($missing !== []) {
            $errorMessage = $this->providerErrorMessage($responseData);
            Log::warning('EasyKash cash API returned an incomplete response.', [
                'http_status' => $response->status(),
                'response_fields' => array_keys($data),
                'missing_fields' => $missing,
                'provider_message' => $this->safeDiagnosticMessage($errorMessage),
            ]);
            throw new RuntimeException('EasyKash returned an incomplete cash payment response.');
        }

        return [
            'voucher' => (string) $data['voucher'],
            'expiryDate' => (string) $data['expiryDate'],
            'provider' => (string) $data['provider'],
            'easykashRef' => (string) $data['easykashRef'],
        ];
    }

    public function normalizePayerName(string $name): string
    {
        return preg_replace('/[^\p{L}]/u', '', $name) ?? '';
    }

    private function providerErrorMessage(mixed $responseData): mixed
    {
        if (! is_array($responseData)) {
            return null;
        }

        $candidate = array_is_list($responseData) ? ($responseData[0] ?? null) : $responseData;
        if (is_array($candidate)) {
            return $candidate['message'] ?? $candidate['error'] ?? $candidate['error_description'] ?? null;
        }

        return is_string($candidate) ? $candidate : null;
    }

    private function responseFieldNames(mixed $responseData): array
    {
        if (! is_array($responseData)) {
            return [];
        }

        $candidate = array_is_list($responseData) ? ($responseData[0] ?? null) : $responseData;

        return is_array($candidate) ? array_keys($candidate) : array_keys($responseData);
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

    private function safeDiagnosticMessage(mixed $message): ?string
    {
        if (! is_string($message)) {
            return null;
        }

        $message = mb_substr(trim($message), 0, 300);
        foreach ([config('easykash.api_key'), config('easykash.hmac_secret')] as $secret) {
            if (is_string($secret) && $secret !== '') {
                $message = str_replace($secret, '[redacted]', $message);
            }
        }

        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $message);
        $message = preg_replace('/(?<!\w)\+?\d[\d\s().-]{7,}\d(?!\w)/', '[redacted-number]', $message);

        return $message;
    }
}
