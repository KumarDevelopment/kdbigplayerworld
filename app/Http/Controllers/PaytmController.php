<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use App\Models\Recharge;
use App\Models\Wallet;
use App\Models\Withdraw;
use App\Models\PaytmSettings;
use App\Models\GameSettings;
use App\Models\withdrawlPaymentMethod;
use PaytmWallet;
use GuzzleHttp\Client;
use Cache;
use Validator;
use Config;
use DB;
use URL;



require_once base_path() . '/Paytm_PHP_Checksum-master/PaytmChecksum.php';
use PaytmChecksum;

class PaytmController extends Controller
{

    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
        $this->middleware('auth:api', ['except' => ['paymentCallback','createPaytmOrder']]);

    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function createPaytmOrder(Request $request)
    {
        try
        {

            $validator = Validator::make($request->all(), ['id'=>'required','amount' => 'required']);
            if ($validator->fails()) {
                return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
            }

           
            $paytmMerchantData= PaytmSettings::where('type','PAYTM')->first();
            $paytmMerchantID  = $paytmMerchantData->key_id ?? env('PAYTM_MERCHANT_ID');
            $gameSettings= GameSettings::first();
            $minRecharge = $gameSettings->min_recharge ?? 100;
            
            if($minRecharge > $request->amount)
            {
                return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' =>'Invalid Amount' ], 400);
            }
            $paytmParams = array();
            $user = $this->user;
            $orderID = 'order_' . mt_rand() . $request->id;
            Cache::put('orderID', $orderID);
            $customerID = 'CUST_' . mt_rand() . $request->id;
            $callbackUrl = env('APP_URL').'paytmCallback';

            $paytmParams["body"] = array(
                "requestType" => "Payment",
                "mid" => $paytmMerchantID ,
                "websiteName" => env('PAYTM_MERCHANT_WEBSITE') ,
                "orderId" => $orderID,
                "channelId" => env('channelId') ,
                "callbackUrl" => $callbackUrl,
                "txnAmount" => array(
                    "value" => $request->amount,
                    "currency" => "INR",
                ) ,
                "userInfo" => array(
                    "custId" => $customerID,
                ) ,
                // "enablePaymentMode"=>array(
                //     "mode"=>"UPI",
                //     // "channels"=>array('UPI')
                //     )
            );

            Recharge::create(['user_id' => $request->id, 'amount' => $request->amount, 'customer_id' => $customerID, 'order_id' => $orderID]);

            /*
             * Generate checksum by parameters we have in body
             * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
            */

            $paytmMerchantKey = $paytmMerchantData->token ?? env('PAYTM_MERCHANT_KEY');
            $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES) ,$paytmMerchantKey );

            $paytmParams["head"] = array(
                "signature" => $checksum
            );

            $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
            // echo $post_data;
            // exit();

            /* for Staging */
            $url = env('PAYTM_BASE_URL') . "theia/api/v1/initiateTransaction?mid=" . $paytmMerchantID . "&orderId=" . $orderID . "";

            /* for Production */
            // $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=YOUR_MID_HERE&orderId=ORDERID_98765";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json"
            ));
            $response = curl_exec($ch);
            // echo $response;
            // exit();
            $result = json_decode($response,true);
            
            return $this->show($result, $orderID, json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES) , $checksum);

        }
        catch(\Exception $e)
        {

            return response()->json(['error' => $e->getMessage() ], 500);
        }

    }

    /**
     * Get a payment callback status
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function paymentCallback(Request $request)
    {
        try
        {

            $paytmMerchantData= PaytmSettings::where('type','PAYTM')->first();
            $paytmMerchantID  = $paytmMerchantData->key_id ?? env('PAYTM_MERCHANT_ID');
            $paytmMerchantKey = $paytmMerchantData->token ?? env('PAYTM_MERCHANT_KEY');
            $input = @file_get_contents("php://input");
            $gameSettings= GameSettings::first();
            
            @$ORDERID = Cache::get('orderID');
            
            $paytmParams = array();

            /* body parameters */
            $paytmParams["body"] = array(

                /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
                "mid" => $paytmMerchantID ,

                /* Enter your order id which needs to be check status for */
                "orderId" => $ORDERID,
            );

            /**
             * Generate checksum by parameters we have in body
             * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys
             */
            $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES) , $paytmMerchantKey);

            /* head parameters */
            $paytmParams["head"] = array(

                /* put generated checksum value here */
                "signature" => $checksum
            );

            /* prepare JSON string for request */
            $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

            /* for Staging */
            $url = env('PAYTM_STATUS_URL');

            /* for Production */
            // $url = "https://securegw.paytm.in/v3/order/status";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept:application/json'
            ));
            $response = curl_exec($ch);
            $result = json_decode($response, true);
           
            if ($result['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS')
            {
                Recharge::where('order_id', $ORDERID)->update(['txn_id' => $result['body']['txnId'], 'bank_txn_id' => $result['body']['bankTxnId'], 'status' => 'SUCCESS']);

                $recharge = Recharge::where('order_id', $ORDERID)->first();
                if($recharge)
                {
                    $checkUserWallet = Wallet::where('user_id',$recharge->user_id)->first();
                    if($checkUserWallet)
                    {
                        $totalAmount = (int)$checkUserWallet->user_recharge_amount + (int)$result['body']['txnAmount'];
                        $finalAmount = $checkUserWallet->total_amount + $result['body']['txnAmount'];
                        Wallet::where('user_id', $recharge->user_id)->update(['user_recharge_amount' => $totalAmount,'total_amount' => $finalAmount]);
                    }else{
                         // Share bonus amount
                         $shareReferalCode = User::where('id',$recharge->user_id)->first()->share_referral_code;
                         $shareUserID = User::where('referral_code',$shareReferalCode)->first()->id;
                         $checkShareUserWallet = Wallet::where('user_id',$shareUserID)->first();
                         if($checkShareUserWallet)
                         {
                            $totalAmount = $checkShareUserWallet->user_recharge_amount + $gameSettings->refer_bonus ?? 118;
                            $totalFinalAmount = $checkShareUserWallet->total_amount + $gameSettings->refer_bonus ?? 118;
                            Wallet::where('user_id', $shareUserID)->update(['user_recharge_amount' => $totalAmount,"total_amount" => $totalFinalAmount]);

                         }else{
                            
                            Wallet::create(['user_id' => $shareUserID,'user_recharge_amount' => '118','total_amount' => '118']);
                         }


                         Wallet::create(['user_id' => $recharge->user_id,'user_recharge_amount' => $result['body']['txnAmount']]);
                    }
                }
            }
            else
            {
                Recharge::where('order_id', $ORDERID)->update(['status' => 'FAILURE']);
            }
            
            return $this->paytmSuccessFailureResponse($response);

        }
        catch(\Exception $e)
        {

            return response()->json(['error' => $e->getMessage() ], 500);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function show($data, $orderID, $body, $checksum)
    {
        return view('/paytm')->with('data', $data)->with('orderID', $orderID);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function paytmSuccessFailureResponse($response)
    {
        $result = json_decode($response);
        return view('/response')->with('result', $result);
       
    }


     /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function addUpiAddress(Request $request)
    {
        $user = $this->user;
        $validator = Validator::make($request->all(), ['name'=>'required','email' => 'required|email','upi_address' =>'required',
            'confirm_upi_address' =>'required|same:upi_address']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $isExist = withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method','UPI_ADDRESS')->first();

        if(!$isExist)
        {
             $upiAddress = withdrawlPaymentMethod::create(['user_id' => $user->id, 'payment_method'=> 'UPI_ADDRESS','name' =>$request->name,'email' => $request->email ,'payment_id' => $request->upi_address]);

        }else{
             withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method','UPI_ADDRESS')->update(['user_id' => $user->id, 'payment_method'=> 'UPI_ADDRESS','name' =>$request->name,'email' => $request->email ,'payment_id' => $request->upi_address]);
             $upiAddress =  withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method','UPI_ADDRESS')->first();

        }

       


        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' =>  $upiAddress], 200);
       
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function createPaytmWallet(Request $request)
    {
        $user = $this->user;
        $validator = Validator::make($request->all(), ['name'=>'required','paytm_wallet' =>'required',
            'confirm_paytm_wallet' =>'required|same:paytm_wallet']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $isExist = withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method','PAYTM_WALLET')->first();

        if(!$isExist)
        {
              $upiAddress = withdrawlPaymentMethod::create(['user_id' => $user->id, 'payment_id'=> $request->paytm_wallet,'name' =>$request->name ,'payment_method'=> 'PAYTM_WALLET']);

        }else{
             withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method','PAYTM_WALLET')->update(['user_id' => $user->id, 'payment_id'=> $request->paytm_wallet,'name' =>$request->name ,'payment_method'=> 'PAYTM_WALLET']);
             $upiAddress =  withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method','PAYTM_WALLET')->first();

        }
       

        

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' =>  $upiAddress], 200);
       
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getWithdrawlPaymentMethod(Request $request)
    {
        $user = $this->user;

        $list  = withdrawlPaymentMethod::where('user_id',$user->id)->get();

        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Success','result' => $list], 200);

    }


     /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function sentWithdrawlRequest(Request $request)
    {
        $user = $this->user;

        $validator = Validator::make($request->all(), ['payment_method'=>'required','amount' =>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }


        $WithdrawAmount = env('WITHDRAW_AMOUNT');
       
        if($WithdrawAmount > $request->amount)
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' =>'Invalid Amount' ], 400);
        }

        $userBonusAmount = Wallet::where('user_id',$user->id)->first()->user_bonus_amount;

        
        if($userBonusAmount < $request->amount)
        {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' =>'Insufficient Amount' ], 400);
 
        }

       
        if($request->amount > env('INTEREST_AMOUNT'))
        {
            $interesetAmount = $request->amount - 30;
            $intereset = 30;
        }

        if($request->amount < env('INTEREST_AMOUNT'))
        {
            $getInterest = ($request->amount * 2) / 100;
            $interesetAmount = $request->amount - $getInterest;
            $intereset = $getInterest;
        }

         $AddInterestAmount =    $request->amount +  $intereset;
         $totalBonusAmount = $userBonusAmount  - $AddInterestAmount;

        $payment_id =  withdrawlPaymentMethod::where('user_id',$user->id)->where('payment_method',$request->payment_method)->first()->id; 
        $upiAddress = Withdraw::create(['user_id' => $user->id, 'withdrawl_payment_id'=> $payment_id,'withdraw_amount' => $interesetAmount,'withdraw_interest_amount' => $intereset ]);

         #Update the wallet amount
        wallet::where('user_id',$user->id)->update([
            'user_bonus_amount' => $userBonusAmount-$request->amount
        ]);



        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Success','result' => $upiAddress], 200);

    }


public function getupino(Request $request){
    $allupi=DB::table('upi')->inRandomOrder()->first();

             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' => $allupi], 200);
}






}

