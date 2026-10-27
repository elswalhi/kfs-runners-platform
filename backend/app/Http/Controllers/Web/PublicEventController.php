<?php

namespace App\Http\Controllers\Web;

use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Support\Facades\Storage;

class PublicEventController
{
    public function index()
    {
        $events = Event::query()
            ->where('is_published', true)
            ->with(['coverPhoto'])
            ->withCount('photos')
            ->orderByDesc('event_year')
            ->orderBy('sort_order')
            ->get();

        return view('site.events.index', compact('events'));
    }

    public function show(Event $event)
    {
        abort_unless($event->is_published, 404);
        $event->load(['photos' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'coverPhoto']);

        return view('site.events.show', compact('event'));
    }

    public function photo(EventPhoto $photo)
    {
        abort_unless($photo->event?->is_published, 404);

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
