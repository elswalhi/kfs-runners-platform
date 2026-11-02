<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'min_age', 'max_age', 'sort_order'];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function containsAge(?int $age): bool
    {
        return $age !== null && $age >= $this->min_age && $age <= $this->max_age;
    }
}
