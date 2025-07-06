<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

interface CompanyRepositoryInterface
{
    /**
     * ページネーション付きで会社一覧を取得
     */
    public function getPaginatedCompanies(int $perPage, int $page, string $sortBy = 'created_at', string $sortOrder = 'desc', ?string $userId = null): LengthAwarePaginator;

    /**
     * 会社IDの配列を取得
     */
    public function getCompaniesByIds(array $companyIds): Collection;

    /**
     * 会社を作成
     */
    public function create(array $data): Company;

    /**
     * 会社を更新
     */
    public function update(string $id, array $data): Company;

    /**
     * 会社を削除
     */
    public function delete(string $id): bool;

    /**
     * 会社を検索
     */
    public function findById(string $id): ?Company;

    /**
     * ユーザーの会社一覧を取得
     */
    public function getCompaniesByUserId(string $userId): Collection;

    /**
     * 会社の総数を取得
     */
    public function count(): int;

    /**
     * 指定された名前の会社数を取得
     */
    public function countByName(string $name): int;

    /**
     * ユーザーが作成した会社数を取得
     */
    public function countByUserId(string $userId): int;

    /**
     * 期間内に作成された会社数を取得
     */
    public function countCreatedBetween(Carbon $startDate, Carbon $endDate): int;

    /**
     * ユーザーの期間内に作成された会社数を取得
     */
    public function countCreatedBetweenByUserId(Carbon $startDate, Carbon $endDate, string $userId): int;

    /**
     * 会社の予約ステータスを取得
     */
    public function getBookingStatus(Company $company): array;

    /**
     * ユーザーIDで会社を検索
     */
    public function findByUserId(string $userId): Collection;

    /**
     * 会社の統計情報を取得
     */
    public function getCompanyStats(): array;

    /**
     * ユーザーが会社にアクセス可能かチェック
     */
    public function canUserAccessCompany(string $companyId, string $userId): bool;
}
