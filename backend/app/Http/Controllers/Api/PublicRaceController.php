<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\Event;
use Illuminate\Http\Request;

class PublicRaceController extends Controller
{
    public function index(Request $request)
    {
        $event = $request->filled('event')
            ? Event::query()->where('slug', $request->query('event'))->first()
            : Event::query()->where('is_default', true)->first();
        $raceQuery = $event ? $event->races() : Race::query()->whereNull('event_id');
        $races = $raceQuery->where('is_active', true)->with(['kitPrices' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get()->map(fn (Race $race) => [
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
