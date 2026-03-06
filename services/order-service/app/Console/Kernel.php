<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Console Kernel — registers Artisan commands.
 */
class Kernel extends ConsoleKernel
{
    /** @var array<int, class-string> */
    protected $commands = [
        \App\Console\Commands\ProcessOrderCommands::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // No scheduled tasks for the order service
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
