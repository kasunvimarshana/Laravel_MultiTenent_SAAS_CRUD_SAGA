<?php
declare(strict_types=1);
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers inventory-specific configuration and scheduled commands.
 */
class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/inventory.php', 'inventory');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ProcessInventoryCommands::class,
                \App\Console\Commands\ExpireReservations::class,
            ]);
        }
    }
}
