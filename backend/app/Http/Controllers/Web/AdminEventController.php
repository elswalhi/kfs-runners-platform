<?php

namespace App\Http\Controllers\Web;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminEventController
{
    public function index()
    {
        $events = Event::query()
            ->with(['coverPhoto'])
            ->withCount('photos')
            ->orderByDesc('event_year')
            ->orderBy('sort_order')
            ->get();

        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return view('admin.events.form', ['event' => new Event(), 'isCreating' => true]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedEvent($request);
        $photos = $data['photos'] ?? [];
        unset($data['photos']);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) Event::query()->max('sort_order') + 1;

        $event = Event::query()->create($data);
        $this->storePhotos($event, $photos);

        if ($event->drive_url) {
            $this->performDriveSync($event);
        }

        return redirect()->route('admin.events.edit', $event)->with('success', __('Event created.'));
    }

    public function edit(Event $event)
    {
        $event->load(['photos' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'coverPhoto']);

        return view('admin.events.form', ['event' => $event, 'isCreating' => false]);
    }

    public function update(Request $request, Event $event)
    {
        $previousDriveUrl = $event->drive_url;
        $data = $this->validatedEvent($request, $event);
        $photos = $data['photos'] ?? [];
        unset($data['photos']);
        $data['is_published'] = $request->boolean('is_published');

        $event->update($data);
        $this->storePhotos($event, $photos);

        if ($event->drive_url && ($event->drive_url !== $previousDriveUrl || $request->boolean('sync_drive'))) {
            $synced = $this->performDriveSync($event);
            if ($synced > 0) {
                return back()->with('success', __('Event details updated and :count photos synced from Google Drive.', ['count' => $synced]));
            }
        }

        return back()->with('success', __('Event details updated.'));
    }

    public function destroy(Event $event)
    {
        foreach ($event->photos as $photo) {
            if (! $photo->isBundledAsset() && ! $photo->isExternalUrl()) {
                Storage::disk('public')->delete($photo->path);
            }
        }

        $event->delete();

        return redirect()->route('admin.events.index')->with('success', __('Event deleted.'));
    }

    public function setCover(Event $event, EventPhoto $photo)
    {
        abort_unless((int) $photo->event_id === (int) $event->id, 404);

        EventPhoto::query()->where('event_id', $event->id)->update(['is_cover' => false]);
        $photo->update(['is_cover' => true]);

        return back()->with('success', __('Event cover photo updated.'));
    }

    public function addPhotos(Request $request, Event $event)
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        $this->storePhotos($event, $data['photos']);

        return back()->with('success', __('Event photos added.'));
    }

    public function syncDrivePhotos(Request $request, Event $event)
    {
        $driveUrl = trim((string) ($request->input('drive_url') ?: $event->drive_url));
        if ($driveUrl === '') {
            return back()->withErrors(['drive_url' => __('Please provide a Google Drive folder URL.')]);
        }

        if (! filter_var($driveUrl, FILTER_VALIDATE_URL) || ! str_contains($driveUrl, 'drive.google.com')) {
            return back()->withErrors(['drive_url' => __('The URL must be a valid Google Drive link.')]);
        }

        if ($event->drive_url !== $driveUrl) {
            $event->update(['drive_url' => $driveUrl]);
        }

        $replaceExisting = $request->boolean('replace_existing');
        $synced = $this->performDriveSync($event, $replaceExisting);

        if ($synced === 0 && ! $replaceExisting) {
            // Check if photos exist on drive but were all already in DB
            $fetched = GoogleDriveService::fetchPhotosFromFolder($driveUrl);
            if (empty($fetched)) {
                return back()->withErrors(['drive_url' => __('Could not find any photos in the provided Google Drive folder. Please ensure the folder sharing setting is set to "Anyone with the link can view".')]);
            }

            return back()->with('success', __('All :count photos from Google Drive are already synced.', ['count' => count($fetched)]));
        }

        $total = $event->photos()->count();

        return back()->with('success', __('Synced :count photos from Google Drive. Event now has :total photos.', [
            'count' => $synced,
            'total' => $total,
        ]));
    }

    public function deletePhoto(Event $event, EventPhoto $photo)
    {
        abort_unless((int) $photo->event_id === (int) $event->id, 404);
        $wasCover = $photo->is_cover;
        if (! $photo->isBundledAsset() && ! $photo->isExternalUrl()) {
            Storage::disk('public')->delete($photo->path);
        }
        $photo->delete();

        if ($wasCover) {
            $event->photos()->orderBy('sort_order')->orderBy('id')->first()?->update(['is_cover' => true]);
        }

        return back()->with('success', __('Event photo removed.'));
    }

    public function photoPreview(Event $event, EventPhoto $photo)
    {
        abort_unless((int) $photo->event_id === (int) $event->id, 404);
        if ($photo->isExternalUrl()) {
            return redirect()->away($photo->adminUrl());
        }
        if ($photo->isBundledAsset()) {
            abort_unless(is_file(public_path($photo->path)), 404);

            return response()->file(public_path($photo->path), [
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $path = $photo->path;
        abort_unless(is_string($path) && str_starts_with($path, 'events/' . $event->id . '/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validatedEvent(Request $request, ?Event $event = null): array
    {
        $slug = trim((string) $request->input('slug'));
        $request->merge(['slug' => Str::slug($slug !== '' ? $slug : (string) $request->input('title'))]);

        $slugRule = Rule::unique('events', 'slug');
        if ($event) {
            $slugRule->ignore($event->id);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'title_ar' => ['nullable', 'string', 'max:180'],
            'slug' => ['required', 'alpha_dash', 'max:200', $slugRule],
            'event_year' => ['required', 'integer', 'between:1900,2200'],
            'location' => ['nullable', 'string', 'max:180'],
            'location_ar' => ['nullable', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:280'],
            'summary_ar' => ['nullable', 'string', 'max:280'],
            'description' => ['nullable', 'string', 'max:20000'],
            'description_ar' => ['nullable', 'string', 'max:20000'],
            'registration_url' => ['nullable', 'url', 'max:2048'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'drive_url' => ['nullable', 'url', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:20'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ]);
    }

    private function performDriveSync(Event $event, bool $replaceExisting = false): int
    {
        if (! $event->drive_url) {
            return 0;
        }

        $fetchedPhotos = GoogleDriveService::fetchPhotosFromFolder($event->drive_url);
        if (empty($fetchedPhotos)) {
            return 0;
        }

        if ($replaceExisting) {
            foreach ($event->photos as $p) {
                if ($p->isExternalUrl()) {
                    $p->delete();
                }
            }
            $event->load('photos');
        }

        $existingPaths = $event->photos()->pluck('path')->all();
        $existingMap = array_flip($existingPaths);

        $hasCover = $event->photos()->where('is_cover', true)->exists();
        $sortOrder = (int) $event->photos()->max('sort_order');
        $addedCount = 0;

        foreach ($fetchedPhotos as $photoData) {
            $path = $photoData['path'];
            if (isset($existingMap[$path])) {
                continue;
            }

            $sortOrder++;
            $event->photos()->create([
                'path' => $path,
                'alt' => $photoData['name'] ?? $event->title,
                'is_cover' => ! $hasCover,
                'sort_order' => $sortOrder,
            ]);
            $hasCover = true;
            $addedCount++;
        }

        return $addedCount;
    }

    private function storePhotos(Event $event, array $photos): void
    {
        $hasCover = $event->photos()->where('is_cover', true)->exists();
        $sortOrder = (int) $event->photos()->max('sort_order');

        foreach ($photos as $photo) {
            $path = $photo->store('events/' . $event->id, 'public');
            if (! $path) {
                continue;
            }

            $sortOrder++;
            $event->photos()->create([
                'path' => $path,
                'alt' => $event->title,
                'is_cover' => ! $hasCover,
                'sort_order' => $sortOrder,
            ]);
            $hasCover = true;
        }
    }
}
