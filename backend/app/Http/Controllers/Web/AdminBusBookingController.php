<?php

namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\BusBooking;
use Illuminate\Http\Request;

class AdminBusBookingController
{
    public function index()
    {
        $price = (float) AppSetting::get('zayed_bus_price', 350);
        $bookings = BusBooking::query()->latest()->paginate(25);
        $paidCount = BusBooking::query()->where('payment_status', 'paid')->count();

        return view('admin.bus-bookings', compact('price', 'bookings', 'paidCount'));
    }

    public function updatePrice(Request $request)
    {
        $data = $request->validate(['price' => ['required', 'numeric', 'gt:1', 'max:1000000', 'decimal:0,2']]);
        AppSetting::set('zayed_bus_price', number_format((float) $data['price'], 2, '.', ''));

        return back()->with('success', __('Bus ticket price saved.'));
    }
}
