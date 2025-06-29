<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // ...existing commands...
        \App\Console\Commands\GenerateEstimateTemplates::class,
        \App\Console\Commands\CleanOldSessions::class,
        \App\Console\Commands\TestSmsNotifications::class,
        \App\Console\Commands\ApplyToursMigration::class,
        \App\Console\Commands\CheckTours::class,
        \App\Console\Commands\ResetTours::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Очистка устаревших сессий ежедневно в 2 часа ночи
        $schedule->command('session:clean')->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
