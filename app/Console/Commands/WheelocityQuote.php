<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WheelocityID;
use App\Models\Game;
use App\Models\Wallet;
use App\Models\WheelocityBettingResult;
use App\Models\GameResultSetting;
use App\Events\GetWheelocityID;
use App\Models\WheelocityPlay;
use DB;

class WheelocityQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "quote:wheelocity";

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
        $game = WheelocityID::get();
        if ($game->isEmpty()) {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $date = $dateReplace . "001";
            WheelocityID::create(["wheelocity_id" => $date]);
           
        } else {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $ID = WheelocityID::orderBy("id", "DESC")->first()->wheelocity_id;

            //WheelocityID::create(["wheelocity_id" => $ID + 1]);

            $resultSet = GameResultSetting::where('game_type','WHEELOCITY')->first();
            if(!$resultSet)
            {
                $winValue = 'MAX(amount) as amount';
                $having = 'MAX(amount)';
            }else{
                if($resultSet->win_result == 'MAX'){
                    $winValue = 'MAX(amount) as amount';
                    $having = 'MAX(amount)';
                }elseif($resultSet->win_result == 'MIN'){
                    $winValue = 'MIN(amount) as amount';
                    $having = 'MIN(amount)';
                }elseif($resultSet->win_result == 'MEDIUM'){
                    $winValue = 'AVG(amount) as amount';
                    $having = 'AVG(amount)';
                }
            }

            $winnigUserData =DB::select( DB::raw("SELECT $winValue FROM wheelocity_betting WHERE status = 2 AND game_id = '$ID' HAVING $having > 0 ") );

        
            $arrayData = [];

            if ($winnigUserData) {
                foreach($winnigUserData  as $value)
                {
                    array_push($arrayData,$value->amount);
                }

                $string = implode(',', $arrayData);

                //print_r($string);die;
                
                $userData = DB::select( DB::raw("SELECT * FROM wheelocity_betting WHERE amount IN ($string) AND status = 2 AND game_id = '$ID'") );

                if($userData)
                {
                     foreach ($userData as $value) {
                        if($value->value == 0)
                        {
                            $winningAmount = $value->amount * 2;
                        }elseif($value->value == 1){
                            $winningAmount = $value->amount * 3;
                        }elseif($value->value == 2){
                            $winningAmount = $value->amount * 5;
                        }elseif($value->value == 3){
                            $winningAmount = $value->amount * 50;
                        }
                        
                        WheelocityBettingResult::create([
                        "wc_winning_amount" => $winningAmount,
                        "wc_betting_amount" => $value->amount,
                        "play_id" => $ID,
                        "user_id" => $value->user_id,
                        "wc_winning_value" => $value->value,
                    ]);
                }
                    
                   
                       

                        #Update betting Status
                        WheelocityPlay::where("value", $value->value)
                            ->where("game_id", $ID)
                            ->where("amount", $value->amount)
                            ->where("status", 2)
                            ->update([
                                "status" => 1,
                            ]);

                        WheelocityPlay::where("game_id", $ID)
                            ->where("status", 2)
                            ->update([
                                "status" => 0,
                            ]);

                        
                        $totalWalletAmount = Wallet::where(
                            "user_id",
                            $value->user_id
                        )->sum("user_bonus_amount");

                        $totalAmount = Wallet::where(
                            "user_id",
                            $value->user_id
                        )->sum("total_amount");

                        $winAmnt = $value->amount + $winningAmount;
                        $totalWinningAmount = $totalWalletAmount + $winAmnt;
                        $finalAmount = $totalAmount + $winAmnt;
                        Wallet::where("user_id", $value->user_id)->update([
                            "user_bonus_amount" => $totalWinningAmount,
                            "total_amount" => $finalAmount
                        ]);

                       
                    WheelocityID::create(["wheelocity_id" => $ID + 1]);
                 

                    }else{
                         $storedValue = "0123";
                        $aRandomValue = $storedValue[rand(0, strlen($storedValue) - 1)];


                        WheelocityBettingResult::create([
                            "wc_winning_amount" => 0,
                            "wc_betting_amount" => 0,
                            "play_id" => $ID,
                            "user_id" => 0,
                            "wc_winning_value" => $aRandomValue,
                        ]);

                        WheelocityPlay::where("game_id", $ID)
                        ->where("status", 2)
                                ->update([
                                    "status" => 0,
                                ]);

                        
                        WheelocityID::create(["wheelocity_id" => $ID + 1]);
                       
                    }
                }else{
                    //print_r($results);die;
                    $storedValue = "0123";
                    $aRandomValue = $storedValue[rand(0, strlen($storedValue) - 1)];


                    WheelocityBettingResult::create([
                        "wc_winning_amount" => 0,
                        "wc_betting_amount" => 0,
                        "play_id" => $ID,
                        "user_id" => 0,
                        "wc_winning_value" => $aRandomValue,
                    ]);

                    WheelocityPlay::where("game_id", $ID)
                    ->where("status", 2)
                            ->update([
                                "status" => 0,
                            ]);

                    
                    WheelocityID::create(["wheelocity_id" => $ID + 1]);
                   

                }
          
        }

        $data = WheelocityID::orderBy("id", "DESC")->first();
        if($game->time_left < 2){
            WheelocityID::create(["wheelocity_id" => $data->wheelocity_id + 1]);
            $data = WheelocityID::orderBy("id", "DESC")->first();
        }
        event(new GetWheelocityID($data));
        $this->info("Successfully run.");
    }
}
