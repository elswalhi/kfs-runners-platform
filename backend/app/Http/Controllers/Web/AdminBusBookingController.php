<?php

namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\BusBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminBusBookingController
{
    public function index(Request $request)
    {
        $price = (float) AppSetting::get('zayed_bus_price', 350);
        $filters = $this->validatedFilters($request);
        $deleted = ($filters['tab'] ?? 'active') === 'deleted';
        $bookings = $this->filteredBookings($filters)->with('payment')->latest()->paginate(25)->withQueryString();
        $paidCount = BusBooking::query()->where('payment_status', 'paid')->count();
        $deletedCount = BusBooking::onlyTrashed()->count();
        $totalAmount = BusBooking::query()->sum('amount');
        $paidAmount = BusBooking::query()->where('payment_status', 'paid')->sum('amount');
        $pendingAmount = BusBooking::query()->where('payment_status', 'pending')->sum('amount');

        return view('admin.bus-bookings', compact('price', 'bookings', 'paidCount', 'deletedCount', 'deleted', 'totalAmount', 'paidAmount', 'pendingAmount'));
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $bookings = $this->filteredBookings($filters)->latest()->cursor();
        $filename = ($filters['tab'] ?? 'active') === 'deleted'
            ? 'deleted-bus-bookings.csv'
            : 'bus-bookings.csv';

        return response()->streamDownload(function () use ($bookings) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Booking number', 'Name', 'Email', 'Phone', 'Amount (EGP)', 'Payment status', 'Booking date']);
            foreach ($bookings as $booking) {
                $row = [
                    $booking->booking_number,
                    $booking->name,
                    $booking->email,
                    "'".$booking->phone,
                    $booking->amount,
                    $booking->payment_status,
                    $booking->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                ];
                fputcsv($output, array_map(
                    static fn ($value) => is_string($value) && preg_match('/\A[\s\x00-\x1F]*[=+\-@]/u', $value)
                        ? "'".$value
                        : $value,
                    $row
                ));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'tab' => ['nullable', 'in:active,deleted'],
            'search' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }

    private function filteredBookings(array $filters): Builder
    {
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
        return $query;
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

    public function updatePayment(Request $request, BusBooking $booking)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['paid', 'pending'])]]);
        DB::transaction(function () use ($booking, $data) {
            $payment = $booking->payment()->lockForUpdate()->firstOrFail();
            abort_unless($payment->provider === 'wallet', 403);
            $booking = BusBooking::query()->lockForUpdate()->findOrFail($booking->id);
            $booking->update(['payment_status' => $data['status']]);
            $payment->update([
                'status' => $data['status'],
                'verified_at' => $data['status'] === 'paid' ? now() : null,
            ]);
        });

        return back()->with('success', __('Bus payment status updated.'));
    }

    public function restore(int $booking)
    {
        BusBooking::onlyTrashed()->findOrFail($booking)->restore();

        return back()->with('success', __('Bus booking restored.'));
    }

    public function receipt(int $booking)
    {
        $record = BusBooking::withTrashed()->findOrFail($booking);
        $path = $record->receipt_path;
        abort_unless(is_string($path) && preg_match('/\Abus-receipts\/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)\z/i', $path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $record->booking_number.'-receipt.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
