<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\CompanyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class CompanyRepository implements CompanyRepositoryInterface
{
    protected Company $model;

    public function __construct(Company $model)
    {
        $this->model = $model;
    }

    /**
     * ページネーション付きで会社一覧を取得
     */
    public function getPaginatedCompanies(int $perPage, int $page, string $sortBy = 'created_at', string $sortOrder = 'desc', ?string $userId = null): LengthAwarePaginator
    {
        $query = $this->model->select('companies.*');

        // ユーザーIDでフィルタ
        if ($userId) {
            $query->where('companies.user_id', $userId);
        }

        // ソート処理
        switch ($sortBy) {
            case 'name':
                $query->orderBy('companies.name', $sortOrder);
                break;
            case 'email':
                $query->orderBy('companies.email', $sortOrder);
                break;
            case 'created_at':
            default:
                $query->orderBy('companies.created_at', $sortOrder);
                break;
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 会社IDの配列を取得
     */
    public function getCompaniesByIds(array $companyIds): Collection
    {
        return $this->model->whereIn('id', $companyIds)->get();
    }

    /**
     * 会社を作成
     */
    public function create(array $data): Company
    {
        return $this->model->create($data);
    }

    /**
     * 会社を更新
     */
    public function update(string $id, array $data): Company
    {
        $company = $this->model->findOrFail($id);
        $company->update($data);
        return $company->fresh();
    }

    /**
     * 会社を削除
     */
    public function delete(string $id): bool
    {
        $company = $this->model->findOrFail($id);
        return $company->delete();
    }

    /**
     * 会社を検索
     */
    public function findById(string $id): ?Company
    {
        return $this->model->find($id);
    }

    /**
     * ユーザーの会社一覧を取得
     */
    public function getCompaniesByUserId(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * 会社の総数を取得
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * 指定された名前の会社数を取得
     */
    public function countByName(string $name): int
    {
        return $this->model->where('name', $name)->count();
    }

    /**
     * ユーザーが作成した会社数を取得
     */
    public function countByUserId(string $userId): int
    {
        return $this->model->where('user_id', $userId)->count();
    }

    /**
     * 期間内に作成された会社数を取得
     */
    public function countCreatedBetween(Carbon $startDate, Carbon $endDate): int
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * ユーザーの期間内に作成された会社数を取得
     */
    public function countCreatedBetweenByUserId(Carbon $startDate, Carbon $endDate, string $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * 会社の予約ステータスを取得
     */
    public function getBookingStatus(Company $company): array
    {
        // TimeRex予約データを取得
        $timerexBookings = $company->timerex_bookings ?? [];

        if (empty($timerexBookings)) {
            return [
                'has_booking' => false,
                'booking_date' => null,
                'booking_time' => null,
                'status' => 'no_booking',
                'meeting_url' => null
            ];
        }

        // 最新の予約を取得
        $latestBooking = collect($timerexBookings)->sortByDesc('created_at')->first();

        return [
            'has_booking' => true,
            'booking_date' => $latestBooking['start_date'] ?? null,
            'booking_time' => $latestBooking['start_time'] ?? null,
            'status' => $latestBooking['status'] ?? 'pending',
            'meeting_url' => $latestBooking['meeting_url'] ?? null
        ];
    }

    /**
     * ユーザーIDで会社を検索
     */
    public function findByUserId(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * 会社の統計情報を取得
     */
    public function getCompanyStats(): array
    {
        $totalCompanies = $this->model->count();
        $activeCompanies = $this->model->where('status', 'active')->count();
        $inactiveCompanies = $totalCompanies - $activeCompanies;

        $companiesWithDocuments = $this->model
            ->whereHas('documents')
            ->count();

        $companiesWithBookings = $this->model
            ->whereNotNull('timerex_bookings')
            ->count();

        // トップパフォーマンス会社を取得（仮実装）
        $topCompanies = $this->model
            ->select('id', 'name')
            ->limit(5)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'score' => rand(80, 100) // 実際のスコア計算に置き換える
                ];
            })
            ->toArray();

        return [
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'inactive_companies' => $inactiveCompanies,
            'companies_with_documents' => $companiesWithDocuments,
            'companies_with_bookings' => $companiesWithBookings,
            'average_score' => $totalCompanies > 0 ? rand(75, 90) : 0, // 実際のスコア計算に置き換える
            'top_performing_companies' => $topCompanies
        ];
    }

    public function canUserAccessCompany(string $companyId, string $userId): bool
    {
        // For now, implement a simple check
        // In a real application, this would check user permissions, company ownership, etc.
        $company = $this->findById($companyId);
        if (!$company) {
            return false;
        }

        // Simple implementation: all users can access all active companies
        // This should be replaced with proper authorization logic
        return $company->status === 'active';
    }
}
