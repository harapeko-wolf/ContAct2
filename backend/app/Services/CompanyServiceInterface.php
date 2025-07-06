<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CompanyServiceInterface
{
    /**
     * 会社一覧を取得（スコア計算付き）
     *
     * @param int $perPage
     * @param int $page
     * @param string $sortBy
     * @param string $sortOrder
     * @param string|null $userId
     * @return LengthAwarePaginator
     */
    public function getCompaniesPaginatedWithScore(int $perPage, int $page, string $sortBy = 'created_at', string $sortOrder = 'desc', ?string $userId = null): LengthAwarePaginator;

    /**
     * 会社を作成
     *
     * @param array $data
     * @return Company
     */
    public function createCompany(array $data): Company;

    /**
     * 会社を更新
     *
     * @param string $id
     * @param array $data
     * @return Company
     */
    public function updateCompany(string $id, array $data): Company;

    /**
     * 会社を削除
     *
     * @param string $id
     * @return bool
     */
    public function deleteCompany(string $id): bool;

    /**
     * 会社の詳細を取得
     *
     * @param string $id
     * @return Company|null
     */
    public function getCompanyById(string $id): ?Company;

    /**
     * 会社のスコア詳細を取得
     *
     * @param string $id
     * @return array
     */
    public function getCompanyScoreDetails(string $id): array;

    /**
     * 会社の予約ステータスを取得
     *
     * @param Company $company
     * @return array
     */
    public function getBookingStatus(Company $company): array;

    /**
     * ユーザーの会社一覧を取得
     *
     * @param string $userId
     * @return Collection
     */
    public function getCompaniesByUserId(string $userId): Collection;

    /**
     * 会社の統計情報を取得
     *
     * @return array
     */
    public function getCompanyStats(): array;
}
