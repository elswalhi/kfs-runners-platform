<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RunnerEmailChallenge extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'session_key';

    protected $keyType = 'string';

    protected $hidden = ['session_key', 'email', 'code_hash'];

    protected function casts(): array
    {
        return [
            'return_to_registration' => 'boolean',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'resend_at' => 'datetime',
        ];
    }
}
