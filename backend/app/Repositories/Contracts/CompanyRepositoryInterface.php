<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CompanyRepositoryInterface
{
    /**
     * 会社一覧をスコア付きで取得
     */
    public function getCompaniesWithScore(int $perPage = 10): LengthAwarePaginator;

    /**
     * 会社のフィードバックデータを一括取得
     */
    public function getFeedbacksDataBatch(array $companyIds): array;

    /**
     * 会社の閲覧データを一括取得
     */
    public function getViewsDataBatch(array $companyIds): array;

    /**
     * IDで会社を取得
     */
    public function findById(string $id): ?Company;

    /**
     * ユーザーの会社一覧を取得
     */
    public function getByUserId(string $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * 会社を作成
     */
    public function create(array $data): Company;

    /**
     * 会社を更新
     */
    public function update(Company $company, array $data): bool;

    /**
     * 会社を削除
     */
    public function delete(Company $company): bool;

    /**
     * 公開会社一覧を取得
     */
    public function getPublicCompanies(): Collection;

    /**
     * 会社の存在確認（ユーザー所有）
     */
    public function existsForUser(string $companyId, string $userId): bool;
}
