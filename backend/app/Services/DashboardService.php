<?php

namespace App\Services;

use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\DocumentRepositoryInterface;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use App\Repositories\AppSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class DashboardService implements DashboardServiceInterface
{
    private CompanyRepositoryInterface $companyRepository;
    private DocumentRepositoryInterface $documentRepository;
    private DocumentViewRepositoryInterface $viewRepository;
    private DocumentFeedbackRepositoryInterface $feedbackRepository;
    private AppSettingRepositoryInterface $settingRepository;

    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        DocumentRepositoryInterface $documentRepository,
        DocumentViewRepositoryInterface $viewRepository,
        DocumentFeedbackRepositoryInterface $feedbackRepository,
        AppSettingRepositoryInterface $settingRepository
    ) {
        $this->companyRepository = $companyRepository;
        $this->documentRepository = $documentRepository;
        $this->viewRepository = $viewRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->settingRepository = $settingRepository;
    }

    public function getStats(?\App\Models\User $user = null): array
    {
        // Get current totals based on user permissions
        if ($user && $user->isAdmin()) {
            // Admin users see all companies
            $totalCompanies = $this->companyRepository->count();
            $userCompanyIds = []; // Empty means all companies for admin
        } elseif ($user) {
            // Regular users see only their company's data
            $totalCompanies = $this->companyRepository->countByUserId($user->id);
            $userCompanies = $this->companyRepository->getCompaniesByUserId($user->id);
            $userCompanyIds = $userCompanies->pluck('id')->toArray();
        } else {
            // Users without authentication see 0
            $totalCompanies = 0;
            $userCompanyIds = [];
        }

        // For admin users, get all data; for regular users, filter by their companies
        if ($user && $user->isAdmin()) {
            $totalDocuments = $this->documentRepository->count();
            $totalViews = $this->viewRepository->countTotalViews();
            $totalFeedback = $this->feedbackRepository->countTotalFeedback();
        } elseif (!empty($userCompanyIds)) {
            // Count documents for user's companies
            $totalDocuments = 0;
            foreach ($userCompanyIds as $companyId) {
                $totalDocuments += \App\Models\Document::where('company_id', $companyId)->count();
            }

            // Count views for user's companies
            $totalViews = 0;
            foreach ($userCompanyIds as $companyId) {
                $totalViews += \App\Models\DocumentView::whereHas('document', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->count();
            }

            // Count feedback for user's companies
            $totalFeedback = 0;
            foreach ($userCompanyIds as $companyId) {
                $totalFeedback += \App\Models\DocumentFeedback::whereHas('document', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->count();
            }
        } else {
            $totalDocuments = 0;
            $totalViews = 0;
            $totalFeedback = 0;
        }

        // Calculate growth rates
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Company growth rates based on user permissions
        if ($user && $user->isAdmin()) {
            $companiesThisMonth = $this->companyRepository->countCreatedBetween($thisMonth, Carbon::now());
            $companiesLastMonth = $this->companyRepository->countCreatedBetween($lastMonthStart, $lastMonthEnd);
        } elseif ($user) {
            $companiesThisMonth = $this->companyRepository->countCreatedBetweenByUserId($thisMonth, Carbon::now(), $user->id);
            $companiesLastMonth = $this->companyRepository->countCreatedBetweenByUserId($lastMonthStart, $lastMonthEnd, $user->id);
        } else {
            $companiesThisMonth = 0;
            $companiesLastMonth = 0;
        }

        // Views and feedback growth rates based on user permissions
        if ($user && $user->isAdmin()) {
            $viewsThisMonth = $this->viewRepository->countViewsBetween($thisMonth, Carbon::now());
            $viewsLastMonth = $this->viewRepository->countViewsBetween($lastMonthStart, $lastMonthEnd);
            $feedbackThisMonth = $this->feedbackRepository->countFeedbackBetween($thisMonth, Carbon::now());
            $feedbackLastMonth = $this->feedbackRepository->countFeedbackBetween($lastMonthStart, $lastMonthEnd);
        } elseif (!empty($userCompanyIds)) {
            // Count views this month for user's companies
            $viewsThisMonth = 0;
            foreach ($userCompanyIds as $companyId) {
                $viewsThisMonth += \App\Models\DocumentView::whereHas('document', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->whereBetween('viewed_at', [$thisMonth, Carbon::now()])->count();
            }

            // Count views last month for user's companies
            $viewsLastMonth = 0;
            foreach ($userCompanyIds as $companyId) {
                $viewsLastMonth += \App\Models\DocumentView::whereHas('document', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->whereBetween('viewed_at', [$lastMonthStart, $lastMonthEnd])->count();
            }

            // Count feedback this month for user's companies
            $feedbackThisMonth = 0;
            foreach ($userCompanyIds as $companyId) {
                $feedbackThisMonth += \App\Models\DocumentFeedback::whereHas('document', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->whereBetween('created_at', [$thisMonth, Carbon::now()])->count();
            }

            // Count feedback last month for user's companies
            $feedbackLastMonth = 0;
            foreach ($userCompanyIds as $companyId) {
                $feedbackLastMonth += \App\Models\DocumentFeedback::whereHas('document', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            }
        } else {
            $viewsThisMonth = 0;
            $viewsLastMonth = 0;
            $feedbackThisMonth = 0;
            $feedbackLastMonth = 0;
        }

        // Calculate growth rates
        $companyGrowthRate = $this->calculateGrowthRate($companiesThisMonth, $companiesLastMonth);
        $viewGrowthRate = $this->calculateGrowthRate($viewsThisMonth, $viewsLastMonth);
        $feedbackGrowthRate = $this->calculateGrowthRate($feedbackThisMonth, $feedbackLastMonth);

        // Get recent data for dashboard display - filtered by user permissions
        $recentFeedback = $this->getRecentFeedback(4, $user);
        $recentActivity = $this->getRecentActivity(4, $user);
        $surveySettings = $this->getSurveySettings();

        return [
            'stats' => [
                'total_companies' => $totalCompanies,
                'total_documents' => $totalDocuments,
                'total_views' => $totalViews,
                'total_feedback' => $totalFeedback,
                'company_growth_rate' => $companyGrowthRate,
                'view_growth_rate' => $viewGrowthRate,
                'feedback_growth_rate' => $feedbackGrowthRate
            ],
            'companies' => [
                'total' => $totalCompanies,
                'this_month' => $companiesThisMonth,
                'last_month' => $companiesLastMonth
            ],
            'documents' => [
                'total' => $totalDocuments
            ],
            'views' => [
                'total' => $totalViews,
                'this_month' => $viewsThisMonth,
                'last_month' => $viewsLastMonth
            ],
            'feedback' => [
                'total' => $totalFeedback,
                'this_month' => $feedbackThisMonth,
                'last_month' => $feedbackLastMonth
            ],
            'growth_rates' => [
                'companies' => $companyGrowthRate,
                'views' => $viewGrowthRate,
                'feedback' => $feedbackGrowthRate
            ],
            'recent_feedback' => $recentFeedback,
            'recent_activity' => $recentActivity,
            'survey_settings' => $surveySettings
        ];
    }

    public function getRecentFeedback(int $limit = 4, ?\App\Models\User $user = null): Collection
    {
        $allFeedback = $this->feedbackRepository->getRecentFeedback($limit * 3); // Get more to filter

        // Filter by user permissions
        if ($user && !$user->isAdmin()) {
            $userCompanies = $this->companyRepository->getCompaniesByUserId($user->id);
            $userCompanyIds = $userCompanies->pluck('id')->toArray();

            $allFeedback = $allFeedback->filter(function ($feedback) use ($userCompanyIds) {
                return in_array($feedback->document->company_id, $userCompanyIds);
            });
        }

        return $allFeedback->take($limit);
    }

    public function getRecentActivity(int $limit = 4, ?\App\Models\User $user = null): Collection
    {
        $allActivity = $this->viewRepository->getRecentViews($limit * 3); // Get more to filter

        // Filter by user permissions
        if ($user && !$user->isAdmin()) {
            $userCompanies = $this->companyRepository->getCompaniesByUserId($user->id);
            $userCompanyIds = $userCompanies->pluck('id')->toArray();

            $allActivity = $allActivity->filter(function ($activity) use ($userCompanyIds) {
                return in_array($activity->document->company_id, $userCompanyIds);
            });
        }

        return $allActivity->take($limit);
    }

    public function getSurveySettings(): array
    {
        $settings = $this->settingRepository->getPublicSettings()->pluck('value', 'key')->toArray();

        return $settings['survey'] ?? [
            'survey_enabled' => true,
            'rating_scale' => 5,
            'feedback_types' => ['positive', 'negative', 'rating'],
            'auto_survey_trigger' => true,
            'survey_delay' => 3000, // milliseconds
            'required_view_duration' => 30 // seconds
        ];
    }

    public function calculateGrowthRate(int $currentCount, int $previousCount): float
    {
        if ($previousCount === 0) {
            return 0.0;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 2);
    }

    public function getStatsByPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $companiesCreated = $this->companyRepository->countCreatedBetween($startDate, $endDate);
        $totalViews = $this->viewRepository->countViewsBetween($startDate, $endDate);
        $totalFeedback = $this->feedbackRepository->countFeedbackBetween($startDate, $endDate);

        // Calculate period length in days
        $periodDays = $startDate->diffInDays($endDate) ?: 1;

        return [
            'companies_created' => $companiesCreated,
            'total_views' => $totalViews,
            'total_feedback' => $totalFeedback,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $periodDays
            ],
            'averages' => [
                'companies_per_day' => round($companiesCreated / $periodDays, 1),
                'views_per_day' => round($totalViews / $periodDays, 1),
                'feedback_per_day' => round($totalFeedback / $periodDays, 1)
            ]
        ];
    }
}
