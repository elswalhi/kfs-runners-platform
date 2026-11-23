<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusBooking extends Model
{
    use SoftDeletes;

    protected $fillable = ['reference', 'name', 'email', 'phone', 'amount', 'payment_status', 'receipt_path'];
    protected $hidden = ['receipt_path'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function getBookingNumberAttribute(): string
    {
        return 'BUS-'.str_pad((string) $this->getKey(), 5, '0', STR_PAD_LEFT);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
