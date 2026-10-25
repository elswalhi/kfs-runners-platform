<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Team;
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

    public function createPaymentLink(Registration $registration): array
    {
        $phone = preg_replace('/\D+/', '', $registration->phone);
        return $this->createCheckoutLink([
            'amount' => (float) $registration->amount,
            'currency' => $registration->currency,
            'name' => trim($registration->first_name . ' ' . $registration->last_name),
            'email' => $registration->email,
            'mobile' => $phone,
            'customerReference' => $registration->registration_code,
        ]);
    }

    public function createTeamPaymentLink(Team $team, float $amount, string $currency): array
    {
        return $this->createCheckoutLink([
            'amount' => $amount,
            'currency' => $currency,
            'name' => $team->contact_name,
            'email' => $team->email,
            'mobile' => preg_replace('/\D+/', '', $team->phone),
            'customerReference' => $team->registration_code,
        ]);
    }

    public function inquire(string $customerReference): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::withHeaders(['authorization' => (string) config('easykash.api_key')])
                ->asJson()
                ->acceptJson()
                ->timeout(10)
                ->post('https://back.easykash.net/api/cash-api/inquire', [
                    'customerReference' => $customerReference,
                ]);

            if ($response->successful() && is_array($response->json())) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('EasyKash inquire call failed.', [
                'reference' => $customerReference,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function createCheckoutLink(array $payer): array
    {
        $cashExpiry = (int) config('easykash.cash_expiry', 48);
        if (! in_array($cashExpiry, [3, 6, 12, 48], true)) {
            throw new RuntimeException('EasyKash cash expiry must be 3, 6, 12, or 48 hours.');
        }

        $response = Http::withHeaders(['authorization' => (string) config('easykash.api_key')])
            ->asJson()
            ->acceptJson()
            ->timeout(20)
            ->post(config('easykash.pay_api_url'), [
                ...$payer,
                // Omitting paymentOptions asks EasyKash to show every method enabled for this account.
                'cashExpiry' => $cashExpiry,
                'redirectUrl' => config('easykash.redirect_url'),
            ]);

        $responseData = $response->json();
        if (! $response->successful() || ! is_array($responseData)) {
            $errorMessage = $this->providerErrorMessage($responseData);
            Log::warning('EasyKash pay API returned an unsuccessful response.', [
                'http_status' => $response->status(),
                'response_fields' => $this->responseFieldNames($responseData),
                'provider_message' => $this->safeDiagnosticMessage($errorMessage),
            ]);
            throw new RuntimeException('EasyKash did not create a payment link.');
        }

        $data = is_array($responseData['data'] ?? null) ? $responseData['data'] : $responseData;
        $redirectUrl = $data['redirectUrl'] ?? null;
        $redirectHost = is_string($redirectUrl) ? parse_url($redirectUrl, PHP_URL_HOST) : null;
        if (! is_string($redirectUrl) || ! in_array($redirectHost, ['easykash.net', 'www.easykash.net'], true) || parse_url($redirectUrl, PHP_URL_SCHEME) !== 'https') {
            $errorMessage = $this->providerErrorMessage($responseData);
            Log::warning('EasyKash pay API returned an invalid redirect response.', [
                'http_status' => $response->status(),
                'response_fields' => array_keys($data),
                'provider_message' => $this->safeDiagnosticMessage($errorMessage),
            ]);
            throw new RuntimeException('EasyKash returned an invalid payment link.');
        }

        return ['redirectUrl' => $redirectUrl];
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
