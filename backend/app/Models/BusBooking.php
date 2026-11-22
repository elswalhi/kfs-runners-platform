<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BusBooking extends Model
{
    protected $fillable = ['reference', 'name', 'email', 'phone', 'amount', 'payment_status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
