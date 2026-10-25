<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("app_setting_{$key}", 3600, function () use ($key, $default) {
            $record = static::query()->where('key', $key)->first();
            if (! $record) {
                return $default;
            }

            $decoded = json_decode($record->value, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $record->value;
        });
    }

    public static function set(string $key, $value): void
    {
        $serialized = is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $serialized]
        );

        Cache::forget("app_setting_{$key}");
    }
}
