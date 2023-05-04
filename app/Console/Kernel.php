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
        Commands\DailyQuote::class,
        Commands\HeadTailQuote::class,
        Commands\SpinQuote::class,
        Commands\ThreeMinuteParityQuote::class,
        Commands\WheelocityQuote::class
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('quote:daily')
        ->everyMinute();
        $schedule->command('quote:seconds')
        ->everySeconds(30);
        $schedule->command('quote:spin')
        ->everyThirtyMinute(30);
        $schedule->command('quote:minutes')
        ->everyThreeMinutes();
        $schedule->command('quote:wheelocity')
        ->everySeconds(30);
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}