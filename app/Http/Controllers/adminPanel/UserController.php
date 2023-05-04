<?php

namespace App\Http\Controllers\adminPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Notification;
use App\Notifications\SendPushNotification;
use App\Models\UserLoginActivity;
use App\Models\User;
use App\Models\Wallet;
use App\Models\GameID;
use App\Models\Recharge;
use App\Models\Game;
use App\Models\BettingResult;
use App\Models\PaytmSettings;
use App\Models\Withdraw;
use App\Models\withdrawlPaymentMethod;
use App\Models\HeadAndTailBetting;
use App\Models\HeadTail;
use App\Models\HeadAndTailBettingResult;
use App\Models\ThreeMinuteGamePlay;
use App\Models\ManualPayment;
use App\Models\WheelocityID;
use App\Models\WheelocityPlay;
use App\Models\WheelocityBettingResult;
use App\Models\ThreeMinuteGameID;
use GuzzleHttp\Client;
use Validator;
use Config;
use DB;

class UserController extends Controller {

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */

    public function __construct() {

    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     */

    public function getUserList(Request $request){

        $validator = Validator::make($request->all(), ['offset'=>'required','search' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $user = User::select('*');
        if($request->search == 1)
        {
            $user->where(function ($query) use ($request) {
            $query->where('name', "like", "%" . $request->key . "%");
            $query->orWhere('mobile', "like", "%" . $request->key . "%");
            });  
        }

        if($request->limit)
        {
            $user->offset($request->offset);
            $user->limit($request->limit);
        }
        
        $result = $user->get();
        foreach($result as $value)
        {
            $bonusAmount = Wallet::where('user_id',$value->id)->first();
            if(!$bonusAmount)
            {
                $value->totalWalletAmount = 0; 
                $value->totalRecharge = 0;
            }else{
                    $value->winings = $bonusAmount->user_bonus_amount;
                    $value->topup = $bonusAmount->user_recharge_amount;
            }

            $withdrawID = withdrawlPaymentMethod::where('user_id',$value->id)->first();
            if(!$withdrawID)
            {
                $value->totalWithdrawlAmount = 0;
            }else{
                 $value->totalWithdrawlAmount = Withdraw::where('withdrawl_payment_id',$withdrawID->id)->where('withdraw_status','ACCEPTED')->sum('withdraw_amount');
            }
            $bankDetails=withdrawlPaymentMethod::where('user_id',$value->id)->first();
            if(!$bankDetails)
            {
                $value->payment_method = '';
                $value->payment_id = '';
            }
            else{
                $value->payment_method = $bankDetails->payment_method;
                $value->payment_id = $bankDetails->payment_id;
            }
            
            
        }

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'user' =>  $result], 200);
    }

    
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
    */
    
    public function updateUserStatus(Request $request){
         $validator = Validator::make($request->all(), ['userId'=>'required','status' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
          User::where('id',$request->userId)->update([
            'deleted' =>$request->status
            
        ]);
         return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Profile updated successfully'], 201);
    }
    public function admindeleteUser(Request $request){
        $validator = Validator::make($request->all(), ['userId'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
          User::where('id',$request->userId)->delete();
         return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Profile deletd permently '], 200);
    }

    public function getUserRechargeList(Request $request){

        $validator = Validator::make($request->all(), ['offset'=>'required','search' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $recharge = Recharge::query();
        $recharge->with(['user' => function ($query) {
            $query->select('id', 'mobile');
        }]);
        if($request->search == 1)
        {

            $recharge->where(function ($query) use ($request) {
              $query
                ->where('txn_id', 'like', '%'.$request->key.'%')
                ->orWhereHas('user', function ($query) use ($request) {
                    $query->where('mobile', "like", "%" . $request->key . "%");
                });
            });
         
        }

        if($request->limit)
        {
            $recharge->offset($request->offset);
            $recharge->limit($request->limit);
        }
        
        $result = $recharge->get();
        foreach($result as $value)
        {
            $value->totalRecharge = Recharge::where('user_id',$value->user_id)->where('status','SUCCESS')->sum('amount');
        }


        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'recharge' =>  $result], 200);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     */

    public function getUserActivity(Request $request){

        $validator = Validator::make($request->all(), ['user_id'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $user = User::select('users.id','users.mobile','users.created_at');
        $user->where('users.id',$request->user_id);
        $user->groupBy('users.id');
        $result = $user->get();
        foreach($result as $value)
        {
            $value->game = Game::select('game_id','value','amount','status')->where('user_id',$request->user_id)->get();
            $value->recharge = Recharge::select('*')->where('user_id',$request->user_id)->where('status','SUCCESS')->get();
        }
        
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'user' =>  $result], 200);
    }


     /**
     * Write code on Method
     *
     * @return response()
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), ['image'=>'required','title' => 'required','message' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }


        $firebaseToken = User::whereNotNull('fcmToken')->pluck('fcmToken')->all();
        $firebaseData= PaytmSettings::where('type','FIREBASE')->first();
        $firebaseKey  = $firebaseData->key_id ?? env('FIREBASE_SERVER_KEY');
        $SERVER_API_KEY = $firebaseKey;
        $imageName = time().'.'.$request->image->extension();  
        $request->image->move(public_path('images'), $imageName);
        $image = url('/').'/images/'.$imageName;

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "icon" => $image,
                "title" => $request->title,
                "body" => $request->message,
                "imageUrl" =>  $request->url, 
            ]
        ];
        $dataString = json_encode($data);

       
    
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];


        $ch = curl_init();
      
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
               
        $response = curl_exec($ch);
  
        
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Sent Successfully'], 200);
    }



     /**
     * Write code on Method
     *
     * @return response()
     */
    public function getUserLoginActivity(Request $request)
    {
        $validator = Validator::make($request->all(), ['offset'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $userloginactivity = UserLoginActivity::query();
        $userloginactivity->with(['user' => function ($query) {
        $query->select('id', 'name','mobile');
        }]);
        

        if($request->limit)
        {
            $userloginactivity->offset($request->offset);
            $userloginactivity->limit($request->limit);
        }
       
        $result = $userloginactivity->get();
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' =>  $result], 200);


    }


     /**
     * Write code on Method
     *
     * @return response()
     */
    public function parityManagement(Request $request)
    {
            if($request->play_time == 'THREE_MINUTES')
            {
                $gameID  = ThreeMinuteGameID::orderBy('id','DESC')->first();
                 $time= date('Y-m-d H:i:s', strtotime($gameID->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +3 minutes'));
       //echo $addtime;
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
     
    $gameID=$gameID->game_id;
                 $orders= [];
               
                for($i= 0; $i <= 13; $i++)
                {
                    $game = ThreeMinuteGamePlay::select( DB::raw('COUNT(user_id) AS totalUserCount'),
                    DB::raw('SUM(amount) AS totalAmount'))->where('value',$i)->where('game_id',$gameID)->get();
                    $orders[] = array('user'=> $game[0]['totalUserCount'],'amount' => $game[0]['totalAmount'] ?? 0,'value' => $i);
                }
            }else{
                 $gameID  = GameID::orderBy('id','DESC')->first();
                 //$gameID  = ThreeMinuteGameID::orderBy('id','DESC')->first();
                 $time= date('Y-m-d H:i:s', strtotime($gameID->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       //echo $addtime;
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
     
    $gameID=$gameID->game_id;
                 $orders= [];
               
                for($i= 0; $i <= 13; $i++)
                {
                    $game = Game::select( DB::raw('COUNT(user_id) AS totalUserCount'),
                    DB::raw('SUM(amount) AS totalAmount'))->where('value',$i)->where('game_id',$gameID)->get();
                    $orders[] = array('user'=> $game[0]['totalUserCount'],'amount' => $game[0]['totalAmount'] ?? 0,'value' => $i);
                }
            }
           
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'game_id' => $gameID,'remaning_time'=>$remaningtime,'result' =>  $orders], 200);

    }


     /**
     * Write code on Method
     *
     * @return response()
     */
    public function updateParityResult(Request $request)
    {
        $validator = Validator::make($request->all(), ['game_id'=>'required','value' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
         $checkBettingResult = BettingResult::where('game_id',$request->game_id)->first();
           if(!$checkBettingResult)
           {

                 $winnigUserData = Game::where('value',$request->value)->where('game_id',$request->game_id)->where('status',0)->get();
                 if (!$winnigUserData->isEmpty()) 
                 {
                     foreach($winnigUserData as $value)
                     {
                        if($value->value == 11 || $value->value == 12 || $value->value == 13)
                        {
                            $winningAmount = ($value->amount * 50) / 100;
                        }else{
                            $winningAmount = ($value->amount * 90) / 100;
                        }
                        BettingResult::create(['winning_amount' => $winningAmount,'betting_amount' =>$value->amount,
                        'game_id' => $request->game_id ,'user_id' =>  $value->user_id,'winning_value' => $value->value]);

                          #Update betting Status
                        Game::where('value',$request->value)->where('game_id',$request->game_id)->update([
                            'status' => 1
                        ]);

                        Game::where("game_id", $request->game_id)->where("status", 2)
                        ->update([
                            "status" => 0,
                        ]); 

                        $totalBonusAmount = Game::where('value',$request->value)->where('game_id',$request->game_id)->sum('amount');

                        $totalWalletAmount = Wallet::where('user_id',$value->user_id)->sum('user_bonus_amount');

                        $totalAmount = Wallet::where('user_id',$value->user_id)->sum('total_amount');
                       
                       
                        $winAmnt = $value->amount + $winningAmount;
                        $totalWinningAmount = $totalWalletAmount  + $winningAmount;
                        $finalAmount = $totalAmount  + $winningAmount;
                            Wallet::where('user_id',$value->user_id)->update([
                                'user_bonus_amount' =>$totalWinningAmount,
                                "total_amount" => $finalAmount
                            ]);


                        //GameID::whereDate('created_at',date('Y-m-d'))->truncate();
                        DB::table('betting_Value_count')->where('game_id', $request->game_id)->truncate();
                        GameID::create(["game_id" => $request->game_id + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $request->game_id + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table("betting_Value_count")->insert($data);
                        }
                    }
                }else{
                    $storedValue ='0123456789';
                    $aRandomValue= $storedValue[rand(0,strlen($storedValue) - 1)];
                   
                    BettingResult::create(['winning_amount' => 0,'betting_amount' =>0,
                   'game_id' => $request->game_id ,'user_id' =>  0,'winning_value' => $request->value]);

                    Game::where("game_id", $request->game_id)->where("status", 2)
                    ->update([
                        "status" => 0,
                    ]); 

                    GameID::create(["game_id" => $request->game_id + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $request->game_id + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table("betting_Value_count")->insert($data);
                        }
                }
            // }else
            // {
            //      $storedValue ='0123456789';
            //      $aRandomValue= $storedValue[rand(0,strlen($storedValue) - 1)];
                   
            //      BettingResult::create(['winning_amount' => 0,'betting_amount' =>0,
            //        'game_id' => $request->game_id ,'user_id' =>  0,'winning_value' => $aRandomValue]);

            //      Game::where("game_id", $request->game_id)
            //         ->update([
            //             "status" => 0,
            //         ]); 

            // }
            }
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Success'], 200);

    }


     /* * Write code on Method
     *
     * @return response()
     */
    public function getWithdrawlList(Request $request)
    {
        $validator = Validator::make($request->all(), ['offset'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $user = withdrawlPaymentMethod::select('withdraw.id','withdrawl_payment_method.payment_method','withdrawl_payment_method.user_id','users.mobile','withdraw.withdraw_amount','withdraw.withdraw_interest_amount'
        ,'withdraw.withdraw_status','withdrawl_payment_method.payment_id','withdrawl_payment_method.name','withdrawl_payment_method.email','withdraw.created_at');
        $user->join('withdraw','withdraw.withdrawl_payment_id','=','withdrawl_payment_method.id');
        $user->join('users','users.id','=','withdrawl_payment_method.user_id');
       
        if($request->limit)
        {
            $user->offset($request->offset);
            $user->limit($request->limit);
        }
        $result = $user->get();
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' =>  $result], 200);

    }


    /* * Write code on Method
     *
     * @return response()
     */

    public function updateWithdrawlRequest(Request $request)
    {
        $validator = Validator::make($request->all(), ['status'=>'required','withdraw_id' => 'required','withdrawl_amount' => 'required','user_id' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

         #Update the withdraw
        if($request->status == 'APPROVED')
        {
             Withdraw::whereId($request->withdraw_id)->update([
            'withdraw_status' =>$request->status,
            
            ]);
        }else{
             $userBonusAmount = Wallet::where('user_id',$request->user_id)->first()->user_bonus_amount;
             $totalBonusAmount = $userBonusAmount + $request->withdrawl_amount;
              wallet::where('user_id',$request->user_id)->update([
            'user_bonus_amount' => $totalBonusAmount
        ]);
              Withdraw::whereId($request->withdraw_id)->update([
            'withdraw_status' =>$request->status,
            
            ]);

        }
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Success'], 200);


    }



public function updatePendingPayment(Request $request)
    {
        $validator = Validator::make($request->all(), ['ManualPaymentId'=>'required','type'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        if($request->type==1){
      $manualPayment=ManualPayment::where('ManualPaymentId',$request->ManualPaymentId)->first();
      $amount=$manualPayment->amount;
      $userId=$manualPayment->userId;
      $status=$manualPayment->status;
      $txnId=$manualPayment->transactionId;
      if($status==0){
          ManualPayment::where('ManualPaymentId',$request->ManualPaymentId)->update([
            'status' =>1,
            
            ]);
            Recharge::where('ManualPayment_Id',$request->ManualPaymentId)->update([
            'status' =>'SUCCESS',
            
            ]);
         $useramount=Wallet::where('user_id',$userId)->first();
         $oldpayment=$useramount->user_recharge_amount;
         $oldtotal=$useramount->total_amount;
         $newamount=$amount+$oldpayment;
         $newtotal=$oldtotal+$amount;
         Wallet::where('user_id',$userId)->update([
            'user_recharge_amount' =>$newamount,
            'total_amount'=>$newtotal
            
            ]);
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Success'], 200);
      }
      else{
          return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Success'], 200);
      }
    }
    else{
        ManualPayment::where('ManualPaymentId',$request->ManualPaymentId)->update([
            'status' =>2,
            
            ]);
            Recharge::where('ManualPayment_Id',$request->ManualPaymentId)->update([
            'status' =>'FAILURE',
            
            ]);
        
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Rejected'], 200);
    }
         #Update the withdraw
       


    }
    
      /**
     * getPhoneList
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getPhoneList(Request $request) 
    {
        
        $user = User::select('mobile','id');
        $user->where('deleted',0);


        if($request->search)
        {
            $user->where('mobile', "like", "%" . $request->search . "%");
        }

        $result =  $user->get();

        if (!$result->isEmpty()) 
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $result], 200);
        
        }else
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'message' => 'No Records Found', 'result' => $result], 200);
        
        }
        
    }
 public function getPendingamount(Request $request) 
    {
        $type=$request->type;
         $mobile=$request->mobile;
        //  $recharge = Recharge::query();
        // $recharge->with(['user' => function ($query) {
        //     $query->select('id', 'mobile');
        // }]);
         if(!empty($mobile)){
             $userdata=User::where('mobile','LIKE',"%{$mobile}%")->get();
             $iu=array();
             foreach ($userdata as $userss){
                 array_push($iu,$userss->id);
             }
              $user = ManualPayment::select('*')->whereIn('userId',$iu);
         }
         else{
        $user = ManualPayment::select('*');
         }
        if($type==1){
        $user->where('status',1);
        }
        elseif ($type==2) {
        $user->where('status',2);
        }
        elseif($type==0)
       {
     $user->where('status',0);
        
       }
       else{
           
       }
        

        //$result =  $user->leftjoin('users','users.id', '=', 'ManualPayment.userId')->where('countries.country_name', $country)->get();
        $result =  $user->join('users', 'users.id', '=', 'ManualPayment.userId')->get();
        if (!$result->isEmpty()) 
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $result], 200);
        
        }else
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'message' => 'No Records Found', 'result' => $result], 200);
        
        }
        
    }

       /**
     * getPhoneList
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function sentRewardAmount(Request $request) 
    {
        
        $validator = Validator::make($request->all(), ['id'=>'required','amount' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }


        $userBonusAmount = Wallet::where('user_id',$request->id)->first()->user_recharge_amount;
             $totalBonusAmount = $userBonusAmount + $request->amount;
              wallet::where('user_id',$request->id)->update([
            'user_recharge_amount' => $totalBonusAmount
        ]);

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Success'], 200);

        
    }


       /**
     * getPhoneList
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getTradeHistory(Request $request) 
    {
        
        $validator = Validator::make($request->all(), ['id'=>'required','from_date' => 'required','to_date' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $gamearray=array();

        $gameHistory = Game::where('user_id',$request->id);
        $gameHistory->whereBetween(DB::raw('DATE(`created_at`)'), [$request->from_date,$request->to_date]);
        //$gameHistory->whereDate('created_at',$request->date);
        if($request->limit)
        {
            $gameHistory->offset($request->offset);
            $gameHistory->limit($request->limit);
        }
        $result =  $gameHistory->get();
        foreach($result as $value)
        {
            $value->game_type = 'fast parity';
        }
        
       array_push($gamearray,$result);
        $gameHistory2 = WheelocityPlay::where('user_id',$request->id);
        $gameHistory2->whereBetween(DB::raw('DATE(`created_at`)'), [$request->from_date,$request->to_date]);
        //$gameHistory->whereDate('created_at',$request->date);
        if($request->limit)
        {
            $gameHistory2->offset($request->offset);
            $gameHistory2->limit($request->limit);
        }
        $result2 =  $gameHistory2->get();
        foreach($result2 as $value2)
        {
            $value2->game_type = 'Wheelocity';
        }
        array_push($gamearray,$result2);
        $gameHistory3 = HeadAndTailBetting::where('user_id',$request->id);
        $gameHistory3->whereBetween(DB::raw('DATE(`created_at`)'), [$request->from_date,$request->to_date]);
        
          $gameHistory3 = HeadAndTailBetting::where('user_id',$request->id);
        $gameHistory3->whereBetween(DB::raw('DATE(`created_at`)'), [$request->from_date,$request->to_date]);
        //$gameHistory->whereDate('created_at',$request->date);
        if($request->limit)
        {
            $gameHistory3->offset($request->offset);
            $gameHistory3->limit($request->limit);
        }
        $result3=  $gameHistory3->get();
        foreach($result3 as $value3)
        {
            $value3->game_type = 'HeadAndTail';
        }
    //     echo $result3;
    //   exit();
        array_push($gamearray,$result3);   
       if(!$result->isEmpty())
       {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'Success','result' => $gamearray], 200);
       }else{
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' =>  'No Records Found','result' => $gamearray], 200);
       }
        
    }


       /**
     * getPhoneList
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getHeadTailHistory(Request $request) 
    {
        
       
        $gameID  = HeadTail::orderBy('id','DESC')->first();
               $time= date('Y-m-d H:i:s', strtotime($gameID->created_at));
       $addtime= $newDate = date('Y-m-d H:i:s', strtotime($time. ' +1 minutes'));
       $currenttime=date('Y-m-d H:i:s');
      $remaningtime=strtotime($addtime)-strtotime($currenttime);
    $gameID=$gameID->head_tail_id;
     
        $orders= [];
       
        for($i= 0; $i <= 1; $i++)
        {
            if($i == 0) {
                $value = 'head';
            }else{
                $value = 'tail';
            }
            $game = HeadAndTailBetting::select( DB::raw('SUM(amount) AS totalAmount'))->where('value',$i)->where('game_id',$gameID)->get();
            $orders[] = array($value=> $i,'amount' => $game[0]['totalAmount'] ?? 0);
       
                 
        }

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'game_id' => $gameID, 'remaning_time'=>$remaningtime,'result' =>  $orders], 200);

       
        
    }


       /**
     * getPhoneList
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateHeadTailResult(Request $request) 
    {
        
       
       $validator = Validator::make($request->all(), ['period_id'=>'required','value' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $results = DB::select(
                DB::raw(
                    "SELECT play_value FROM head_tail_betting_count WHERE count =  0 AND play_id = '$request->period_id'"
                )
            );

           $checkBettingResult = HeadAndTailBettingResult::where('play_id',$request->period_id)->first();
           if(!$checkBettingResult)
           {
                if($results)
                {
                    $winnigUserData = HeadAndTailBetting::where("value", $request->value)
                        ->where("game_id", $request->period_id)
                        ->where("status", 2)
                        ->get();
                    if (!$winnigUserData->isEmpty()) {
                        foreach ($winnigUserData as $value) {
                            $winningAmount = ($value->amount * 90) / 100;
                            HeadAndTailBettingResult::create([
                            "ht_winning_amount" => $winningAmount,
                            "ht_betting_amount" => $value->amount,
                            "play_id" => $request->period_id,
                            "user_id" => $value->user_id,
                            "ht_winning_value" => $value->value,
                        ]);
                           

                            #Update betting Status
                            HeadAndTailBetting::where("value", $value->value)
                                ->where("game_id", $request->period_id)
                                ->update([
                                    "status" => 1,
                                ]);

                            HeadAndTailBetting::where("game_id", $request->period_id)->where("status", 2)
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

                            DB::table("head_tail_betting_count")
                            ->where("game_id", $request->period_id)
                            ->truncate();

                            HeadTail::create(["head_tail_id" => $request->period_id + 1]);
                            for ($i = 0; $i <= 1; $i++) {
                                $data = ["play_id" => $request->period_id + 1, "play_value" => $i, "count" => 0];
                                DB::table("head_tail_betting_count")->insert($data);
                            }
                        

                        }
                    }else{

                        HeadAndTailBettingResult::create([
                            "ht_winning_amount" => 0,
                            "ht_betting_amount" => 0,
                            "play_id" => $request->period_id,
                            "user_id" => 0,
                            "ht_winning_value" => $request->value,
                        ]);

                        HeadAndTailBetting::where("game_id", $request->period_id)->where("status", 2)
                                ->update([
                                    "status" => 0,
                                ]);

                         DB::table("head_tail_betting_count")
                            ->where("game_id", $request->period_id)
                            ->truncate();

                         HeadTail::create(["head_tail_id" => $request->period_id + 1]);
                            for ($i = 0; $i <= 1; $i++) {
                                $data = ["play_id" => $request->period_id + 1, "play_value" => $i, "count" => 0];
                                DB::table("head_tail_betting_count")->insert($data);
                            }
                       
                    }
                }
            }

            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Success'], 200);
       
        
    }


      /**
     * Write code on Method
     *
     * @return response()
     */
    public function updateThreeMinutParityResult(Request $request)
    {
        $validator = Validator::make($request->all(), ['game_id'=>'required','value' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $checkBettingResult = ThreeMinuteGamePlay::where(
                "game_id",
                $request->game_id
            )->first();
            if (!$checkBettingResult) {

                 $winnigUserData = ThreeMinuteGamePlay::where('value',$request->value)->where('game_id',$request->game_id)->where('status',0)->get();
                 if (!$winnigUserData->isEmpty()) 
                 {
                     foreach($winnigUserData as $value)
                     {
                        if($value->value == 11 || $value->value == 12 || $value->value == 13)
                        {
                            $winningAmount = ($value->amount * 50) / 100;
                        }else{
                            $winningAmount = ($value->amount * 90) / 100;
                        }
                        ThreeMinuteBettingResult::create(['winning_amount' => $winningAmount,'betting_amount' =>$value->amount,
                        'game_id' => $request->game_id ,'user_id' =>  $value->user_id,'winning_value' => $value->value]);

                          #Update betting Status
                        ThreeMinuteGamePlay::where('value',$request->value)->where('game_id',$request->game_id)->update([
                            'status' => 1
                        ]);

                        ThreeMinuteGamePlay::where("game_id", $request->game_id)->where("status", 2)
                        ->update([
                            "status" => 0,
                        ]); 

                        $totalBonusAmount = ThreeMinuteGamePlay::where('value',$request->value)->where('game_id',$request->game_id)->sum('amount');

                        $totalWalletAmount = Wallet::where('user_id',$value->user_id)->sum('user_bonus_amount');

                        $totalAmount = Wallet::where('user_id',$value->user_id)->sum('total_amount');
                       
                       
                        $winAmnt = $value->amount + $winningAmount;
                        $totalWinningAmount = $totalWalletAmount  + $winningAmount;
                        $finalAmount = $totalAmount  + $winningAmount;
                            Wallet::where('user_id',$value->user_id)->update([
                                'user_bonus_amount' =>$totalWinningAmount,
                                "total_amount" => $finalAmount
                            ]);


                        //GameID::whereDate('created_at',date('Y-m-d'))->truncate();
                        DB::table('betting_Value_count')->where('game_id', $request->game_id)->truncate();
                        ThreeMinuteGameID::create(["game_id" => $request->game_id + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $request->game_id + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table(
                                "three_minute_betting_Value_count"
                            )->insert($data);
                        }
                    }
                }else{
                    $storedValue ='0123456789';
                    $aRandomValue= $storedValue[rand(0,strlen($storedValue) - 1)];
                   
                    ThreeMinuteBettingResult::create(['winning_amount' => 0,'betting_amount' =>0,
                   'game_id' => $request->game_id ,'user_id' =>  0,'winning_value' => $request->value]);

                    ThreeMinuteGamePlay::where("game_id", $request->game_id)->where("status", 2)
                    ->update([
                        "status" => 0,
                    ]); 
                    ThreeMinuteGameID::create(["game_id" => $request->game_id + 1]);
                        for ($i = 1; $i <= 10; $i++) {
                            $data = [
                                "game_id" => $request->game_id + 1,
                                "value" => $i,
                                "count" => 0,
                            ];
                            DB::table(
                                "three_minute_betting_Value_count"
                            )->insert($data);
                        }
                }
            }
            // }else
            // {
            //      $storedValue ='0123456789';
            //      $aRandomValue= $storedValue[rand(0,strlen($storedValue) - 1)];
                   
            //      BettingResult::create(['winning_amount' => 0,'betting_amount' =>0,
            //        'game_id' => $request->game_id ,'user_id' =>  0,'winning_value' => $aRandomValue]);

            //      Game::where("game_id", $request->game_id)
            //         ->update([
            //             "status" => 0,
            //         ]); 

            // }
       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Success'], 200);

    }




public function updateTopupbalence(Request $request)
{
  //$user = $this->user;
        $validator = Validator::make($request->all(), ['amount'=>'required','user_id'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        } 
        // $walet= wallet::where('user_id',$request->user_id)->first();
        // $walet_recharge=$walet->user_recharge_amount;
        // $walet_bonus=$walet->user_bonus_amount;
        // $mainbalance=$request->amount-$walet->user_bonus_amount;
         wallet::where('user_id',$request->user_id)->update([
                    //'total_amount' => $request->amount,
                    'user_recharge_amount' => $request->amount
                ]);
                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => "Update Successfully"], 200);
}
public function updateWiningbalence(Request $request)
{
  //$user = $this->user;
        $validator = Validator::make($request->all(), ['amount'=>'required','user_id'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        } 
        // $walet= wallet::where('user_id',$request->user_id)->first();
        // $walet_recharge=$walet->user_recharge_amount;
        // $walet_bonus=$walet->user_bonus_amount;
        // $mainbalance=$request->amount-$walet->user_bonus_amount;
         wallet::where('user_id',$request->user_id)->update([
                    //'total_amount' => $request->amount,
                    'user_bonus_amount' => $request->amount
                ]);
                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => "Update Successfully"], 200);
}
public function updatewholocityResult(){
     $validator = Validator::make($request->all(), ['preiodid'=>'required','value'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        } 
        
        $checkBettingResult = WheelocityPlay::where(
                "game_id",
                $request->preiodid
            )->first();
        
}








  
}
