<?php

namespace App\Http\Controllers\Web;

use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PublicEventController
{
    public function index()
    {
        $upcomingEvents = Event::query()
            ->where('is_published', true)
            ->where('is_current', true)
            ->with(['coverPhoto'])
            ->withCount(['photos', 'races'])
            ->orderBy('starts_at')
            ->get();

        $events = Event::query()
            ->where('is_published', true)
            ->where('is_current', false)
            ->with(['coverPhoto'])
            ->withCount('photos')
            ->orderByDesc('event_year')
            ->orderBy('sort_order')
            ->get();

        return view('site.events.index', compact('events', 'upcomingEvents'));
    }

    public function show(Event $event)
    {
        abort_unless($event->is_published, 404);
        $event->load([
            'photos' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'coverPhoto',
            'races' => fn ($query) => $query->where('is_active', true)->with(['kitPrices' => fn ($prices) => $prices->where('is_active', true)])->orderBy('sort_order'),
        ]);
        $raceDateTime = $event->starts_at
            ? Carbon::parse($event->starts_at->format('Y-m-d H:i:s'), 'Africa/Cairo')->toIso8601String()
            : null;

        return view('site.events.show', compact('event', 'raceDateTime'));
    }

    public function photo(EventPhoto $photo)
    {
        abort_unless($photo->event?->is_published, 404);

        if ($photo->isExternalUrl()) {
            return redirect()->away($photo->publicUrl());
        }

        return $this->streamUploadedPhoto($photo);
    }

    private function streamUploadedPhoto(EventPhoto $photo)
    {
        $path = $photo->path;
        $expectedPrefix = 'events/' . $photo->event_id . '/';
        abort_unless(is_string($path) && str_starts_with($path, $expectedPrefix) && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        $absolutePath = Storage::disk('public')->path($path);
        abort_unless(is_file($absolutePath), 404);

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
