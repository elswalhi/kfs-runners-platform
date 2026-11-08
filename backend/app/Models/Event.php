<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'title_ar', 'slug', 'event_year', 'location', 'location_ar',
        'summary', 'summary_ar', 'description', 'description_ar',
        'registration_url', 'source_url', 'drive_url', 'is_published', 'sort_order',
        'starts_at', 'is_current', 'registration_enabled', 'payments_enabled',
        'countdown_enabled', 'is_default', 'qr_fields',
    ];

    protected function casts(): array
    {
        return [
            'event_year' => 'integer',
            'is_published' => 'boolean',
            'is_current' => 'boolean',
            'registration_enabled' => 'boolean',
            'payments_enabled' => 'boolean',
            'countdown_enabled' => 'boolean',
            'is_default' => 'boolean',
            'qr_fields' => 'array',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class);
    }

    public function coverPhoto(): HasOne
    {
        return $this->hasOne(EventPhoto::class)->where('is_cover', true);
    }

    public function races(): HasMany
    {
        return $this->hasMany(Race::class);
    }

    public function registrations(): HasManyThrough
    {
        return $this->hasManyThrough(Registration::class, Race::class);
    }

    public function translated(string $field): ?string
    {
        $translated = app()->getLocale() === 'ar' ? $this->getAttribute($field . '_ar') : null;

        return filled($translated) ? $translated : $this->getAttribute($field);
    }
}
