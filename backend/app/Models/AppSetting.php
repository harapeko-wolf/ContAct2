<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * リクエスト中のキャッシュ（staticで共有）
     */
    private static $cache = [];

    /**
     * 設定値を取得（キャッシュ付き）
     */
    public static function get(string $key, $default = null)
    {
        if (!isset(self::$cache[$key])) {
            $setting = self::where('key', $key)->first();
            self::$cache[$key] = $setting ? $setting->value : null;
        }
        
        return self::$cache[$key] ?? $default;
    }

    /**
     * 設定値を保存
     */
    public static function set(string $key, $value, ?string $description = null, string $type = 'string', bool $is_public = false)
    {
        // キャッシュをクリア
        self::clearCache($key);
        
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'type' => $type,
                'is_public' => $is_public,
            ]
        );
    }

    /**
     * キャッシュをクリア
     */
    public static function clearCache(?string $key = null)
    {
        if ($key === null) {
            // 全キャッシュクリア
            self::$cache = [];
        } else {
            // 特定キーのみクリア
            unset(self::$cache[$key]);
        }
    }

    /**
     * 複数の設定を一括で取得（最適化版）
     */
    public static function getMultiple(array $keys, $default = [])
    {
        // 未キャッシュのキーを特定
        $uncachedKeys = [];
        foreach ($keys as $key) {
            if (!isset(self::$cache[$key])) {
                $uncachedKeys[] = $key;
            }
        }
        
        // 未キャッシュのキーを一括取得
        if (!empty($uncachedKeys)) {
            $settings = self::whereIn('key', $uncachedKeys)->get();
            foreach ($settings as $setting) {
                self::$cache[$setting->key] = $setting->value;
            }
            
            // 見つからなかったキーはnullでキャッシュ
            foreach ($uncachedKeys as $key) {
                if (!isset(self::$cache[$key])) {
                    self::$cache[$key] = null;
                }
            }
        }
        
        // 結果を組み立て
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::$cache[$key] ?? ($default[$key] ?? null);
        }
        
        return $result;
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
        $settings = self::where('is_public', true)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }

        return $result;
    }
}
