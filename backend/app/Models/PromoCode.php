<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_uses',
        'times_used',
        'valid_from',
        'valid_until',
        'race_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        if ($this->valid_from && now()->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && now()->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    public function isValidForRace(?int $raceId): bool
    {
        if ($this->race_id === null) {
            return true;
        }

        return $this->race_id === $raceId;
    }

    public function calculateDiscount(float $baseAmount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = round(($baseAmount * (float) $this->discount_value) / 100, 2);
        } else {
            $discount = (float) $this->discount_value;
        }

        return min($baseAmount, max(0.00, $discount));
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return 'Exhausted';
        }

        if ($this->valid_until && now()->gt($this->valid_until)) {
            return 'Expired';
        }

        if ($this->valid_from && now()->lt($this->valid_from)) {
            return 'Scheduled';
        }

        return 'Active';
    }
}
