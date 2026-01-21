<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled posts (every minute)
        $schedule->job(new \App\Jobs\ProcessScheduledPosts)
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Process autopost queue (every 5 minutes)
        $schedule->job(new \App\Jobs\ProcessAutopost)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Check domains status - NS activation and SSL (every 5 minutes)
        $schedule->job(new \App\Jobs\CheckDomainsStatus)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Server health checks (every 10 minutes)
        $schedule->call(function () {
            $servers = \App\Models\Server::where('is_active', true)->get();
            foreach ($servers as $server) {
                dispatch(new \App\Jobs\CheckServerHealth($server));
            }
        })->everyTenMinutes();

        // Clean old activity logs (daily at 3 AM)
        $schedule->call(function () {
            $days = \App\Models\GlobalSetting::get('activity_log_retention_days', 30);
            \Spatie\Activitylog\Models\Activity::where('created_at', '<', now()->subDays($days))->delete();
        })->dailyAt('03:00');

        // Reset daily AI request counters (daily at midnight)
        $schedule->call(function () {
            \App\Models\AiSetting::query()->update(['requests_today' => 0]);
        })->dailyAt('00:00');

        // Clean failed jobs older than 7 days (weekly)
        $schedule->command('queue:prune-failed --hours=168')
            ->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
