<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ServiceRequestService;
use App\Services\ServiceRequestWorkflowService;
use App\Services\EvidenceService;

class ServiceRequestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar como singletons para mejor performance
        $this->app->singleton(ServiceRequestService::class);
        $this->app->singleton(ServiceRequestWorkflowService::class);
        $this->app->singleton(EvidenceService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
