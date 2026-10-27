<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'title_ar', 'slug', 'event_year', 'location', 'location_ar',
        'summary', 'summary_ar', 'description', 'description_ar',
        'registration_url', 'source_url', 'drive_url', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['event_year' => 'integer', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class);
    }

    public function coverPhoto(): HasOne
    {
        return $this->hasOne(EventPhoto::class)->where('is_cover', true);
    }

    public function translated(string $field): ?string
    {
        $translated = app()->getLocale() === 'ar' ? $this->getAttribute($field . '_ar') : null;

        return filled($translated) ? $translated : $this->getAttribute($field);
    }
}
