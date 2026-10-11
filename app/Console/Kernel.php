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
        $schedule->command('ranking:recalculate')->daily();
        $schedule->command('folgas:recalculate')->daily();
        $schedule->command('audit:prune')->dailyAt('03:15');
        $schedule->command('lembretes:processar')
            ->everyFiveMinutes()
            ->between((string) config('whatsapp.window_start', '08:00'), (string) config('whatsapp.window_end', '18:00'))
            ->withoutOverlapping();
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
