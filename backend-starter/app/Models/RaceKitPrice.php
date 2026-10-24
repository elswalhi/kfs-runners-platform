<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceKitPrice extends Model
{
    use HasFactory;
    protected $fillable = ['race_id', 'kit_type', 'price', 'currency', 'is_active'];
    protected function casts(): array { return ['price' => 'decimal:2', 'is_active' => 'boolean']; }
    public function race(): BelongsTo { return $this->belongsTo(Race::class); }
}
