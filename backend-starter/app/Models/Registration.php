<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    use HasFactory;
    protected $fillable = [
        'registration_code', 'race_id', 'team_id', 'kit_type', 'first_name', 'last_name', 'email', 'phone',
        'birth_date', 'gender', 'id_number', 'shirt_size', 'team_name', 'document_path', 'status',
        'payment_status', 'amount', 'currency', 'notes',
    ];
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'id_number' => 'encrypted',
            'amount' => 'decimal:2',
        ];
    }
    public function race(): BelongsTo { return $this->belongsTo(Race::class); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
