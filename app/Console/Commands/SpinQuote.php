<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Spin;
use DB;

class SpinQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "quote:spin";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Respectively send an exclusive quote to everyone daily via email.";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $results = DB::select(
                DB::raw(
                    "SELECT * FROM spin WHERE 
                    time < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND status = 0;"
                )
            );

        if($results)
        {
            foreach ($results as $value) {
                Spin::whereId($value->id)->update([
                    'status' =>1,
                
                ]);
            }
        }


        $this->info("Successfully run.");
    }
}
