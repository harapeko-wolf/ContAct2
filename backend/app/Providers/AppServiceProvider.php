<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\DocumentRepositoryInterface;
use App\Repositories\DocumentViewRepositoryInterface;
use App\Repositories\DocumentFeedbackRepositoryInterface;
use App\Repositories\AppSettingRepositoryInterface;

// Repository Implementations
use App\Repositories\CompanyRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\DocumentViewRepository;
use App\Repositories\DocumentFeedbackRepository;
use App\Repositories\AppSettingRepository;

// Service Interfaces
use App\Services\CompanyServiceInterface;
use App\Services\DocumentServiceInterface;
use App\Services\ScoreCalculationServiceInterface;
use App\Services\DashboardServiceInterface;
use App\Services\FileManagementServiceInterface;

// Service Implementations
use App\Services\CompanyService;
use App\Services\DocumentService;
use App\Services\ScoreCalculationService;
use App\Services\DashboardService;
use App\Services\FileManagementService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository Layer DI Bindings
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(DocumentViewRepositoryInterface::class, DocumentViewRepository::class);
        $this->app->bind(DocumentFeedbackRepositoryInterface::class, DocumentFeedbackRepository::class);
        $this->app->bind(AppSettingRepositoryInterface::class, AppSettingRepository::class);

        // Service Layer DI Bindings
        $this->app->bind(CompanyServiceInterface::class, CompanyService::class);
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
        $this->app->bind(ScoreCalculationServiceInterface::class, ScoreCalculationService::class);
        $this->app->bind(DashboardServiceInterface::class, DashboardService::class);
        $this->app->bind(FileManagementServiceInterface::class, FileManagementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
