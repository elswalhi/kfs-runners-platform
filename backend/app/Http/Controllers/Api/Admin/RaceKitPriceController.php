<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RaceKitPrice;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RaceKitPriceController extends Controller
{
    public function index()
    {
        return response()->json(['data' => RaceKitPrice::query()->with('race')->orderBy('race_id')->orderBy('kit_type')->get()->map(fn (RaceKitPrice $p) => $this->serialize($p))]);
    }

    public function update(Request $request, RaceKitPrice $raceKitPrice)
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $willBeActive = $validated['is_active'] ?? $raceKitPrice->is_active;
        if ($raceKitPrice->race->is_active && $raceKitPrice->is_active && ! $willBeActive && $this->isOnlyActivePriceOfType($raceKitPrice)) {
            return response()->json(['message' => 'An active race must keep both Full and Basic kit prices active.'], 422);
        }
        $raceKitPrice->update($validated);
        return response()->json(['data' => $this->serialize($raceKitPrice->fresh('race'))]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'race_id' => ['required', 'integer', 'exists:races,id'],
            'kit_type' => ['required', Rule::in(['full', 'basic'])],
            'price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (RaceKitPrice::query()->where('race_id', $data['race_id'])->where('kit_type', $data['kit_type'])->exists()) {
            return response()->json(['message' => 'This race already has a price for that kit. Edit the existing price instead.'], 409);
        }
        $race = Race::query()->findOrFail($data['race_id']);
        if ($race->is_active && ! ($data['is_active'] ?? true)) {
            return response()->json(['message' => 'Prices added to an active race must be active. Deactivate the race first to add an inactive price.'], 422);
        }
        $price = $race->kitPrices()->create([
            'kit_type' => $data['kit_type'],
            'price' => $data['price'],
            'currency' => strtoupper($data['currency'] ?? 'EGP'),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => $this->serialize($price->load('race'))], 201);
    }

    public function destroy(RaceKitPrice $raceKitPrice)
    {
        if ($raceKitPrice->race->is_active && $raceKitPrice->is_active && $this->isOnlyActivePriceOfType($raceKitPrice)) {
            return response()->json(['message' => 'An active race must keep both Full and Basic kit prices active.'], 409);
        }
        if ($raceKitPrice->race->registrations()->where('kit_type', $raceKitPrice->kit_type)->exists()) {
            return response()->json(['message' => 'This price is used by registrations. Deactivate it instead of deleting it.'], 409);
        }

        $raceKitPrice->delete();

        return response()->json(['message' => 'Race-kit price deleted.']);
    }

    private function isOnlyActivePriceOfType(RaceKitPrice $price): bool
    {
        return ! $price->race->kitPrices()->where('is_active', true)->where('kit_type', $price->kit_type)->where('id', '!=', $price->id)->exists();
    }

    private function serialize(RaceKitPrice $p): array
    {
        return [
            'id' => $p->id, 'race_id' => $p->race_id,
            'race_name' => $p->race?->name,
            'distance_label' => $p->race?->distance_label,
            'kit_type' => $p->kit_type, 'price' => (float) $p->price,
            'currency' => $p->currency, 'is_active' => (bool) $p->is_active,
        ];
    }
}
