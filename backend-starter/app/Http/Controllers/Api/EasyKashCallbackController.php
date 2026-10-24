<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Registration;
use App\Services\EasyKashCashApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EasyKashCallbackController extends Controller
{
    public function __invoke(Request $request, EasyKashCashApi $easyKash)
    {
        if (! filled(config('easykash.hmac_secret'))) {
            return response()->json(['message' => 'EasyKash callback verification is not configured.'], 503);
        }

        $payload = $request->all();
        if (! $easyKash->verifiesCallback($payload)) {
            return response()->json(['message' => 'Invalid EasyKash callback signature.'], 401);
        }

        $reference = (string) $payload['easykashRef'];
        $customerReference = (string) $payload['customerReference'];
        $amount = is_numeric($payload['Amount']) ? number_format((float) $payload['Amount'], 2, '.', '') : null;
        if ($amount === null) {
            return response()->json(['message' => 'Invalid callback amount.'], 422);
        }

        $payment = Payment::query()
            ->where('provider', 'easykash')
            ->where(function ($query) use ($reference, $customerReference) {
                $query->where('gateway_reference', $reference)
                    ->orWhere('transaction_reference', $customerReference);
            })
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment reference was not found.'], 404);
        }

        if (number_format((float) $payment->amount, 2, '.', '') !== $amount) {
            return response()->json(['message' => 'Callback amount does not match the registration.'], 422);
        }

        $providerStatus = strtoupper(trim((string) $payload['status']));
        $paymentStatus = match ($providerStatus) {
            'PAID' => 'paid',
            'FAILED', 'CANCELED', 'CANCELLED', 'EXPIRED' => 'failed',
            'REFUNDED' => 'refunded',
            default => 'pending',
        };

        DB::transaction(function () use ($payment, $paymentStatus, $providerStatus, $payload) {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment) {
                return;
            }

            // A delayed non-paid callback must never undo a confirmed payment.
            if ($lockedPayment->status === 'paid' && $paymentStatus !== 'paid') {
                return;
            }

            $lockedPayment->update([
                'status' => $paymentStatus,
                'gateway_reference' => (string) $payload['easykashRef'],
                'verified_at' => $paymentStatus === 'paid' ? now() : $lockedPayment->verified_at,
                'payload' => array_merge($lockedPayment->payload ?? [], [
                    'provider_status' => $providerStatus,
                    'payment_method' => (string) $payload['PaymentMethod'],
                    'product_code' => (string) $payload['ProductCode'],
                    'voucher' => isset($payload['voucher']) && is_scalar($payload['voucher']) ? (string) $payload['voucher'] : null,
                ]),
            ]);

            if ($lockedPayment->registration_id) {
                Registration::query()->whereKey($lockedPayment->registration_id)->update([
                    'payment_status' => $paymentStatus,
                    'updated_at' => now(),
                ]);
            }
        });

        Log::info('EasyKash callback processed.', [
            'easykash_reference' => $reference,
            'provider_status' => $providerStatus,
        ]);

        return response()->json(['ok' => true]);
    }
}
