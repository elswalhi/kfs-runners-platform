<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;

class PublicRaceController extends Controller
{
    public function index()
    {
        $races = Race::query()->where('is_active', true)->with(['kitPrices' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get()->map(fn (Race $race) => [
            'id' => $race->id,
            'slug' => $race->slug,
            'name' => $race->name,
            'distance_label' => $race->distance_label,
            'category_type' => $race->category_type,
            'prices' => $race->kitPrices->map(fn ($p) => ['kit_type' => $p->kit_type, 'price' => (float) $p->price, 'currency' => $p->currency]),
        ]);
        return response()->json(['data' => $races]);
    }
}
