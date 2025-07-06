<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\CompanyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class CompanyService implements CompanyServiceInterface
{
    private CompanyRepositoryInterface $companyRepository;
    private ScoreCalculationServiceInterface $scoreCalculationService;

    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        ScoreCalculationServiceInterface $scoreCalculationService
    ) {
        $this->companyRepository = $companyRepository;
        $this->scoreCalculationService = $scoreCalculationService;
    }

    public function getCompaniesPaginatedWithScore(int $perPage, int $page, string $sortBy = 'created_at', string $sortOrder = 'desc', ?string $userId = null): LengthAwarePaginator
    {
        $paginator = $this->companyRepository->getPaginatedCompanies($perPage, $page, $sortBy, $sortOrder, $userId);
        $companies = collect($paginator->items());

        // スコア計算のためのID配列を作成
        $companyIds = $companies->pluck('id')->toArray();

        // バッチでスコアを計算
        $scores = $this->scoreCalculationService->calculateBatchScores($companyIds);

        // 会社データにスコアを追加
        $companies->each(function ($company) use ($scores) {
            $company->score = $scores[$company->id] ?? 0;
        });

        return $paginator;
    }

    public function createCompany(array $data): Company
    {
        return $this->companyRepository->create($data);
    }

    public function updateCompany(string $id, array $data): Company
    {
        return $this->companyRepository->update($id, $data);
    }

    public function deleteCompany(string $id): bool
    {
        return $this->companyRepository->delete($id);
    }

    public function getCompanyById(string $id): ?Company
    {
        return $this->companyRepository->findById($id);
    }

    public function getCompanyScoreDetails(string $id): array
    {
        $company = $this->companyRepository->findById($id);

        if (!$company) {
            throw new InvalidArgumentException('Company not found');
        }

        return $this->scoreCalculationService->calculateDetailedScore($company);
    }

    public function getBookingStatus(Company $company): array
    {
        return $this->companyRepository->getBookingStatus($company);
    }

    public function getCompaniesByUserId(string $userId): Collection
    {
        return $this->companyRepository->findByUserId($userId);
    }

    public function getCompanyStats(): array
    {
        return $this->companyRepository->getCompanyStats();
    }
}
