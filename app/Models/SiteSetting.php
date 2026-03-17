<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting_{$key}");
    }

    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget("site_setting_{$key}");
    }

    /** Returns full public URL for the custom logo, or null to use default. */
    public static function logoUrl(): ?string
    {
        $path = static::get('logo');

        return $path ? Storage::url($path) : null;
    }

    /** Returns full public URL for the custom background, or null to use default. */
    public static function backgroundUrl(): ?string
    {
        $path = static::get('background');

        return $path ? Storage::url($path) : null;
    }
}
