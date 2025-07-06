<?php

namespace App\Repositories;

use App\Models\DocumentView;
use Illuminate\Database\Eloquent\Collection;

interface DocumentViewRepositoryInterface
{
    /**
     * 閲覧ログを作成
     *
     * @param array $data
     * @return DocumentView
     */
    public function create(array $data): DocumentView;

    /**
     * ドキュメントの閲覧ログを取得
     *
     * @param string $documentId
     * @return Collection
     */
    public function getViewsByDocument(string $documentId): Collection;

    /**
     * 会社の閲覧ログを取得
     *
     * @param string $companyId
     * @return Collection
     */
    public function getViewsByCompany(string $companyId): Collection;

    /**
     * 複数会社の閲覧ログを一括取得（N+1問題対応）
     *
     * @param array $companyIds
     * @param int $minDuration
     * @return array
     */
    public function getBatchViewsByCompanies(array $companyIds, int $minDuration = 0): array;

    /**
     * 期間内の閲覧ログ数を取得
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return int
     */
    public function countViewsBetween(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): int;

    /**
     * 総閲覧数を取得
     *
     * @return int
     */
    public function countTotalViews(): int;

    /**
     * 最新の閲覧ログを取得
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentViews(int $limit = 10): Collection;

    /**
     * 指定時間以上の閲覧ログを取得
     *
     * @param string $companyId
     * @param int $minDuration
     * @return Collection
     */
    public function getViewsByCompanyWithMinDuration(string $companyId, int $minDuration): Collection;

    /**
     * 会社の閲覧統計を取得（Service層用）
     *
     * @param string $companyId
     * @return array
     */
    public function getViewStatsByCompany(string $companyId): array;

    /**
     * 複数会社の閲覧統計を一括取得
     *
     * @param array $companyIds
     * @return array
     */
    public function getBatchViewStats(array $companyIds): array;
}
