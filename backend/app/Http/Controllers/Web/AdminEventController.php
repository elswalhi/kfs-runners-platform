<?php

namespace App\Http\Controllers\Web;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\Race;
use App\Models\AppSetting;
use App\Services\QrCodeService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminEventController
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->with(['coverPhoto'])
            ->withCount(['photos', 'races', 'registrations'])
            ->orderByDesc('is_current')
            ->orderByDesc('starts_at')
            ->orderByDesc('event_year')
            ->orderBy('sort_order')
            ->get();
        $currentEvents = $events->where('is_current', true)->values();
        $selectedEvent = $events->firstWhere('id', $request->integer('event'))
            ?? $currentEvents->firstWhere('is_default', true)
            ?? $currentEvents->first()
            ?? $events->first();
        $selectedEvent?->load(['races' => fn ($query) => $query->with('kitPrices')->withCount('registrations')->orderBy('sort_order')->orderBy('id')]);
        $currentRegistrationCount = $currentEvents->sum('registrations_count');
        $nextEvent = $currentEvents->filter(fn (Event $event) => $event->starts_at && $event->starts_at->isFuture())->sortBy('starts_at')->first();

        return view('admin.events.index', compact('events', 'currentEvents', 'selectedEvent', 'currentRegistrationCount', 'nextEvent'));
    }

    public function configure(Event $event)
    {
        $event->load(['coverPhoto', 'races' => fn ($query) => $query->with('kitPrices')->withCount('registrations')->orderBy('sort_order')->orderBy('id')]);
        $qrFields = QrCodeService::runnerFields();
        $selectedQrFields = $event->qr_fields ?? AppSetting::get('runner_qr_fields', QrCodeService::defaultRunnerFields());
        if (! is_array($selectedQrFields)) {
            $selectedQrFields = QrCodeService::defaultRunnerFields();
        }
        $selectedQrFields = array_values(array_intersect(array_keys($qrFields), $selectedQrFields));

        return view('admin.events.configure', compact('event', 'qrFields', 'selectedQrFields'));
    }

    public function updateSettings(Request $request, Event $event)
    {
        $qrFields = QrCodeService::runnerFields();
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'title_ar' => ['sometimes', 'nullable', 'string', 'max:180'],
            'event_year' => ['sometimes', 'required', 'integer', 'between:1900,2200'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'location' => ['sometimes', 'nullable', 'string', 'max:180'],
            'location_ar' => ['sometimes', 'nullable', 'string', 'max:180'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:280'],
            'summary_ar' => ['sometimes', 'nullable', 'string', 'max:280'],
            'registration_enabled' => ['sometimes', 'boolean'],
            'payments_enabled' => ['sometimes', 'boolean'],
            'countdown_enabled' => ['sometimes', 'boolean'],
            'is_current' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'qr_fields' => ['sometimes', 'required', 'array', 'min:1'],
            'qr_fields.*' => ['required', 'string', Rule::in(array_keys($qrFields))],
        ]);

        $isCurrent = array_key_exists('is_current', $data) ? $request->boolean('is_current') : $event->is_current;
        $registrationEnabled = array_key_exists('registration_enabled', $data) ? $request->boolean('registration_enabled') : $event->registration_enabled;
        if (array_key_exists('is_default', $data) && $request->boolean('is_default') && (! $isCurrent || ! $registrationEnabled)) {
            return back()->withErrors(['is_default' => __('The default event must be current and accept registrations.')])->withInput();
        }

        DB::transaction(function () use ($event, $request, $data) {
            if (array_key_exists('is_default', $data) && $request->boolean('is_default')) {
                Event::query()->where('is_default', true)->where('id', '!=', $event->id)->update(['is_default' => false]);
            }

            $updates = collect($data)->except(['qr_fields'])->all();
            foreach (['registration_enabled', 'payments_enabled', 'countdown_enabled', 'is_current', 'is_published', 'is_default'] as $booleanField) {
                if (array_key_exists($booleanField, $data)) {
                    $updates[$booleanField] = $request->boolean($booleanField);
                }
            }
            if (array_key_exists('qr_fields', $data)) {
                $updates['qr_fields'] = array_values(array_unique($data['qr_fields']));
            }

            $event->update($updates);
        });

        return back()->with('success', __('Event settings saved.'));
    }

    public function storeRace(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')->where('event_id', $event->id)],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'bib_number_start' => ['required', 'integer', 'min:1', 'max:999999999'],
            'full_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'basic_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
        ]);

        DB::transaction(function () use ($event, $data, $request) {
            $race = $event->races()->create([
                'name' => trim($data['name']),
                'slug' => strtolower($data['slug']),
                'distance_label' => trim($data['distance_label']),
                'category_type' => $data['category_type'],
                'sort_order' => $data['sort_order'] ?? ((int) $event->races()->max('sort_order') + 1),
                'is_active' => $request->boolean('is_active', true),
                'bib_number_start' => $data['bib_number_start'],
                'next_bib_number' => $data['bib_number_start'],
            ]);

            foreach (['full' => $data['full_price'], 'basic' => $data['basic_price']] as $kit => $price) {
                $race->kitPrices()->create(['kit_type' => $kit, 'price' => $price, 'currency' => 'EGP', 'is_active' => true]);
            }
        });

        return back()->with('success', __('Race distance and prices added to this event.'));
    }

    public function updateRace(Request $request, Event $event, Race $race)
    {
        abort_unless((int) $race->event_id === (int) $event->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('races', 'slug')->where('event_id', $event->id)->ignore($race->id)],
            'distance_label' => ['required', 'string', 'max:40'],
            'category_type' => ['required', Rule::in(['individual', 'family', 'para'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'bib_number_start' => ['required', 'integer', 'min:1', 'max:999999999'],
            'full_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
            'basic_price' => ['required', 'numeric', 'gt:1', 'max:1000000'],
        ]);

        DB::transaction(function () use ($race, $data, $request) {
            $activeKitTypes = $race->kitPrices()->where('is_active', true)->pluck('kit_type')->unique();
            if ($request->boolean('is_active') && (! $activeKitTypes->contains('full') || ! $activeKitTypes->contains('basic'))) {
                abort(422, __('An active race needs active Full and Basic prices.'));
            }

            $nextBibNumber = max((int) $race->next_bib_number, (int) $data['bib_number_start'], (int) $race->registrations()->max('bib_number') + 1);
            $race->update([
                ...collect($data)->except(['full_price', 'basic_price'])->all(),
                'name' => trim($data['name']),
                'slug' => strtolower($data['slug']),
                'distance_label' => trim($data['distance_label']),
                'is_active' => $request->boolean('is_active'),
                'next_bib_number' => $nextBibNumber,
            ]);

            foreach (['full' => $data['full_price'], 'basic' => $data['basic_price']] as $kit => $price) {
                $race->kitPrices()->updateOrCreate(['kit_type' => $kit], ['price' => $price, 'currency' => 'EGP', 'is_active' => true]);
            }
        });

        return back()->with('success', __('Event race settings saved.'));
    }

    public function destroyRace(Event $event, Race $race)
    {
        abort_unless((int) $race->event_id === (int) $event->id, 404);
        if ($race->registrations()->exists()) {
            return back()->withErrors(['race' => __('This distance has registrations. Deactivate it instead of deleting it.')]);
        }

        $race->delete();

        return back()->with('success', __('Race distance removed from this event.'));
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
        $data['is_current'] = $request->boolean('is_current');
        $data['registration_enabled'] = $request->boolean('registration_enabled');
        $data['payments_enabled'] = $request->boolean('payments_enabled', true);
        $data['countdown_enabled'] = $request->boolean('countdown_enabled', true);
        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = (int) Event::query()->max('sort_order') + 1;

        if ($data['is_default'] && (! $data['is_current'] || ! $data['registration_enabled'])) {
            return back()->withErrors(['is_default' => __('The default event must be current and accept registrations.')])->withInput();
        }

        $event = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                Event::query()->where('is_default', true)->update(['is_default' => false]);
            }

            return Event::query()->create($data);
        });
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
        $data['is_current'] = $request->boolean('is_current');
        $data['registration_enabled'] = $request->boolean('registration_enabled');
        $data['payments_enabled'] = $request->boolean('payments_enabled', true);
        $data['countdown_enabled'] = $request->boolean('countdown_enabled', true);
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default'] && (! $data['is_current'] || ! $data['registration_enabled'])) {
            return back()->withErrors(['is_default' => __('The default event must be current and accept registrations.')])->withInput();
        }

        DB::transaction(function () use ($event, $data) {
            if ($data['is_default']) {
                Event::query()->where('is_default', true)->where('id', '!=', $event->id)->update(['is_default' => false]);
            }
            $event->update($data);
        });
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
        if ($event->registrations()->exists()) {
            return back()->withErrors(['event' => __('This event has registrations. Keep the event and disable registration instead of deleting it.')]);
        }

        $event->races()->get()->each->delete();

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
            'starts_at' => ['nullable', 'date'],
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
            'is_current' => ['nullable', 'boolean'],
            'registration_enabled' => ['nullable', 'boolean'],
            'payments_enabled' => ['nullable', 'boolean'],
            'countdown_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
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
