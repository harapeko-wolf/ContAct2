<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Collection;

interface AppSettingRepositoryInterface
{
    /**
     * 設定を作成
     *
     * @param array $data
     * @return AppSetting
     */
    public function create(array $data): AppSetting;

    /**
     * 設定を更新
     *
     * @param string $key
     * @param mixed $value
     * @return AppSetting
     */
    public function updateByKey(string $key, mixed $value): AppSetting;

    /**
     * 設定を削除
     *
     * @param string $key
     * @return bool
     */
    public function deleteByKey(string $key): bool;

    /**
     * キーで設定を検索
     *
     * @param string $key
     * @return AppSetting|null
     */
    public function findByKey(string $key): ?AppSetting;

    /**
     * 複数の設定を一括取得
     *
     * @param array $keys
     * @return Collection
     */
    public function getSettingsByKeys(array $keys): Collection;

    /**
     * 全ての設定を取得
     *
     * @return Collection
     */
    public function getAllSettings(): Collection;

    /**
     * 公開設定のみを取得
     *
     * @return Collection
     */
    public function getPublicSettings(): Collection;

    /**
     * 設定値を取得（デフォルト値対応）
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * 設定のキャッシュをクリア
     *
     * @return bool
     */
    public function clearCache(): bool;
}
