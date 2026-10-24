<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'registration_code', 'status'];
    public function registrations(): HasMany { return $this->hasMany(Registration::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
