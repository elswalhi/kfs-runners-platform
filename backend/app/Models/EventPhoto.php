<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'path', 'alt', 'is_cover', 'sort_order'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean', 'sort_order' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function publicUrl(): string
    {
        if ($this->isExternalUrl()) {
            return $this->path;
        }

        if (is_string($this->path) && str_starts_with($this->path, 'drive:')) {
            $fileId = substr($this->path, 6);
            return "https://lh3.googleusercontent.com/d/{$fileId}";
        }

        if ($this->isBundledAsset()) {
            return asset($this->path);
        }

        return route('event-photos.show', $this);
    }

    public function adminUrl(): string
    {
        if ($this->isExternalUrl()) {
            return $this->path;
        }

        if (is_string($this->path) && str_starts_with($this->path, 'drive:')) {
            $fileId = substr($this->path, 6);
            return "https://lh3.googleusercontent.com/d/{$fileId}";
        }

        if ($this->isBundledAsset()) {
            return asset($this->path);
        }

        return route('admin.events.photos.preview', ['event' => $this->event_id, 'photo' => $this->id]);
    }

    public function isExternalUrl(): bool
    {
        return is_string($this->path) && (str_starts_with($this->path, 'https://') || str_starts_with($this->path, 'http://'));
    }

    public function isBundledAsset(): bool
    {
        return is_string($this->path)
            && str_starts_with($this->path, 'images/events/')
            && ! str_contains($this->path, '..');
    }
}
