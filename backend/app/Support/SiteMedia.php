<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Race;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SiteMedia
{
    public static function pages(?Collection $races = null): array
    {
        $pages = self::fixedPages();

        $races ??= Race::query()->orderBy('sort_order')->get();
        foreach ($races as $race) {
            $pages['event']['slots']['race-card-' . $race->slug] = [
                'label' => 'Race: ' . $race->name,
                'hint' => 'Optional image used only on this race card. Otherwise the Default race card image is shown.',
                'fallback' => 'images/kfs-runners-hero.png',
            ];
        }

        return $pages;
    }

    public static function slot(string $page, string $slot): ?array
    {
        $fixed = self::fixedPages();

        if (isset($fixed[$page]['slots'][$slot])) {
            return $fixed[$page]['slots'][$slot];
        }

        if ($page === 'event' && str_starts_with($slot, 'race-card-')) {
            $slug = substr($slot, strlen('race-card-'));
            $race = Race::query()->where('slug', $slug)->first(['name']);
            if ($race) {
                return [
                    'label' => 'Race: ' . $race->name,
                    'hint' => 'Optional image used only on this race card. Otherwise the Default race card image is shown.',
                    'fallback' => 'images/kfs-runners-hero.png',
                ];
            }
        }

        return null;
    }

    public static function path(string $page, string $slot): ?string
    {
        $media = AppSetting::get('site_media', []);
        $path = is_array($media) ? data_get($media, $page . '.' . $slot) : null;
        $expectedPrefix = 'site-media/' . $page . '/';

        if (! is_string($path) || ! str_starts_with($path, $expectedPrefix) || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    public static function url(string $page, string $slot, string $fallback): string
    {
        if (! self::slot($page, $slot)) {
            return $fallback;
        }

        $path = self::path($page, $slot);
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return $fallback;
        }

        return route('site-media.show', [
            'page' => $page,
            'slot' => $slot,
            'v' => substr(sha1($path), 0, 12),
        ]);
    }

    private static function fixedPages(): array
    {
        return [
            'home' => ['label' => 'Home', 'description' => 'Team introduction, sports, groups, and homepage feature cards.', 'slots' => [
                'hero' => ['label' => 'Home hero', 'hint' => 'Main team banner behind the headline.', 'fallback' => 'images/kfs-runners-hero.png'],
                'about' => ['label' => 'Team story photo', 'hint' => 'Photo beside the About KFS Runners text.', 'fallback' => 'images/kfs-runners-hero.png'],
                'running' => ['label' => 'Running card', 'hint' => 'Photo for Running and fitness training.', 'fallback' => 'images/kfs-runners-hero.png'],
                'skating' => ['label' => 'Skating card', 'hint' => 'Photo for the Skating service.', 'fallback' => 'images/kfs-skating-group.png'],
                'cycling' => ['label' => 'Cycling card', 'hint' => 'Photo for the Cycling service.', 'fallback' => 'images/kfs-cycling-group.png'],
                'event-promo' => ['label' => 'Event preview card', 'hint' => 'Photo for the Event link near the bottom of Home.', 'fallback' => 'images/kfs-runners-hero.png'],
                'register-promo' => ['label' => 'Registration preview card', 'hint' => 'Photo for the Marathon Registration link near the bottom of Home.', 'fallback' => 'images/kfs-runners-hero.png'],
            ]],
            'event' => ['label' => 'Event', 'description' => 'Event banner and race card photos.', 'slots' => [
                'hero' => ['label' => 'Event hero', 'hint' => 'Main image at the top of the Event page.', 'fallback' => 'images/kfs-runners-hero.png'],
                'race-default' => ['label' => 'Default race card', 'hint' => 'Used for race cards without their own image.', 'fallback' => 'images/kfs-runners-hero.png'],
            ]],
            'register' => ['label' => 'Marathon Registration', 'description' => 'Registration banner and order summary photo.', 'slots' => [
                'hero' => ['label' => 'Registration hero', 'hint' => 'Banner behind the registration form introduction.', 'fallback' => 'images/kfs-runners-hero.png'],
                'order-card' => ['label' => 'Order summary photo', 'hint' => 'Photo in the selected-race summary beside the form.', 'fallback' => 'images/kfs-runners-hero.png'],
            ]],
        ];
    }
}
