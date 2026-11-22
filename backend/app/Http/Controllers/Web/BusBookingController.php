<?php

namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\BusBooking;
use App\Models\Payment;
use App\Services\EasyKashCashApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BusBookingController
{
    public function create()
    {
        $price = (float) AppSetting::get('zayed_bus_price', 350);

        return response()->view('site.bus-booking', compact('price'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, EasyKashCashApi $easyKash)
    {
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => preg_replace('/[\s().-]+/', '', $request->input('phone'))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:32', 'regex:/\A(?:01[0125][0-9]{8}|\+201[0125][0-9]{8})\z/'],
            'displayed_price' => ['required', 'numeric', 'gt:1'],
        ]);
        if ($easyKash->normalizePayerName($data['name']) === '') {
            return back()->withInput()->withErrors(['name' => __('Please enter a name containing letters.')]);
        }
        $price = (float) AppSetting::get('zayed_bus_price', 350);
        if (round((float) $data['displayed_price'], 2) !== round($price, 2)) {
            return back()->withInput()->withErrors(['price' => __('The ticket price has changed. Please review the new price before continuing.')]);
        }
        if ($price <= 1 || ! $easyKash->configured() || ! in_array((int) config('easykash.cash_expiry', 48), [3, 6, 12, 48], true)) {
            return back()->withInput()->withErrors(['payment' => __('Online payment is temporarily unavailable. Please contact the team.')]);
        }

        unset($data['displayed_price']);
        $booking = DB::transaction(function () use ($data, $price) {
            $booking = BusBooking::create([
                ...$data,
                'name' => trim($data['name']),
                'reference' => 'BUS-'.strtoupper(Str::random(24)),
                'amount' => $price,
                'payment_status' => 'pending',
            ]);
            Payment::create([
                'bus_booking_id' => $booking->id,
                'provider' => 'easykash',
                'amount' => $price,
                'currency' => 'EGP',
                'status' => 'pending',
                'transaction_reference' => $booking->reference,
            ]);

            return $booking;
        });

        try {
            $checkout = $easyKash->createBusPaymentLink($booking);
            return redirect()->away($checkout['redirectUrl']);
        } catch (Throwable $exception) {
            Log::warning('Bus payment link could not be confirmed.', [
                'reference' => $booking->reference,
                'exception' => $exception::class,
            ]);
            return redirect()->route('bus.return', ['customerReference' => $booking->reference])
                ->with('checkout_uncertain', true);
        }
    }

    public function paymentReturn(Request $request)
    {
        $reference = $request->query('customerReference');
        $booking = is_string($reference)
            ? BusBooking::query()->where('reference', $reference)->first()
            : null;
        $voucher = $request->query('voucher');
        $voucher = is_string($voucher) && preg_match('/\A[a-zA-Z0-9-]{1,100}\z/', $voucher) ? $voucher : null;

        return response()->view('site.bus-return', compact('booking', 'voucher'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function status(string $reference)
    {
        $booking = BusBooking::query()->where('reference', $reference)->firstOrFail();
        return response()->json(['status' => $booking->payment_status])
            ->header('Cache-Control', 'private, no-store');
    }
}
