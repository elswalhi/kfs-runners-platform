<?php

namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\BusBooking;
use App\Models\Payment;
use App\Mail\BusWalletBookingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    public function store(Request $request)
    {
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => preg_replace('/[\s().-]+/', '', $request->input('phone'))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'max:32', 'regex:/\A(?:01[0125][0-9]{8}|\+201[0125][0-9]{8})\z/'],
            'displayed_price' => ['required', 'numeric', 'gt:1'],
            'receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
        ]);
        if (! preg_match('/\p{L}/u', $data['name'])) {
            return back()->withInput()->withErrors(['name' => __('Please enter a name containing letters.')]);
        }
        $price = (float) AppSetting::get('zayed_bus_price', 350);
        if (round((float) $data['displayed_price'], 2) !== round($price, 2)) {
            return back()->withInput()->withErrors(['price' => __('The ticket price has changed. Please review the new price before continuing.')]);
        }
        unset($data['displayed_price'], $data['receipt']);
        $receiptPath = $request->file('receipt')->store('bus-receipts', 'local');
        if (! is_string($receiptPath)) {
            return back()->withInput($request->except('receipt'))
                ->withErrors(['receipt' => __('Could not save the transfer image. Please try again.')]);
        }

        try {
            $booking = DB::transaction(function () use ($data, $price, $receiptPath) {
                $booking = BusBooking::create([
                    ...$data,
                    'name' => trim($data['name']),
                    'reference' => 'BUS-'.strtoupper(Str::random(24)),
                    'amount' => $price,
                    'payment_status' => 'pending',
                    'receipt_path' => $receiptPath,
                ]);
                Payment::create([
                    'bus_booking_id' => $booking->id,
                    'provider' => 'wallet',
                    'amount' => $price,
                    'currency' => 'EGP',
                    'status' => 'pending',
                    'transaction_reference' => $booking->reference,
                ]);

                return $booking;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($receiptPath);
            throw $exception;
        }

        try {
            if (config('mail.default') === 'log') {
                throw new \RuntimeException('Email transport is not configured.');
            }
            Mail::to('swalhiitsme@gmail.com')->send(new BusWalletBookingNotification($booking));
        } catch (Throwable $exception) {
            Log::warning('Bus wallet booking notification could not be sent.', [
                'reference' => $booking->reference,
                'exception' => $exception::class,
            ]);
        }

        return redirect()->route('bus.return', ['customerReference' => $booking->reference]);
    }

    public function paymentReturn(Request $request)
    {
        $reference = $request->query('customerReference');
        $booking = is_string($reference)
            ? BusBooking::withTrashed()->where('reference', $reference)->first()
            : null;
        $voucher = $request->query('voucher');
        $voucher = is_string($voucher) && preg_match('/\A[a-zA-Z0-9-]{1,100}\z/', $voucher) ? $voucher : null;

        $wallet = $booking?->payment?->provider === 'wallet';
        return response()->view('site.bus-return', compact('booking', 'voucher', 'wallet'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function status(string $reference)
    {
        $booking = BusBooking::withTrashed()->where('reference', $reference)->firstOrFail();
        return response()->json(['status' => $booking->payment_status])
            ->header('Cache-Control', 'private, no-store');
    }
}
