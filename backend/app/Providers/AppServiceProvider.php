<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository の依存性注入を登録
        $this->app->bind(
            \App\Repositories\Contracts\CompanyRepositoryInterface::class,
            \App\Repositories\CompanyRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
