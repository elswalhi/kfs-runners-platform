<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class RunnerAccount extends Authenticatable
{
    protected $fillable = [
        'email', 'email_verified_at', 'first_name', 'last_name', 'phone',
        'birth_date', 'gender', 'team_name',
    ];

    protected $hidden = [
        'email', 'email_verified_at', 'first_name', 'last_name', 'phone',
        'birth_date', 'gender', 'team_name', 'remember_token',
    ];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'birth_date' => 'date'];
    }

    public function getAuthPassword(): ?string
    {
        return null;
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
