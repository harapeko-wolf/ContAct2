<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
        'is_public',
    ];

    protected $casts = [
        'value' => 'json',
        'is_public' => 'boolean',
    ];

    /**
     * 設定値を取得（キャッシュ付き）
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "app_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * 設定値を保存（キャッシュも更新）
     */
    public static function set(string $key, $value, string $description = null, string $type = 'string', bool $is_public = false)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'type' => $type,
                'is_public' => $is_public,
            ]
        );

        // キャッシュを更新
        $cacheKey = "app_setting_{$key}";
        Cache::put($cacheKey, $value, 3600);

        return $setting;
    }

    /**
     * 複数の設定を一括で取得
     */
    public static function getMultiple(array $keys, $default = [])
    {
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = self::get($key, $default[$key] ?? null);
        }
        return $settings;
    }

    /**
     * カテゴリ別の設定を取得
     */
    public static function getByCategory(string $category)
    {
        $pattern = $category . '.%';
        $settings = self::where('key', 'like', $pattern)->get();

        $result = [];
        foreach ($settings as $setting) {
            // キーからカテゴリ部分を除去
            $key = str_replace($category . '.', '', $setting->key);
            $result[$key] = $setting->value;
        }

        return $result;
    }

    /**
     * 公開設定のみ取得（フロントエンド用）
     */
    public static function getPublicSettings()
    {
        $cacheKey = "public_settings";

        return Cache::remember($cacheKey, 3600, function () {
            $settings = self::where('is_public', true)->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        });
    }

    /**
     * 設定値削除時にキャッシュもクリア
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("app_setting_{$setting->key}");
            Cache::forget("public_settings");
        });

        static::deleted(function ($setting) {
            Cache::forget("app_setting_{$setting->key}");
            Cache::forget("public_settings");
        });
    }
}
