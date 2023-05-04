<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HeadTail;
use App\Models\HeadAndTailBettingResult;
use App\Models\HeadAndTailBetting;
use App\Models\Wallet;
use DB;

class HeadTailQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "quote:seconds";

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

        $this->info("Successfully run.");
    }
}
