<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'distance_label', 'category_type', 'sort_order', 'is_active', 'bib_number_start', 'next_bib_number'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'bib_number_start' => 'integer',
            'next_bib_number' => 'integer',
        ];
    }

    public static function suggestedBibNumberStart(?string $distanceLabel, ?string $categoryType = null): int
    {
        preg_match('/\d+/', (string) $distanceLabel, $matches);
        $distance = (int) ($matches[0] ?? 0);

        if ($distance === 2 && $categoryType === 'para') {
            return 250;
        }

        return $distance >= 10
            ? $distance * 101
            : max(100, $distance * 100);
    }

    public function kitPrices(): HasMany
    {
        return $this->hasMany(RaceKitPrice::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }
}
