<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WheelocityID;
use App\Models\WheelocityPlay;
use App\Models\WheelocityBettingResult;
use Validator;
use Config;
use DB;


class WheelocityController extends Controller {


    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }


    /** gamePlay
     *
     * @return \Illuminate\Http\JsonResponse
    */

    public function wheelocityPlay(Request $request) {
       $user = $this->user;
       $validator = Validator::make($request->all(), ['game_id'=>'required','value' => 'required','amount' =>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
       
         #check wallet amount
        $walletAmount = Wallet::where('user_id',$user->id)->first();
        if(!$walletAmount)
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Insufficient amount'], 500);
        }
        if($walletAmount->total_amount < $request->amount){
             return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'The amount is not valid'], 500);
        }
        
        $game = WheelocityPlay::create(['user_id' => $user->id, 'game_id'=> $request->game_id,'value' => $request->value,'amount' =>$request->amount]);

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
            
    	return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Game successfully registered', 'game' => $game], 201);
    }


     /**
     * getUserReferHistory
     *
     * @return \Illuminate\Http\JsonResponse
     * type = 1 my order
     * type = 2 Everyones order
     * 
     */
    public function getWheelocityOrders(Request $request) {

        $user = $this->user;
        $validator = Validator::make($request->all(), ['type' => 'required','offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        if($request->type == 1)
        {
            $orders = WheelocityPlay::select('wheelocity_betting.*','users.mobile');
            $orders->leftjoin('users','users.id','=','wheelocity_betting.user_id');
            $orders->where('wheelocity_betting.user_id', $user->id);
            $orders->orderBy("wheelocity_betting.id", "DESC");
            $orders->offset($request->offset);
            $orders->limit($request->limit);
            $myORders = $orders->get();
            if(!$myORders->isEmpty())
            {
                foreach($myORders as $val)
                {
                    
                    if($val->value == 0)
                    {
                        $val->value = 'B';
                    }
                    if($val->value == 1)
                    {
                        $val->value = 'R';
                    }
                    if($val->value == 2)
                    {
                        $val->value = 'B';
                    }
                    if($val->value == 3)
                    {
                        $val->value = 'G';
                    }

                 
                   
                }
                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $myORders], 200);
                

             }else {

                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $myORders], 200);

             }
        }else{
            $orders= [];
            $gameID  = WheelocityPlay::orderBy('id','DESC')->first()->game_id;
            for($i= 0; $i <= 50; $i++)
            {
                $storedValue ='0123';
                $aRandomValue= rand(0,3);
                $phone = '9009343783';
                $phone = sprintf('%s%04d', substr($phone, 0, -4), rand(0, 9999));
                $dateReplace = str_replace("-","",date('Y-m-d'));
                $date = $dateReplace.'00'.$i;
                $points = ($i == 0) ? 40 : 50*$i; //rand(50,200);
                if($aRandomValue == 0)
                {
                    $aRandomValue = 'B';
                }
                if($aRandomValue == 1)
                {
                    $aRandomValue = 'R';
                }
                if($aRandomValue == 2)
                {
                    $aRandomValue = 'B';
                }
                if($aRandomValue == 3)
                {
                    $aRandomValue = 'G';
                }

                $orders[] = array('game_id' => $gameID,'mobile' => $phone ,'value' => $aRandomValue,'points' => $points);
           
                     
            }
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $orders], 200);

          
            
            // $orders = Game::select('game_betting.*','users.mobile')
            // ->leftjoin('users','users.id','=','game_betting.user_id')
            // ->where('game_betting.game_id', $request->game_id)
            // ->orderBy("game_betting.id", "DESC")  
            // ->offset($request->offset)
            // ->limit($request->limit)->get();
            // if(!$orders->isEmpty())
            // {
            //      return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $orders], 200);

            //  }else {

            //      return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $orders], 200);

            //  }

        }
        
    }

    /** getGameID
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getWheelocityGameID(Request $request) {
        $game = WheelocityID::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;
     //  print_r($game);
      

        if($game->time_left > 1){
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'gameID' => $game], 200);
        }else{
            return $this->setWheelocityGameID($request);
        }
    }
    
     public function getWheelocityGameIDlongpool(Request $request) {
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
        
       $game = WheelocityID::orderBy('id','DESC')->first();
        $time= date('Y-m-d H:i:s', strtotime($game->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. '+30 seconds'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    
      $game['remaning_time'] = $remaningtime;
      $gameId=$game->wheelocity_id;
      $bluesome=WheelocityPlay::where('game_id',$gameId)->where('status','2')->where('value','2')->sum('amount');
      $blacksome=WheelocityPlay::where('game_id',$gameId)->where('status','2')->where('value','0')->sum('amount');
      $redsome=WheelocityPlay::where('game_id',$gameId)->where('status','2')->where('value','1')->sum('amount');
      $greensome=WheelocityPlay::where('game_id',$gameId)->where('status','2')->where('value','3')->sum('amount');
       $game['blue'] = $bluesome;
       $game['black'] = $blacksome;
       $game['red'] = $redsome;
       $game['green'] = $greensome;
      // $game['blue'] = $bluesome;
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

    /** setGameID
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setWheelocityGameID(Request $request) {
        $gamedata = WheelocityID::orderBy('id','DESC')->first();
        WheelocityID::create(["wheelocity_id" => $gamedata->wheelocity_id + 1]);

        $game = WheelocityID::orderBy('id','DESC')->first();
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'gameID' => $game], 200);
    }

    /** getBettingResult
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getWheelocityBettingResult(Request $request) {
        $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

       
        $bettingResult = WheelocityBettingResult::orderBy('id','DESC')->offset($request->offset)
            ->limit($request->limit)->get();
        // print_r($bettingResult);
        // exit();
        // foreach ($bettingResult as $values){
        // if($values->wc_winning_value=='0'){
        //  $values->colur='black';
        // }
        // elseif ($bettingResult->wc_winning_value=='1') {
        //     //$bettingResult['color']='red';
        //     $values->colur='red';
        // }
        // elseif ($bettingResult->wc_winning_value=='2') {
        //     //$bettingResult['color']='blue';
        //     $values->colur='blue';
        // }
        // else{
        //   // $bettingResult['color']='green';
        //     $values->colur='green';
        //   // blue
        // }
        // }
        $bettingResultCount = WheelocityBettingResult::count();
      
        
        
         if (!$bettingResult->isEmpty()) {
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'totalCount' => $bettingResultCount, 'result' => $bettingResult], 200);

        }else {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','totalCount' => $bettingResultCount,'result' => $bettingResult], 200);
        }
        
    }


    
}