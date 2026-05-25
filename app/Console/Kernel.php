<?php

namespace App\Console;

use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Avoid automatic command discovery here because it is causing
        // bootstrap memory exhaustion in this environment.
        // Explicitly register the commands that are currently needed.
        ArtisanApplication::starting(function ($artisan) {
            $artisan->resolve(\App\Console\Commands\RunRealtimeSimulation::class);
        });

        require base_path('routes/console.php');
    }
}
