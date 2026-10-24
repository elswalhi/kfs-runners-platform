<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\RaceKitPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RaceController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Race::query()
            ->with('kitPrices')
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Race $race) => $this->serialize($race))]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'],
            'full_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'basic_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $race = DB::transaction(function () use ($data) {
            $race = Race::query()->create([
                'name' => trim($data['name']),
                'slug' => strtolower($data['slug']),
                'distance_label' => trim($data['distance_label']),
                'category_type' => $data['category_type'],
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach (['full' => $data['full_price'], 'basic' => $data['basic_price']] as $kitType => $price) {
                $race->kitPrices()->create([
                    'kit_type' => $kitType,
                    'price' => $price,
                    'currency' => strtoupper($data['currency'] ?? 'EGP'),
                    'is_active' => true,
                ]);
            }

            return $race;
        });

        return response()->json(['data' => $this->serialize($race->load('kitPrices')->loadCount('registrations'))], 201);
    }

    public function show(Race $race)
    {
        return response()->json(['data' => $this->serialize($race->load('kitPrices')->loadCount('registrations'))]);
    }

    public function update(Request $request, Race $race)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')->ignore($race->id)],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['is_active'] && ! $this->hasActiveKitPrices($race)) {
            return response()->json(['message' => 'Add an active Full and Basic kit price before activating this race.'], 422);
        }

        $race->update([...$data, 'name' => trim($data['name']), 'slug' => strtolower($data['slug']), 'distance_label' => trim($data['distance_label'])]);

        return response()->json(['data' => $this->serialize($race->fresh()->load('kitPrices')->loadCount('registrations'))]);
    }

    public function destroy(Race $race)
    {
        if ($race->registrations()->exists()) {
            return response()->json(['message' => 'This race has registrations. Deactivate it instead of deleting it.'], 409);
        }

        $race->delete();

        return response()->json(['message' => 'Race deleted.']);
    }

    private function serialize(Race $race): array
    {
        return [
            'id' => $race->id,
            'name' => $race->name,
            'slug' => $race->slug,
            'distance_label' => $race->distance_label,
            'category_type' => $race->category_type,
            'sort_order' => $race->sort_order,
            'is_active' => $race->is_active,
            'registrations_count' => $race->registrations_count ?? 0,
            'prices' => $race->kitPrices?->map(fn (RaceKitPrice $price) => [
                'id' => $price->id,
                'kit_type' => $price->kit_type,
                'price' => (float) $price->price,
                'currency' => $price->currency,
                'is_active' => (bool) $price->is_active,
            ])->values() ?? [],
        ];
    }

    private function hasActiveKitPrices(Race $race): bool
    {
        $activeKitTypes = $race->kitPrices()
            ->where('is_active', true)
            ->whereIn('kit_type', ['full', 'basic'])
            ->pluck('kit_type')
            ->unique();

        return $activeKitTypes->contains('full') && $activeKitTypes->contains('basic');
    }
}
