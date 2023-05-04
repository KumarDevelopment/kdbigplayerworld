<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Wallet;
use App\Models\HeadTail;
use App\Models\HeadAndTailBetting;
use App\Models\HeadAndTailBettingResult;
use Validator;
use Config;
use DB;


class HeadAndTailController extends Controller {


    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }


    /** getGameID
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getHeadTailGameID(Request $request) {
        $game = HeadTail::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'gameID' => $game], 200);
    }

public function getHeadTailGameIDLongpool(Request $request) {
       // set the response content type to text/event-stream
    header('Content-Type: text/event-stream');
    header("Access-Control-Allow-Origin: *");

    // disable output buffering
    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    // set implicit flush to true to ensure data is sent to the client immediately
    ob_implicit_flush(true);

      
       
    // simulate real-time updates
    for ($i = 0; $i < 1; $i++) {
        // generate some random data
        
     $game = HeadTail::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
    
      $game['remaning_time'] = $remaningtime;
      $amounthead=HeadAndTailBetting::where('status', 2)->where('value', 0)->where('game_id', $game->head_tail_id)->sum('amount');
      $amounttail=HeadAndTailBetting::where('status', 2)->where('value', 1)->where('game_id', $game->head_tail_id)->sum('amount');
       $game['head_amount'] = $amounthead;
      $game['tell_amount'] = $amounttail;
        // send the data to the client
        echo json_encode($game) . PHP_EOL;
        echo PHP_EOL;

        // flush the output buffer to ensure data is sent to the client
        flush();

        // sleep for a few seconds to simulate real-time updates
       // sleep(1);
    }

    // return an empty response to signify that the connection has closed
    return response('', 200);
    }

    /** gamePlay
     *
     * @return \Illuminate\Http\JsonResponse
    */

    public function headAndTailPlay(Request $request) {
      $user = $this->user;
       $validator = Validator::make($request->all(), ['play_id'=>'required','play_type' => 'required','amount' =>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

         #check wallet amount
        $walletAmount = wallet::where('user_id',$user->id)->first();
        if(!$walletAmount)
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Insufficient amount'], 500);
        }
        if($walletAmount->total_amount < $request->amount){
             return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'The amount is not valid'], 500);
        }
        $finalAmount = $walletAmount->user_recharge_amount - $request->amount;

        $game = HeadAndTailBetting::create(['user_id' => $user->id, 'game_id'=> $request->play_id,'value' => $request->play_type,'amount' =>$request->amount]);

         $finalTotalAmount = $walletAmount->total_amount - $request->amount;
         if($request->amount > $walletAmount->user_recharge_amount)
         {
            $checkRechargeAmount = $request->amount - $walletAmount->user_recharge_amount;
            $finalAmount = $walletAmount->user_bonus_amount - $checkRechargeAmount;
            #Update the wallet amount
            wallet::where('user_id',$user->id)->update([
                'user_bonus_amount' => $finalAmount,
                'total_amount' => $finalTotalAmount,
                'user_recharge_amount' => 0
            ]);

          
         }else{
             $finalAmount = $walletAmount->user_recharge_amount - $request->amount;
              #Update the wallet amount
                wallet::where('user_id',$user->id)->update([
                    'total_amount' => $finalTotalAmount,
                    'user_recharge_amount' => $finalAmount
                ]);
         }

        DB::table('head_tail_betting_count')->where('play_id', $request->play_id)->where('play_value',$request->play_type)
            ->update([
              'count'=> DB::raw('count+1'), 
            ]);


        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Game successfully registered', 'game' => $game], 201);


    }

    /** getBettingResult
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getHeadTailBettingResult(Request $request) {
        $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $bettingResult = HeadAndTailBettingResult::orderBy('id','DESC')->offset($request->offset)
        ->limit($request->limit)->get();

        $bettingResultCount = HeadAndTailBettingResult::count();
        foreach($bettingResult as $value){
            if($value->ht_winning_value == 0)
            {
                $value->ht_winning_value = 'HEAD';
            }else{
                $value->ht_winning_value = 'TAIL';
            }
            

        }
        
         if (!$bettingResult->isEmpty()) {
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'totalCount' => $bettingResultCount, 'result' => $bettingResult], 200);

        }else {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','totalCount' => $bettingResultCount,'result' => $bettingResult], 200);
        }
        
    }

    /**
     * getUserReferHistory
     *
     * @return \Illuminate\Http\JsonResponse
     * type = 1 my order
     * type = 2 Everyones order
     * 
     */
    public function getHeadTailOrders(Request $request) {

        $user = $this->user;
        $validator = Validator::make($request->all(), ['type' => 'required','offset'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        
        $orders = HeadAndTailBetting::select('head_tail_betting.*');
        if($request->type == 1)
        {
          $orders->where('head_tail_betting.user_id', $user->id);
        }
        $orders->orderBy("head_tail_betting.id", "DESC");
        if($request->limit)
        {
            $orders->offset($request->offset);
            $orders->limit($request->limit);
        }
        
        $myORders = $orders->get();
        if(!$myORders->isEmpty())
        {
            foreach($myORders as $val)
            {
                
                $winningAmount = HeadAndTailBettingResult::where('ht_winning_value' ,$val->value)->where('user_id',$val->user_id)->where('play_id',$val->game_id)->first();

                if(!$winningAmount)
                {
                    $val->winning_amount = 0;
                }else{
                    $val->winning_amount = $winningAmount->ht_winning_amount;
                }

             }
               return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $myORders], 200);
        }else {

                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $myORders], 200);
        }
        
        
    }

public function getamount(){
      $game = HeadTail::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
    
      $game['remaning_time'] = $remaningtime;
      $amounthead=HeadAndTailBetting::where('status', 2)->where('value', 0)->where('game_id', $game->head_tail_id)->sum('amount');
      $amounttail=HeadAndTailBetting::where('status', 2)->where('value', 1)->where('game_id', $game->head_tail_id)->sum('amount');
      $game['head_amount'] = $amounthead;
      $game['tell_amount'] = $amounttail;
      echo $amounttail;
    // return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $myORders], 200);
}

}

