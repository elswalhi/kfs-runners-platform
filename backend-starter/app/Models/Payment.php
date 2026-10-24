<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = ['registration_id', 'team_id', 'provider', 'amount', 'currency', 'status', 'transaction_reference', 'gateway_reference', 'payload', 'verified_at'];
    protected $hidden = ['payload'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'payload' => 'array', 'verified_at' => 'datetime']; }
    public function registration(): BelongsTo { return $this->belongsTo(Registration::class); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
}
