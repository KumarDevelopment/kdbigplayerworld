<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ThreeMinuteGameID;
use App\Models\ThreeMinuteGamePlay;
use App\Models\Wallet;
use App\Models\ThreeMinuteBettingResult;
use DB;

class ThreeMinuteParityQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "quote:minutes";

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
        $game = ThreeMinuteGameID::get();
        if ($game->isEmpty()) {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $date = $dateReplace . "001";
            ThreeMinuteGameID::create(["game_id" => $date]);
            for ($i = 1; $i <= 10; $i++) {
                $data = ["game_id" => $date, "value" => $i, "count" => 0];
                DB::table("three_minute_betting_Value_count")->insert($data);
            }
        } else {
            $dateReplace = str_replace("-", "", date("Y-m-d"));
            $gameID = ThreeMinuteGameID::orderBy("id", "DESC")->first()
                ->game_id;
            $results = DB::select(
                DB::raw(
                    "SELECT value FROM three_minute_betting_Value_count WHERE count =  0 AND game_id = '$gameID'"
                )
            );

            $checkBettingResult = ThreeMinuteGamePlay::where(
                "game_id",
                $gameID
            )->first();
            if (!$checkBettingResult) {
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
        }

        $this->info("Successfully run.");
    }
}
