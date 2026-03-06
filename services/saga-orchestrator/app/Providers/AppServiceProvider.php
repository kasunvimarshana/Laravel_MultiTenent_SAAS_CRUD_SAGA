<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider – standard Laravel application service provider.
 *
 * Additional interface bindings are handled by SagaServiceProvider.
 * This provider is reserved for framework-level bootstrapping (e.g. model
 * strict mode, global macros).
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enable strict mode in non-production environments to catch silent errors early.
        if (!$this->app->isProduction()) {
            \Illuminate\Database\Eloquent\Model::shouldBeStrict();
        }
    }
}
