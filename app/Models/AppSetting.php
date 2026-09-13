<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One row = one named setting (e.g. "OTP_EXPIRY_MIN" => "10"), seeded in
 * database/seeders/DatabaseSeeder.php. Storing these in the database instead
 * of hardcoding them in PHP means an admin could change them later (in a
 * future phase) without touching code.
 */
class AppSetting extends Model
{
    public $timestamps = false;
    protected $table = 'app_settings';

    // This table's "ID" column is a word like "OTP_EXPIRY_MIN", not a number
    // — these next two lines tell Eloquent "don't expect an auto-incrementing
    // integer ID, the primary key is text."
    protected $primaryKey = 'setting_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['setting_key', 'setting_value'];

    /**
     * A shortcut for reading one setting's value, e.g. AppSetting::get('OTP_EXPIRY_MIN', '10').
     * $default is what to return if that setting doesn't exist in the table at all.
     *
     * `Cache::remember($cacheKey, $seconds, function () { ... })` means:
     * "if we already fetched this recently, reuse that answer instead of
     * asking the database again; otherwise, run the function below to get
     * the real answer, and remember it for 60 seconds." This just avoids
     * hitting the database repeatedly for values that almost never change.
     *
     * The `function () use ($key, $default) { ... }` part is a PHP closure
     * (a mini nameless function). Normally a function can't see variables
     * from outside itself — `use ($key, $default)` is what explicitly lets
     * it borrow those two specific variables from the code around it.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("app_setting:$key", 60, function () use ($key, $default) {
            $row = static::find($key);
            return $row ? $row->setting_value : $default;
        });
    }
}
