<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AppSettingRepository implements AppSettingRepositoryInterface
{
    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function create(array $data): AppSetting
    {
        $setting = AppSetting::create($data);
        $this->clearCache();
        return $setting;
    }

    public function updateByKey(string $key, mixed $value): AppSetting
    {
        $setting = AppSetting::where('key', $key)->firstOrFail();
        $setting->update(['value' => $value]);
        $this->clearCache();
        return $setting;
    }

    public function deleteByKey(string $key): bool
    {
        $deleted = AppSetting::where('key', $key)->delete() > 0;
        if ($deleted) {
            $this->clearCache();
        }
        return $deleted;
    }

    public function findByKey(string $key): ?AppSetting
    {
        return AppSetting::where('key', $key)->first();
    }

    public function getSettingsByKeys(array $keys): Collection
    {
        return AppSetting::whereIn('key', $keys)->get();
    }

    public function getAllSettings(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '_all', self::CACHE_TTL, function () {
            return AppSetting::all();
        });
    }

    public function getPublicSettings(): Collection
    {
        return Cache::remember(self::CACHE_KEY . '_public', self::CACHE_TTL, function () {
            return AppSetting::where('is_public', true)->get();
        });
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->findByKey($key);
        return $setting ? $setting->value : $default;
    }

    public function clearCache(): bool
    {
        Cache::forget(self::CACHE_KEY . '_all');
        Cache::forget(self::CACHE_KEY . '_public');
        return true;
    }

    // Additional methods for test compatibility
    public function findByType(string $type): Collection
    {
        return AppSetting::where('type', $type)->get();
    }

    public function getPublicSettingsKeyValue(): array
    {
        return $this->getPublicSettings()->pluck('value', 'key')->toArray();
    }

    public function getSettingsKeyValue(): array
    {
        return $this->getAllSettings()->pluck('value', 'key')->toArray();
    }

    public function update(string $key, array $data): ?AppSetting
    {
        $setting = AppSetting::where('key', $key)->first();
        if (!$setting) {
            return null;
        }

        $setting->update($data);
        $this->clearCache();
        return $setting;
    }

    public function updateOrCreate(array $data): AppSetting
    {
        $setting = AppSetting::updateOrCreate(
            ['key' => $data['key']],
            $data
        );
        $this->clearCache();
        return $setting;
    }

    public function delete(string $key): bool
    {
        return $this->deleteByKey($key);
    }

    public function exists(string $key): bool
    {
        return AppSetting::where('key', $key)->exists();
    }

    public function countTotalSettings(): int
    {
        return AppSetting::count();
    }
}
