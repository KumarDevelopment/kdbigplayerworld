<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Game;
use App\Models\GameID;
use App\Models\Wallet;
use GuzzleHttp\Client;
use App\Events\GetParityOrders;
use App\Models\ThreeMinuteGamePlay;
use App\Models\BettingResult;
use App\Models\ThreeMinuteGameID;
use App\Models\ThreeMinuteBettingResult;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Validator;
//use Config;
use DB;


class GameController extends Controller {


    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }


    /** gamePlay
     *
     * @return \Illuminate\Http\JsonResponse
    */

    public function gamePlays(Request $request) {
      $user = $this->user;
       $validator = Validator::make($request->all(), ['game_id'=>'required','value' => 'required','amount' =>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $string = '"'.$request->group_value.'"';
        $List = explode(',',$request->group_value);
        
         #check wallet amount
        $walletAmount = wallet::where('user_id',$user->id)->first();
        if(!$walletAmount)
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Insufficient amount'], 500);
        }
        if($walletAmount->total_amount < $request->amount){
             return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'The amount is not valid'], 500);
        }
        if($request->play_time == 'THREE_MINUTES')
        {
            if($request->type == 'group')
            {
                $game = ThreeMinuteGamePlay::create(['user_id' => $user->id, 'game_id'=> $request->game_id,'value' => $request->value,'amount' =>$request->amount,'group_value' => $request->group_value  ]);

            }else{
                $game = ThreeMinuteGamePlay::create(['user_id' => $user->id, 'game_id'=> $request->game_id,'value' => $request->value,'amount' =>$request->amount]);

            }
        }else{
            if($request->type == 'group')
            {
                $game = Game::create(['user_id' => $user->id, 'game_id'=> $request->game_id,'value' => $request->value,'amount' =>$request->amount,'group_value' => $request->group_value  ]);
                $orders = Game::select('game_betting.*','users.mobile');
                $orders->leftjoin('users','users.id','=','game_betting.user_id');
                $orders->where('game_betting.user_id', $user->id);
                $orders->where('game_betting.id', $game->id);
                $orders->orderBy("game_betting.id", "DESC");
                $myORders = $orders->first();
                $winningAmount = BettingResult::where('winning_value' ,$myORders->value)->where('user_id',$myORders->user_id)->where('game_id',$myORders->game_id)->first();

                if(!$winningAmount)
                {
                    $myORders->winning_amount = 0;
                }else{
                    $myORders->winning_amount = $winningAmount->winning_amount;
                }
                if($myORders->value == 11)
                {
                    $myORders->value = 'G';
                }
                if($myORders->value == 12)
                {
                    $myORders->value = 'V';
                }
                if($myORders->value == 13)
                {
                    $myORders->value = 'R';
                }
                //event(new GetParityOrders($myORders));

            }else{
                $game = Game::create(['user_id' => $user->id, 'game_id'=> $request->game_id,'value' => $request->value,'amount' =>$request->amount]);
                $orders = Game::select('game_betting.*','users.mobile');
                $orders->leftjoin('users','users.id','=','game_betting.user_id');
                $orders->where('game_betting.user_id', $user->id);
                $orders->where('game_betting.id', $game->id);
                $orders->orderBy("game_betting.id", "DESC");
                $myORders = $orders->first();
                $winningAmount = BettingResult::where('winning_value' ,$myORders->value)->where('user_id',$myORders->user_id)->where('game_id',$myORders->game_id)->first();

                if(!$winningAmount)
                {
                    $myORders->winning_amount = 0;
                }else{
                    $myORders->winning_amount = $winningAmount->winning_amount;
                }
                if($myORders->value == 11)
                {
                    $myORders->value = 'G';
                }
                if($myORders->value == 12)
                {
                    $myORders->value = 'V';
                }
                if($myORders->value == 13)
                {
                    $myORders->value = 'R';
                }
                
             //   event(new GetParityOrders($myORders));

            }
        }
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
            
        
        
       
        


       if($request->play_time == 'THREE_MINUTES')
       {
            if($request->type == 'group')
            {
                foreach($List as $value)
                {
                   DB::table('three_minute_betting_Value_count')->where('game_id', $request->game_id)->where('value',$value)
                    ->update([
                  'count'=> DB::raw('count+1'), 
                    ]); 
                }
                
            }else{
                DB::table('three_minute_betting_Value_count')->where('game_id', $request->game_id)->where('value',$request->value)
                ->update([
                  'count'=> DB::raw('count+1'), 
                ]);
            }
        }else{
            if($request->type == 'group')
            {
                foreach($List as $value)
                {
                   DB::table('betting_Value_count')->where('game_id', $request->game_id)->where('value',$value)
                    ->update([
                  'count'=> DB::raw('count+1'), 
                    ]); 
                }
                
            }else{
                DB::table('betting_Value_count')->where('game_id', $request->game_id)->where('value',$request->value)
                ->update([
                  'count'=> DB::raw('count+1'), 
                ]);
            }
        }
        

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Game successfully registered', 'game' => $game], 201);
    }

     /** getGameID
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getGameID(Request $request) {
        //sleep(2);
        

        $game = GameID::orderBy('id','DESC')->first();
       $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;
      
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'gameID' => $game], 200);
    }

     /** getBettingResult
     *
     * @return \Illuminate\Http\JsonResponse
     */
     public function sseevent(Request $request){
//          return response()->stream(function () {
//     while (true) {
//         $game = GameID::orderBy('id', 'DESC')->first();
//         $time = date('Y-m-d H:i:s', strtotime($game->created_at));
//         $addtime = $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
//         $currenttime = date('Y-m-d H:i:s');
//         $remaningtime = strtotime($addtime) - strtotime($currenttime);
//         $game['remaning_time'] = $remaningtime;
//         echo "data: " . json_encode($game) . "\n\n";
//         flush();
//         sleep(1);
//     }
// }, 200, [
//     'Content-Type' => 'text/event-stream',
//     'Cache-Control' => 'no-cache',
//     'Connection' => 'keep-alive',
//     'Access-Control-Allow-Origin' => '*',
//     'Access-Control-Allow-Methods' => 'GET, OPTIONS',
//     'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept'
// ]);
//  while (true) {
//             // Do some work to get data to send to the client
//             $game = GameID::orderBy('id', 'DESC')->first();
//         $time = date('Y-m-d H:i:s', strtotime($game->created_at));
//         $addtime = $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
//         $currenttime = date('Y-m-d H:i:s');
//         $remaningtime = strtotime($addtime) - strtotime($currenttime);
//         $game['remaning_time'] = $remaningtime;

//             if (!empty($game)) {
//                 // If we have data, send it to the client and exit the loop
//                 return response()->json($game);
//             }

//             // Sleep for a short period to avoid using too many resources
//             sleep(1);
//         }
        
        $response = new StreamedResponse();

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
         // Define the callback function to generate and stream data to the client
        $response->setCallback(function() {
            while (true) {
                // Generate some data to send to the client
                  $game = GameID::orderBy('id', 'DESC')->first();
        $time = date('Y-m-d H:i:s', strtotime($game->created_at));
        $addtime = $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
        $currenttime = date('Y-m-d H:i:s');
        $remaningtime = strtotime($addtime) - strtotime($currenttime);
        $game['remaning_time'] = $remaningtime;

               
                echo json_encode($game);

                // Flush the output buffer to ensure the data is sent immediately
                ob_flush();
                flush();

                // Sleep for a short period to avoid using too many resources
                sleep(1);
            }
        });

        return $response;
     }

    public function getBettingResult(Request $request) {
        $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        if($request->play_time == 'THREE_MINUTES')
        {
            $bettingResult = ThreeMinuteBettingResult::orderBy('id','DESC')->offset($request->offset)
            ->limit($request->limit)->get();

            $bettingResultCount = ThreeMinuteBettingResult::count(); 
        }else{
            $bettingResult = BettingResult::orderBy('id','DESC')->offset($request->offset)
            ->limit($request->limit)->get();

            $bettingResultCount = BettingResult::count();
        }
        
        
         if (!$bettingResult->isEmpty()) {
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'totalCount' => $bettingResultCount, 'result' => $bettingResult], 200);

        }else {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','totalCount' => $bettingResultCount,'result' => $bettingResult], 200);
        }
        
    }
    
    
//   public function getBettingResult(Request $request) {
//     $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
//     if ($validator->fails()) {
//         return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
//     }

//     $lastUpdated = time(); // initialize last updated time
//     $timeout = 25; // set the timeout to 25 seconds
    
//     while (true) {
//         if($request->play_time == 'THREE_MINUTES')
//         {
//             $bettingResult = ThreeMinuteBettingResult::orderBy('id','DESC')->offset($request->offset)
//             ->limit($request->limit)->get();

//             $bettingResultCount = ThreeMinuteBettingResult::count(); 
//         }else{
//             $bettingResult = BettingResult::orderBy('id','DESC')->offset($request->offset)
//             ->limit($request->limit)->get();

//             $bettingResultCount = BettingResult::count();
//         }

//         if ($lastUpdated < time() - $timeout) {
//             // If the last update time is greater than the timeout, break out of the loop and send the current result
//             break;
//         } elseif (!$bettingResult->isEmpty()) {
//             // If there is a new betting result, send the response
//             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'totalCount' => $bettingResultCount, 'result' => $bettingResult], 200);
//         }

//         // Sleep for 5 seconds before trying again
//         sleep(5);
//     }

//     // If there are no new betting results, return an empty response
//     return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','totalCount' => $bettingResultCount,'result' => $bettingResult], 200);
// }




     /** getWalletAmount
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getWalletAmount(Request $request) {
        $user = $this->user;
        $wallet = Wallet::where('user_id',$user->id)->first();
        if(!$wallet)
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => array()], 200);

        }
        $wallet['total_amount']=$wallet->user_recharge_amount+$wallet->user_bonus_amount;

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $wallet], 200);
    }

     /** getWalletAmount
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getgameIdDetail(Request $request) {
        $user = $this->user;
        $validator = Validator::make($request->all(), ['game_id'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $result = Game::where('id',$request->game_id)->first();

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $result], 200);
    }


    /** getThreeMinuteGameID
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getThreeMinuteGameID(Request $request) {
        $game = ThreeMinuteGameID::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +3 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'gameID' => $game], 200);
    }
public function getThreeMinuteGameIDLongpool(Request $request) {
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
        

        $game = ThreeMinuteGameID::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +3 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;
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


public function getSseData()
{
    $response = new StreamedResponse(function() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *'); // Allow all origins

        // Generate and send updated data to the client every second
for ($i = 0; $i < 10; $i++) {
    sleep(1);

    // Generate updated data
    $data['time'] = time();
    $data['message'] = 'Hello again! (' . $i . ')';

    // Send the updated data to the client as an SSE event
    echo "event: message\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_end_flush();
}
        
    });

    return $response;
}

public function getUpdatedData()
{
    // Set the timeout to 30 seconds
    set_time_limit(30);

    // Continuously check for updated data every second
    while (true) {
        // Check for updated data and return it if available
        $data = $this->checkForUpdatedData();
        if ($data !== null) {
            return response()->json($data);
        }

        // Sleep for 1 second before checking for updated data again
        sleep(1);
    }
}

private function checkForUpdatedData()
{
    // Check for updated data and return it if available
    // Replace this with your own code for checking for updated data
    if ($updatedDataIsAvailable) {
        return [
            'time' => time(),
            'message' => 'Hello again!',
        ];
    }

    // Return null if updated data is not available
    return null;
}
public function listen(Request $request)
{
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
        
$game = GameID::orderBy('id','DESC')->first();
       $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;
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






}