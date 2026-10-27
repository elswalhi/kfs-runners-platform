<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = Registration::query()->with('race')->latest();
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('registration_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('payment_status') && $request->query('payment_status') !== 'all') {
            $query->where('payment_status', $request->query('payment_status'));
        }
        if ($request->filled('status')) $query->where('status', $request->query('status'));
        if ($request->filled('race_id')) $query->where('race_id', $request->integer('race_id'));

        $page = $query->paginate(min(max($request->integer('per_page', 20), 1), 100));
        return response()->json([
            'data' => $page->getCollection()->map(fn (Registration $r) => $this->serialize($r))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
        ]);
    }

    public function show(Registration $registration)
    {
        $registration->load(['race', 'payments', 'team.payments']);
        $data = $this->serialize($registration);
        $data['id_number_masked'] = $registration->maskedIdNumber();
        $data['document_available'] = (bool) $registration->document_path;
        $data['payments'] = $registration->payments->merge($registration->team?->payments ?? collect())->unique('id')->map(fn ($p) => [
            'id' => $p->id, 'provider' => $p->provider, 'amount' => (float) $p->amount,
            'currency' => $p->currency, 'status' => $p->status,
            'transaction_reference' => $p->transaction_reference,
            'payment_scope' => $p->team_id ? 'Team checkout' : 'Individual checkout',
            'created_at' => $p->created_at?->toISOString(),
        ]);
        return response()->json(['data' => $data]);
    }

    public function document(Registration $registration)
    {
        abort_unless($registration->document_path && Storage::disk('local')->exists($registration->document_path), 404);
        return Storage::disk('local')->download($registration->document_path, 'runner-document-' . $registration->registration_code);
    }

    public function update(Request $request, Registration $registration)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])]]);
        $registration->update($data);
        return response()->json(['data' => $this->serialize($registration->fresh('race'))]);
    }

    private function serialize(Registration $r): array
    {
        return [
            'id' => $r->id, 'reference' => $r->registration_code,
            'first_name' => $r->first_name, 'last_name' => $r->last_name,
            'email' => $r->email, 'phone' => $r->phone,
            'birth_date' => $r->birth_date?->toDateString(), 'gender' => $r->gender,
            'race' => $r->race ? ['id' => $r->race->id, 'name' => $r->race->name, 'distance_label' => $r->race->distance_label] : null,
            'team' => $r->team ? ['id' => $r->team->id, 'name' => $r->team->name] : null,
            'kit_type' => $r->kit_type, 'shirt_size' => $r->shirt_size,
            'amount' => (float) $r->amount, 'currency' => $r->currency,
            'status' => $r->status, 'payment_status' => $r->payment_status,
            'created_at' => $r->created_at?->toISOString(),
        ];
    }
}
