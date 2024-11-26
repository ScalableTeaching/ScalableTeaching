<?php

namespace App\Console;

use App\Console\Commands\Export\TaskStatus;
use App\Console\Commands\LoadCurrentProjects;
use App\Console\Commands\MarkExpiredProjects;
use App\Console\Commands\MoveGrades;
use App\Console\Commands\PreloadTask;
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
        LoadCurrentProjects::class,
        MarkExpiredProjects::class,
        TaskStatus::class,
        MoveGrades::class,
        PreloadTask::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('tasks:mark-expired')->everyFiveMinutes()->sendOutputTo(storage_path('logs/tasks-mark-expired.log'));
        $schedule->command('tasks:delegate')->everyFifteenMinutes()->sendOutputTo(storage_path('logs/tasks-delegate.log'));
        $schedule->command('pipelines:refresh-stale')->everyThirtyMinutes()->sendOutputTo(storage_path('logs/pipelines-refresh-stale.log'));
        $schedule->command('tasks:preload')->everyThirtyMinutes()->sendOutputTo(storage_path('logs/tasks-preload.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
