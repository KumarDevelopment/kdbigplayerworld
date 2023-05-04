<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameID;
use App\Models\Game;
use App\Models\Wallet;
use App\Models\BettingResult;
use App\Events\GameCreated;
use DB;
use App\Models\WheelocityID;
use App\Models\WheelocityBettingResult;
use App\Models\GameResultSetting;
use App\Events\GetWheelocityID;
use App\Models\WheelocityPlay;
use App\Models\ThreeMinuteGameID;
use App\Models\ThreeMinuteGamePlay;
use App\Models\ThreeMinuteBettingResult;
use App\Models\HeadTail;
use App\Models\HeadAndTailBettingResult;
use App\Models\HeadAndTailBetting;

class FastparittycronController extends Controller
{
  public function getgameidcontroller()
    {
        $game = GameID::get();
        if ($game->isEmpty()) {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $date = $dateReplace . "001";
            GameID::create(["game_id" => $date]);
            for ($i = 1; $i <= 10; $i++) {
                $data = ["game_id" => $date, "value" => $i, "count" => 0];
                DB::table("betting_Value_count")->insert($data);
            }
        } else {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $gameID = GameID::orderBy("id", "DESC")->first()->game_id;
            $results = DB::select(
                DB::raw(
                    "SELECT value FROM betting_Value_count WHERE count =  0 AND game_id = '$gameID'"
                )
            );

            $checkBettingResult = BettingResult::where(
                "game_id",
                $gameID
            )->first();
            if (!$checkBettingResult) {
                if ($results) {
                    $winnigUserData = Game::where(
                        "value",
                        array_rand($results, 1)
                    )
                        ->where("game_id", $gameID)
                        ->where("status", 2)
                        ->get();
                    if (!$winnigUserData->isEmpty()) {
                        foreach ($winnigUserData as $value) {
                            // group
                            if (
                                $value->value == 11 ||
                                $value->value == 12 ||
                                $value->value == 13
                            ) {
                                $winningAmount = ($value->amount * 50) / 100;
                            } else {
                                $winningAmount = ($value->amount * 90) / 100;
                            }

                            BettingResult::create([
                                "winning_amount" => $winningAmount,
                                "betting_amount" => $value->amount,
                                "game_id" => $gameID,
                                "user_id" => $value->user_id,
                                "winning_value" => $value->value,
                            ]);

                            #Update betting Status
                            Game::where("value", $value->value)
                                ->where("game_id", $gameID)
                                ->update([
                                    "status" => 1,
                                ]);
                            #Update betting Status
                            Game::where("game_id", $gameID)
                                ->where("status", 2)
                                ->update([
                                    "status" => 0,
                                ]);

                            $totalBonusAmount = Game::where(
                                "value",
                                $results[0]->value
                            )
                                ->where("game_id", $gameID)
                                ->sum("amount");
                            $totalWinnigWalletAmount = Wallet::where(
                                "user_id",
                                $value->user_id
                            )->sum("user_bonus_amount");

                            $totalAmount = Wallet::where(
                                "user_id",
                                $value->user_id
                            )->sum("total_amount");

                            $winAmnt = $value->amount + $winningAmount;
                            $totalWinningAmount =
                                $totalWinnigWalletAmount + $winAmnt;
                            $finalAmount = $totalAmount + $winAmnt;
                            Wallet::where("user_id", $value->user_id)->update([
                                "user_bonus_amount" => $totalWinningAmount,
                                "total_amount" => $finalAmount,
                            ]);

                            //GameID::whereDate('created_at',date('Y-m-d'))->truncate();
                            DB::table("betting_Value_count")
                                ->where("game_id", $gameID)
                                ->truncate();
                        }

                        DB::table("betting_Value_count")
                            ->where("game_id", $gameID)
                            ->truncate();
                        GameID::create(["game_id" => $gameID + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $gameID + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table("betting_Value_count")->insert($data);
                        }
                    } else {
                        $storedValue = "123456789";
                        $aRandomValue = rand(1, 10);

                        BettingResult::create([
                            "winning_amount" => 0,
                            "betting_amount" => 0,
                            "game_id" => $gameID,
                            "user_id" => 0,
                            "winning_value" => $aRandomValue,
                        ]);

                        DB::table("betting_Value_count")
                            ->where("game_id", $gameID)
                            ->truncate();

                        #Update betting Status
                        Game::where("game_id", $gameID)
                            ->where("status", 2)
                            ->update([
                                "status" => 0,
                            ]);
                        GameID::create(["game_id" => $gameID + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $gameID + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table("betting_Value_count")->insert($data);
                        }
                    }
                } else {
                    $storedValue = "123456789";
                    $aRandomValue = rand(1, 10);

                    BettingResult::create([
                        "winning_amount" => 0,
                        "betting_amount" => 0,
                        "game_id" => $gameID,
                        "user_id" => 0,
                        "winning_value" => $aRandomValue,
                    ]);

                    DB::table("betting_Value_count")
                        ->where("game_id", $gameID)
                        ->truncate();

                    #Update betting Status
                    Game::where("game_id", $gameID)
                        ->where("status", 2)
                        ->update([
                            "status" => 0,
                        ]);

                    GameID::create(["game_id" => $gameID + 1]);
                     $gameData = GameID::orderBy('id','DESC')->first();
      $time= date('Y-m-d H:i:s', strtotime($game->created_at));
      $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
      $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $gameData['remaning_time'] = $remaningtime;
   // $gameData=['data'=>'ererer','ereres'=>123];
      event(new GameCreated($gameData));
                    for ($i = 1; $i <= 10; $i++) {
                        $data = [
                            "game_id" => $gameID + 1,
                            "value" => $i,
                            "count" => 0,
                        ];
                        DB::table("betting_Value_count")->insert($data);
                    }
                }
            }
        }

        // $this->info("Successfully run.");
    }
    
    public function getwhollecitycontroller(){
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

        // $data = WheelocityID::orderBy("id", "DESC")->first();
        // if($game->time_left < 2){
        //     WheelocityID::create(["wheelocity_id" => $data->wheelocity_id + 1]);
        //     $data = WheelocityID::orderBy("id", "DESC")->first();
        // }
       // event(new GetWheelocityID($data));
     //   $this->info("Successfully run.");
    }
    
    
    public function threeminutegameidfromcron()
    {
        $game = ThreeMinuteGameID::get();
        if ($game->isEmpty()) {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $date = $dateReplace . "001";
            ThreeMinuteGameID::create(["game_id" => $date]);
            for ($i = 1; $i <= 10; $i++) {
                $data = ["game_id" => $date, "value" => $i, "count" => 0];
                DB::table("three_minute_betting_Value_count")->insert($data);
            }
            echo "1";
        } else {
             //echo "2";
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $gameID = ThreeMinuteGameID::orderBy("id", "DESC")->first()
                ->game_id;
            $results = DB::select(
                DB::raw(
                    "SELECT value FROM three_minute_betting_Value_count WHERE count =  0 AND game_id = '$gameID'"
                )
            );
          //  print_r($results);

            $checkBettingResult = ThreeMinuteGamePlay::where(
                "game_id",
                $gameID
            )->first();
            //print_r($checkBettingResult);
            if (!$checkBettingResult) {
                //echo "33";
                if ($results) {
                    $winnigUserData = ThreeMinuteGamePlay::where(
                        "value",
                        array_rand($results, 1)
                    )
                        ->where("game_id", $gameID)
                        ->where("status", 2)
                        ->get();
                    if (!$winnigUserData->isEmpty()) {
                        foreach ($winnigUserData as $value) {
                            // group
                            if (
                                $value->value == 11 ||
                                $value->value == 12 ||
                                $value->value == 13
                            ) {
                                $winningAmount = ($value->amount * 50) / 100;
                            } else {
                                $winningAmount = ($value->amount * 90) / 100;
                            }

                            ThreeMinuteBettingResult::create([
                                "winning_amount" => $winningAmount,
                                "betting_amount" => $value->amount,
                                "game_id" => $gameID,
                                "user_id" => $value->user_id,
                                "winning_value" => $value->value,
                            ]);

                            #Update betting Status
                            ThreeMinuteGamePlay::where("value", $value->value)
                                ->where("game_id", $gameID)
                                ->update([
                                    "status" => 1,
                                ]);
                            #Update betting Status
                            ThreeMinuteGamePlay::where(
                                "game_id",
                                $gameID
                            )->update([
                                "status" => 0,
                            ]);

                            $totalBonusAmount = ThreeMinuteGamePlay::where(
                                "value",
                                $results[0]->value
                            )
                                ->where("game_id", $gameID)
                                ->sum("amount");
                            $totalWalletAmount = Wallet::where(
                                "user_id",
                                $value->user_id
                            )->sum("user_bonus_amount");

                            $winAmnt = $value->amount + $winningAmount;
                            $totalWinningAmount = $totalWalletAmount + $winAmnt;
                            Wallet::where("user_id", $value->user_id)->update([
                                "user_bonus_amount" => $totalWinningAmount,
                            ]);

                            //GameID::whereDate('created_at',date('Y-m-d'))->truncate();
                            DB::table("three_minute_betting_Value_count")
                                ->where("game_id", $gameID)
                                ->truncate();
                        }

                        DB::table("three_minute_betting_Value_count")
                            ->where("game_id", $gameID)
                            ->truncate();
                        ThreeMinuteGameID::create(["game_id" => $gameID + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $gameID + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table(
                                "three_minute_betting_Value_count"
                            )->insert($data);
                        }
                    } else {
                        $storedValue = "0123456789";
                        $aRandomValue =
                            $storedValue[rand(0, strlen($storedValue) - 1)];

                        ThreeMinuteBettingResult::create([
                            "winning_amount" => 0,
                            "betting_amount" => 0,
                            "game_id" => $gameID,
                            "user_id" => 0,
                            "winning_value" => array_rand($results, 1),
                        ]);

                        DB::table("three_minute_betting_Value_count")
                            ->where("game_id", $gameID)
                            ->truncate();

                        #Update betting Status
                        ThreeMinuteGamePlay::where("game_id", $gameID)->update([
                            "status" => 0,
                        ]);
                        ThreeMinuteGameID::create(["game_id" => $gameID + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $gameID + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table(
                                "three_minute_betting_Value_count"
                            )->insert($data);
                        }
                    }
                } else {
                    $storedValue = "0123456789";
                    $aRandomValue =
                        $storedValue[rand(0, strlen($storedValue) - 1)];

                    ThreeMinuteBettingResult::create([
                        "winning_amount" => 0,
                        "betting_amount" => 0,
                        "game_id" => $gameID,
                        "user_id" => 0,
                        "winning_value" => $aRandomValue,
                    ]);

                    DB::table("three_minute_betting_Value_count")
                        ->where("game_id", $gameID)
                        ->truncate();

                    #Update betting Status
                    ThreeMinuteGamePlay::where("game_id", $gameID)->update([
                        "status" => 0,
                    ]);

                    ThreeMinuteGameID::create(["game_id" => $gameID + 1]);
                    for ($i = 1; $i <= 10; $i++) {
                        $data = [
                            "game_id" => $gameID + 1,
                            "value" => $i,
                            "count" => 0,
                        ];
                        DB::table("three_minute_betting_Value_count")->insert(
                            $data
                        );
                    }
                }
            }
            
            else{
                 $gameID = ThreeMinuteGameID::orderBy("id", "DESC")->first()
                ->game_id;
             ThreeMinuteGameID::create(["game_id" => $gameID + 1]);
                    for ($i = 1; $i <= 10; $i++) {
                        $data = [
                            "game_id" => $gameID + 1,
                            "value" => $i,
                            "count" => 0,
                        ];
                        DB::table("three_minute_betting_Value_count")->insert(
                            $data
                        );
                    }
            }
        }

       // $this->info("Successfully run.");
    }
    
    public function createheadandtellgameid(){
        
        $htData = HeadTail::get();
        if ($htData->isEmpty()) {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $date = $dateReplace . "001";
            HeadTail::create(["head_tail_id" => $date]);
            for ($i = 0; $i <= 1; $i++) {
                $data = ["play_id" => $date, "play_value" => $i, "count" => 0];
                DB::table("head_tail_betting_count")->insert($data);
            }
        } else {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $ID = HeadTail::orderBy("id", "DESC")->first()->head_tail_id;
            $winResult = DB::select(
                DB::raw(
                    "SELECT play_value FROM head_tail_betting_count WHERE count =  0 AND play_id = '$ID'"
                )
            );

            if ($winResult) {
                $results = $winResult;
            } else {
                $results = DB::select(
                    DB::raw(
                        "SELECT * FROM head_tail_betting_count WHERE count =  ( SELECT MIN(count) FROM head_tail_betting_count ) AND play_id = '$ID'"
                    )
                );
            }

            $checkBettingResult = HeadAndTailBettingResult::where(
                "play_id",
                $ID
            )->first();
            if (!$checkBettingResult) {
                $winnigUserData = DB::select(
                    DB::raw(
                        "SELECT max(amount) as amount FROM head_tail_betting WHERE status = 2 AND game_id = '$ID' HAVING MAX(amount) > 0 "
                    )
                );

                //print_r(count(var)$winnigUserData);die;
                // $winnigUserData =DB::select( DB::raw("SELECT max(amount) FROM head_tail_betting WHERE amount =  ( SELECT MAX(amount) FROM head_tail_betting ) AND status = 2 AND game_id = '$ID'") );

                $arrayData = [];

                if ($winnigUserData) {
                    foreach ($winnigUserData as $value) {
                        array_push($arrayData, $value->amount);
                    }

                    $string = implode(",", $arrayData);

                    $userData = DB::select(
                        DB::raw(
                            "SELECT * FROM head_tail_betting WHERE amount IN ($string) AND status = 2 AND game_id = '$ID'"
                        )
                    );

                    foreach ($userData as $value) {
                        $winningAmount = ($value->amount * 90) / 100;
                        HeadAndTailBettingResult::create([
                            "ht_winning_amount" => $winningAmount,
                            "ht_betting_amount" => $value->amount,
                            "play_id" => $ID,
                            "user_id" => $value->user_id,
                            "ht_winning_value" => $value->value,
                        ]);

                        #Update betting Status
                        HeadAndTailBetting::where("value", $value->value)
                            ->where("game_id", $ID)
                            ->where("status", 2)
                            ->update([
                                "status" => 1,
                            ]);

                        HeadAndTailBetting::where("game_id", $ID)
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
                            "total_amount" => $finalAmount,
                        ]);

                        DB::table("head_tail_betting_count")
                            ->where("game_id", $ID)
                            ->truncate();
                        HeadTail::create(["head_tail_id" => $ID + 1]);
                        for ($i = 0; $i <= 1; $i++) {
                            $data = [
                                "play_id" => $ID + 1,
                                "play_value" => $i,
                                "count" => 0,
                            ];
                            DB::table("head_tail_betting_count")->insert($data);
                        }
                    }
                } else {
                    //print_r($results);die;
                    $storedValue = "01";
                    $aRandomValue =
                        $storedValue[rand(0, strlen($storedValue) - 1)];

                    HeadAndTailBettingResult::create([
                        "ht_winning_amount" => 0,
                        "ht_betting_amount" => 0,
                        "play_id" => $ID,
                        "user_id" => 0,
                        "ht_winning_value" => $aRandomValue,
                    ]);

                    HeadAndTailBetting::where("game_id", $ID)
                        ->where("status", 2)
                        ->update([
                            "status" => 0,
                        ]);

                    DB::table("head_tail_betting_count")
                        ->where("game_id", $ID)
                        ->truncate();
                    HeadTail::create(["head_tail_id" => $ID + 1]);
                    for ($i = 0; $i <= 1; $i++) {
                        $data = [
                            "play_id" => $ID + 1,
                            "play_value" => $i,
                            "count" => 0,
                        ];
                        DB::table("head_tail_betting_count")->insert($data);
                    }
                }
            }
        }

       // $this->info("Successfully run.");
    
    }
    
}
