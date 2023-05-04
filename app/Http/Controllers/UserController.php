<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Game;
use App\Models\GameID;
use App\Models\BettingResult;
use App\Models\Recharge;
use App\Models\Withdraw;
use App\Models\Wallet;
use App\Models\withdrawlPaymentMethod;
use App\Models\ThreeMinuteBettingResult;
use App\Models\ThreeMinuteGamePlay;
use App\Models\ThreeMinuteGameID;
use App\Models\ManualPayment;
use GuzzleHttp\Client;
use Validator;
use Config;
use DB;


class UserController extends Controller {


    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    /**
     * changeUserPassword
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function changeUserPassword(Request $request) {
        
        $user = $this->user;
        $validator = Validator::make($request->all(), ['old_password'=>'required|string|min:6','new_password' => 'required|string|min:6','confirm_password' =>'required|same:new_password']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

         #Match The Old Password
        if(!Hash::check($request->old_password, $user->password)){
             return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Old Password Does not match! '], 500);
        }


        #Update the new Password
        User::whereId($user->id)->update([
            'password' => Hash::make($request->new_password)
        ]);
        
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Password changed successfully'], 201);
    }


    /**
     * getUserReferHistory
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserReferHistory(Request $request) {
        
        $user = $this->user;
        $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        
        $userReferHistory = User::select('users.name','users.mobile','users.share_referral_code','users.created_at','wallet.user_bonus_amount','users.id')
        ->leftjoin('wallet','wallet.user_id', '=', 'users.id')
        ->where('users.share_referral_code',$user->referral_code)
        ->orderBy('users.id','DESC')
        ->offset($request->offset)
        ->limit($request->limit)->get();

       
        if (!$userReferHistory->isEmpty()) {

            $ids = [];
            foreach($userReferHistory as $value)
            {
                array_push($ids, $value->id);
            }

            $arr = "'" . implode ( "', '", $ids ) . "'";
           $result = DB::select(DB::raw("SELECT * FROM `recharge`
                        WHERE user_id IN (".$arr.")
                        AND status = 'SUCCESS'
                        GROUP BY user_id;
                    "));
             $attempts = count($ids) - count($result);
             $success_refer = count($result);
  
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $userReferHistory,'success_refer' => $success_refer,'attempts' => $attempts], 200);

        }else {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $userReferHistory], 200);

        }
        
    }
    
    
    
    public function ManualPayment(Request $request) {
        date_default_timezone_set('Asia/Kolkata');
        $user = $this->user;
        $validator = Validator::make($request->all(), ['amount' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
       //$count= count($request()->allFiles());
        if($request->hasFile('screenshorts')){ 
        $image_path = $request->file('screenshorts')->store('images', 'public');
        $pathi= "https://win99x.com/public/storage/".$image_path;
        }
        else{
            $pathi='';
        }
        if(!empty($request->transactionId)){
            $transactionId=$request->transactionId;
        }
        else{
            $transactionId='';
        }
        $data = ManualPayment::create([
            'screenshorts' => $pathi,
            'userId'=>$user->id,
            'amount'=>$request->amount,
            'created_at'=>date('Y-m-d H:i:s'),
            'transactionId'=>$transactionId
            
        ]);
  if($data){
      $manualPayment=ManualPayment::where('ManualPaymentId',$data->id)->first();
      $rechargeCreate= Recharge::create([
            'user_id' => $user->id,
            'amount'=>$request->amount,
            'status'=>"PENDING",
            'created_at'=>date('Y-m-d H:i:s'),
            'txn_id'=>$request->transactionId,
            'mode'=>"UPI",
            'ManualPayment_Id'=>$data->id
            
        ]);
       $recharge = Recharge::where('user_id',$user->id)->orderBy('id','DESC')->get();

        
      
      return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'msg' => $recharge], 200);
  }
  else{
      return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'recharge fail'], 200);
  }
        
        // $userReferHistory = User::select('users.name','users.mobile','users.share_referral_code','users.created_at','wallet.user_bonus_amount','users.id')
        // ->leftjoin('wallet','wallet.user_id', '=', 'users.id')
        // ->where('users.share_referral_code',$user->referral_code)
        // ->orderBy('users.id','DESC')
        // ->offset($request->offset)
        // ->limit($request->limit)->get();

       
        // if (!$userReferHistory->isEmpty()) {

        //     $ids = [];
        //     foreach($userReferHistory as $value)
        //     {
        //         array_push($ids, $value->id);
        //     }

        //     $arr = "'" . implode ( "', '", $ids ) . "'";
        //   $result = DB::select(DB::raw("SELECT * FROM `recharge`
        //                 WHERE user_id IN (".$arr.")
        //                 AND status = 'SUCCESS'
        //                 GROUP BY user_id;
        //             "));
        //      $attempts = count($ids) - count($result);
        //      $success_refer = count($result);
  
        //      return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $userReferHistory,'success_refer' => $success_refer,'attempts' => $attempts], 200);

        // }else {
        //     return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $userReferHistory], 200);

        // }
        
    }


    /**
     * getUserReferHistory
     *
     * @return \Illuminate\Http\JsonResponse
     * type = 1 my order
     * type = 2 Everyones order
     * 
     */
    public function getOrders(Request $request) {

        $user = $this->user;
        $validator = Validator::make($request->all(), ['type' => 'required','offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        if($request->type == 1)
        {
            $orders = Game::select('game_betting.*','users.mobile');
            $orders->leftjoin('users','users.id','=','game_betting.user_id');
            $orders->where('game_betting.user_id', $user->id);
            if($request->date)
            {
                $orders->whereDate('game_betting.created_at', $request->date);
            }
            $orders->orderBy("game_betting.id", "DESC");
            $orders->offset($request->offset);
            $orders->limit($request->limit);
            $myORders = $orders->get();
            if(!$myORders->isEmpty())
            {
                foreach($myORders as $val)
                {
                    
                    $winningAmount = BettingResult::where('winning_value' ,$val->value)->where('user_id',$val->user_id)->where('game_id',$val->game_id)->first();

                    if(!$winningAmount)
                    {
                        $val->winning_amount = 0;
                    }else{
                        $val->winning_amount = $winningAmount->winning_amount;
                    }

                    if($val->value == 11)
                    {
                        $val->value = 'G';
                    }
                    if($val->value == 12)
                    {
                        $val->value = 'V';
                    }
                    if($val->value == 13)
                    {
                        $val->value = 'R';
                    }

                 
                   
                }
                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $myORders], 200);
                

             }else {

                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $myORders], 200);

             }
        }else{
            $orders= [];
            $gameID  = GameID::orderBy('id','DESC')->first()->game_id;
            for($i= 0; $i <= 50; $i++)
            {
                $storedValue ='12345678910';
                $aRandomValue= rand(1,10);
                $phone = '9009343783';
                $phone = sprintf('%s%04d', substr($phone, 0, -4), rand(0, 9999));
                $dateReplace = str_replace("-","",date('Y-m-d'));
                $date = $dateReplace.'00'.$i;
                $points = ($i == 0) ? 40 : 50*$i; //rand(50,200);
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




     /**
     * getUserList
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserDetail(Request $request) {
        
        $user = $this->user;
    
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $user], 200);
    }


     /**
     * userProfileUpdate
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function userProfileUpdate(Request $request) {
        
        $user = $this->user;
        $validator = Validator::make($request->all(), ['name'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

       
        #Update the profile
        User::whereId($user->id)->update([
            'name' =>$request->name,
            'address' => $request->address,
            'pincode' => $request->pincode,
        ]);
        
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Profile updated successfully'], 201);
    }


     /**
     * userProfileUpdate
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserRechargeHistory(Request $request) {
        
        $user = $this->user;
        $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

       
        #recharge list
        $recharge = Recharge::where('user_id',$user->id)->orderBy('id','DESC')->offset($request->offset)->limit($request->limit)->get();

         if (!$recharge->isEmpty()) {
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' => $recharge], 200);

        }else {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $recharge], 200);

        }
        
        
    }


    /**
     * userProfileUpdate
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function deleteUser(Request $request) {
        
        $user = $this->user;
         #Update the profile
        User::whereId($user->id)->update([
            'deleted' =>1,
            'mobile' => 'dlt_'.$user->mobile,
        ]);
        
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Profile deleted successfully'], 201);
    }


     /**
     * userProfileUpdate
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getUserWithdrawlHistory(Request $request) {
        
        $user = $this->user;
        $validator = Validator::make($request->all(), ['offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

       
        #recharge list
        $withdraw = withdrawlPaymentMethod::select('withdrawl_payment_method.payment_method','withdraw.withdraw_amount','withdraw.withdraw_status','withdraw.created_at')
        ->leftjoin('withdraw','withdraw.withdrawl_payment_id','=','withdrawl_payment_method.id')
        ->where('withdrawl_payment_method.user_id',$user->id)
        ->orderBy('withdrawl_payment_method.id','DESC')->offset($request->offset)->limit($request->limit)->get();

         if (!$withdraw->isEmpty()) {
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' => $withdraw], 200);

        }else {
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $withdraw], 200);

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
    public function getThreeMinuteOrders(Request $request) {

        $user = $this->user;
        $validator = Validator::make($request->all(), ['type' => 'required','offset'=>'required','limit' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        if($request->type == 1)
        {
            $orders = ThreeMinuteGamePlay::select('three_minute_game_betting.*','users.mobile');
            $orders->leftjoin('users','users.id','=','three_minute_game_betting.user_id');
            $orders->where('three_minute_game_betting.user_id', $user->id);
            if($request->date)
            {
                $orders->whereDate('three_minute_game_betting.created_at', $request->date);
            }
            $orders->orderBy("three_minute_game_betting.id", "DESC");
            $orders->offset($request->offset);
            $orders->limit($request->limit);
            $myORders = $orders->get();
            if(!$myORders->isEmpty())
            {
                foreach($myORders as $val)
                {
                    
                    $winningAmount = ThreeMinuteBettingResult::where('winning_value' ,$val->value)->where('user_id',$val->user_id)->where('game_id',$val->game_id)->first();

                    if(!$winningAmount)
                    {
                        $val->winning_amount = 0;
                    }else{
                        $val->winning_amount = $winningAmount->winning_amount;
                    }

                    if($val->value == 11)
                    {
                        $val->value = 'G';
                    }
                    if($val->value == 12)
                    {
                        $val->value = 'V';
                    }
                    if($val->value == 13)
                    {
                        $val->value = 'R';
                    }

                 
                   
                }
                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'result' => $myORders], 200);
                

             }else {

                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'No Records Found','result' => $myORders], 200);

             }
        }else{
            $orders= [];
            $gameID  = ThreeMinuteGameID::orderBy('id','DESC')->first()->game_id;
            for($i= 0; $i <= 50; $i++)
            {
                $storedValue ='12345678910';
                $aRandomValue= rand(1,10);
                $phone = '9009343783';
                $phone = sprintf('%s%04d', substr($phone, 0, -4), rand(0, 9999));
                $dateReplace = str_replace("-","",date('Y-m-d'));
                $date = $dateReplace.'00'.$i;
                $points = ($i == 0) ? 40 : 50*$i; //rand(50,200);
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


    public function updateWalletAmount(Request $request)
    {
        $user = $this->user;
        $validator = Validator::make($request->all(), ['amount'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }


        #check wallet amount
        $walletAmount = wallet::where('user_id',$user->id)->first();
       
        if($walletAmount->total_amount < $request->amount){
             return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'The amount is not valid'], 500);
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

          return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => "Update Successfully"], 200);

    }
    
    public function updateWalletAmountplus(Request $request){
        $user = $this->user;
        $validator = Validator::make($request->all(), ['amount'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
         $walletAmount = wallet::where('user_id',$user->id)->first();
          $finalTotalAmount = $walletAmount->total_amount+$request->amount;
            $finalAmount = $walletAmount->user_recharge_amount + $request->amount;
           wallet::where('user_id',$user->id)->update([
                    'total_amount' => $finalTotalAmount,
                    'user_recharge_amount' => $finalAmount
                ]);
                 return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => "Update Successfully"], 200);
    }




}
