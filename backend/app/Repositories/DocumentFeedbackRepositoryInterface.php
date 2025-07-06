<?php

namespace App\Repositories;

use App\Models\DocumentFeedback;
use Illuminate\Database\Eloquent\Collection;

interface DocumentFeedbackRepositoryInterface
{
    /**
     * フィードバックを作成
     *
     * @param array $data
     * @return DocumentFeedback
     */
    public function create(array $data): DocumentFeedback;

    /**
     * ドキュメントのフィードバックを取得
     *
     * @param string $documentId
     * @return Collection
     */
    public function getFeedbackByDocument(string $documentId): Collection;

    /**
     * 会社のフィードバックを取得
     *
     * @param string $companyId
     * @return Collection
     */
    public function getFeedbackByCompany(string $companyId): Collection;

    /**
     * 複数会社のフィードバックを一括取得（N+1問題対応）
     *
     * @param array $companyIds
     * @return array
     */
    public function getBatchFeedbackByCompanies(array $companyIds): array;

    /**
     * 最新のフィードバックを取得
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentFeedback(int $limit = 10): Collection;

    /**
     * フィードバックタイプ別の統計を取得
     *
     * @param string $companyId
     * @return array
     */
    public function getFeedbackStatsByCompany(string $companyId): array;

    /**
     * 期間内のフィードバック数を取得
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return int
     */
    public function countFeedbackBetween(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): int;

    /**
     * 総フィードバック数を取得
     *
     * @return int
     */
    public function countTotalFeedback(): int;

    /**
     * 複数会社のフィードバック統計を一括取得
     *
     * @param array $companyIds
     * @return array
     */
    public function getBatchFeedbackStats(array $companyIds): array;
}
