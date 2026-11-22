<?php

namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\BusBooking;
use Illuminate\Http\Request;

class AdminBusBookingController
{
    public function index(Request $request)
    {
        $price = (float) AppSetting::get('zayed_bus_price', 350);
        $filters = $request->validate([
            'tab' => ['nullable', 'in:active,deleted'],
            'search' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $deleted = ($filters['tab'] ?? 'active') === 'deleted';
        $query = $deleted ? BusBooking::onlyTrashed() : BusBooking::query();
        if ($search = trim($filters['search'] ?? '')) {
            $number = preg_match('/\A(?:BUS-)?0*([0-9]+)\z/i', $search, $matches) ? $matches[1] : null;
            $query->where(function ($query) use ($search, $number) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
                if ($number !== null) {
                    $query->orWhere('id', $number);
                }
            });
        }
        if (! empty($filters['status'])) $query->where('payment_status', $filters['status']);
        if (! empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);
        $bookings = $query->latest()->paginate(25)->withQueryString();
        $paidCount = BusBooking::query()->where('payment_status', 'paid')->count();
        $deletedCount = BusBooking::onlyTrashed()->count();

        return view('admin.bus-bookings', compact('price', 'bookings', 'paidCount', 'deletedCount', 'deleted'));
    }

    public function updatePrice(Request $request)
    {
        $data = $request->validate(['price' => ['required', 'numeric', 'gt:1', 'max:1000000', 'decimal:0,2']]);
        AppSetting::set('zayed_bus_price', number_format((float) $data['price'], 2, '.', ''));

        return back()->with('success', __('Bus ticket price saved.'));
    }

    public function destroy(BusBooking $booking)
    {
        $booking->delete();

        return back()->with('success', __('Bus booking moved to deleted.'));
    }

    public function restore(int $booking)
    {
        BusBooking::onlyTrashed()->findOrFail($booking)->restore();

        return back()->with('success', __('Bus booking restored.'));
    }
}
