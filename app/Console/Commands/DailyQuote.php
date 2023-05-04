<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameID;
use App\Models\Game;
use App\Models\Wallet;
use App\Models\BettingResult;
use App\Events\GameCreated;
use DB;

class DailyQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "quote:daily";

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

        $this->info("Successfully run.");
    }
}
