<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ServiceRequestService;
use App\Services\ServiceRequestWorkflowService;
use App\Services\EvidenceService;
use App\Services\SmartParser\SmartParserPipeline;
use App\Services\SmartParser\StructuredFormatDetector;
use App\Services\SmartParser\TextNormalizer;

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

        // Smart Parser: registrar como singletons (clases stateless reutilizables)
        $this->app->singleton(TextNormalizer::class);
        $this->app->singleton(StructuredFormatDetector::class);
        $this->app->singleton(SmartParserPipeline::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
