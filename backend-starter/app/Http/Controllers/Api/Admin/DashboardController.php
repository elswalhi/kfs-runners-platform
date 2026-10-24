<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Race;
use App\Models\Registration;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $paidQuery = Registration::query()->where('payment_status', 'paid');
        $paidCount = (clone $paidQuery)->count();
        $revenue = (clone $paidQuery)->sum('amount');
        $total = Registration::query()->count();
        $pending = Registration::query()->where('payment_status', 'pending')->count();
        $failed = Registration::query()->where('payment_status', 'failed')->count();

        $raceBreakdown = Race::query()->withCount('registrations')->get()->map(fn (Race $race) => [
            'label' => in_array($race->category_type, ['family', 'para'], true) ? $race->name : $race->distance_label,
            'count' => $race->registrations_count,
        ])->values();

        $trend = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'label' => $date->format('D'),
                'value' => (float) Registration::query()->where('payment_status', 'paid')->whereDate('created_at', $date)->sum('amount'),
            ];
        })->values();

        $recent = Registration::query()->with('race')->latest()->limit(6)->get()->map(fn (Registration $r) => $this->serializeRegistration($r));
        return response()->json(['data' => [
            'total_registrations' => $total,
            'paid_registrations' => $paidCount,
            'pending_payments' => $pending,
            'failed_payments' => $failed,
            'total_revenue' => (float) $revenue,
            'conversion_rate' => $total ? round(($paidCount / $total) * 100, 1) : 0,
            'recent_registrations' => $recent,
            'race_breakdown' => $raceBreakdown,
            'revenue_trend' => $trend,
        ]]);
    }

    private function serializeRegistration(Registration $r): array
    {
        return [
            'id' => $r->id, 'reference' => $r->registration_code,
            'first_name' => $r->first_name, 'last_name' => $r->last_name,
            'email' => $r->email, 'phone' => $r->phone,
            'race' => $r->race ? ['name' => $r->race->name, 'distance_label' => $r->race->distance_label] : null,
            'kit_type' => $r->kit_type, 'shirt_size' => $r->shirt_size,
            'amount' => (float) $r->amount, 'currency' => $r->currency,
            'status' => $r->status, 'payment_status' => $r->payment_status,
            'created_at' => $r->created_at?->toISOString(),
        ];
    }
}
