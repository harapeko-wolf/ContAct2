<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;

interface DashboardServiceInterface
{
    /**
     * ダッシュボードの統計情報を取得
     *
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getStats(?\App\Models\User $user = null): array;

    /**
     * 最新のフィードバックを取得
     *
     * @param int $limit
     * @param \App\Models\User|null $user
     * @return Collection
     */
    public function getRecentFeedback(int $limit = 4, ?\App\Models\User $user = null): Collection;

    /**
     * 最新のアクティビティを取得
     *
     * @param int $limit
     * @param \App\Models\User|null $user
     * @return Collection
     */
    public function getRecentActivity(int $limit = 4, ?\App\Models\User $user = null): Collection;

    /**
     * アンケート設定を取得
     *
     * @return array
     */
    public function getSurveySettings(): array;

    /**
     * 成長率を計算
     *
     * @param int $currentCount
     * @param int $previousCount
     * @return float
     */
    public function calculateGrowthRate(int $currentCount, int $previousCount): float;

    /**
     * 期間別の統計情報を取得
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    public function getStatsByPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array;
}
