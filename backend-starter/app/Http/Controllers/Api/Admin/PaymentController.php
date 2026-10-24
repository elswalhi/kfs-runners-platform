<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()->with('registration')->latest();
        if ($request->filled('status') && $request->query('status') !== 'all') $query->where('status', $request->query('status'));
        $page = $query->paginate(min(max($request->integer('per_page', 50), 1), 100));
        return response()->json(['data' => $page->getCollection()->map(fn (Payment $p) => [
            'id' => $p->id,
            'reference' => 'PAY-' . $p->id,
            'registration_reference' => $p->registration?->registration_code,
            'runner' => trim(($p->registration?->first_name ?? '') . ' ' . ($p->registration?->last_name ?? '')) ?: '—',
            'provider' => $p->provider,
            'amount' => (float) $p->amount,
            'currency' => $p->currency,
            'status' => $p->status,
            'transaction_reference' => $p->transaction_reference ?: $p->gateway_reference,
            'created_at' => $p->created_at?->toISOString(),
        ])->values(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }
}
