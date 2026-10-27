<?php

namespace App\Http\Controllers\Web;

use App\Support\SiteMedia;
use Illuminate\Support\Facades\Storage;

class PublicSiteMediaController
{
    public function show(string $page, string $slot)
    {
        abort_unless(SiteMedia::slot($page, $slot), 404);

        $path = SiteMedia::path($page, $slot);
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $absolutePath = Storage::disk('public')->path($path);
        abort_unless(is_file($absolutePath), 404);

        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function showVideo(string $page, string $slot)
    {
        abort_unless(SiteMedia::videoSlot($page, $slot), 404);

        $path = SiteMedia::videoPath($page, $slot);
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $absolutePath = Storage::disk('public')->path($path);
        abort_unless(is_file($absolutePath), 404);

        return response()->file($absolutePath, [
            'Content-Type' => SiteMedia::videoMimeType($path),
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
