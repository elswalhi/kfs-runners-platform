<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class RunnerAccount extends Authenticatable
{
    protected $fillable = [
        'email', 'email_verified_at', 'first_name', 'last_name', 'phone',
        'birth_date', 'gender', 'team_name', 'id_number',
    ];

    protected $hidden = [
        'email', 'email_verified_at', 'first_name', 'last_name', 'phone',
        'birth_date', 'gender', 'team_name', 'id_number', 'remember_token',
    ];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'birth_date' => 'date', 'id_number' => 'encrypted'];
    }

    public function savedIdNumber(): ?string
    {
        try {
            $number = $this->id_number;
        } catch (DecryptException $exception) {
            report($exception);

            return null;
        }

        return filled($number) ? $number : null;
    }

    public function hasCompleteProfile(): bool
    {
        return $this->email_verified_at !== null
            && filled($this->first_name)
            && filled($this->last_name)
            && filled($this->phone)
            && $this->birth_date !== null
            && in_array($this->gender, ['male', 'female'], true)
            && $this->savedIdNumber() !== null;
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
