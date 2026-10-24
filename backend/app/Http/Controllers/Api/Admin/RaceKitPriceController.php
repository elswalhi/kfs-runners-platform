<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RaceKitPrice;
use Illuminate\Http\Request;

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
        $raceKitPrice->update($validated);
        return response()->json(['data' => $this->serialize($raceKitPrice->fresh('race'))]);
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
