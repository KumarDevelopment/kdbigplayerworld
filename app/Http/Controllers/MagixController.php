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
use App\Models\withdrawlPaymentMethod;
use PaytmWallet;
use GuzzleHttp\Client;
use Validator;
use Config;
use DB;
use URL;

class MagixController extends Controller
{

    protected $user;

    public function __construct()
    {

        $this->user = auth()->user();
       $this->middleware('auth:api', ['except' => ['magixCallback']]);

       
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function createMagixOrder(Request $request)
    {
        try
        {

            $validator = Validator::make($request->all(), ['pay_name'=>'required','pay_phone' => 'required','pay_amount' =>'required']);
              if ($validator->fails()) {
                  return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
              }
            $user = $this->user;
            $magixData= PaytmSettings::where('type','MAGIX')->first();
            $magixKey = $magixData->key_id ?? env('MAGIX_KEY');
            $magixToken = $magixData->token ?? env('MAGIX_TOKEN');
    
             # API URL
            $url="https://magixapi.com/upi_payment_gateway/upipay.php";
            $orderID = 'order_' . mt_rand() . $user->id;
            # Put the data into an array
            $data = array(
            "accountID" => $magixKey ,
            "token" => $magixToken,
            "pay_id" => 'PAYMGX' . mt_rand() . $user->id,
            "pay_name"=> $request->pay_name,
            "pay_phone"=> $request->pay_phone,
            "pay_amount"=> $request->pay_amount
          );


          # Initialiaze the curl
          $ch = curl_init( $url );

          # Setup request to send json via POST.
          $payload = json_encode( array( "pay_request"=> $data ) );

          curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
          curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

          # Return response instead of printing.
          curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

          # Send request.
          $result = curl_exec($ch);

          curl_close($ch);

          # Convert the json response into array
          $data_result = json_decode($result, true);
          Recharge::create(['user_id' => $user->id, 'amount' => $request->pay_amount, 'customer_id' => $data['pay_id'], 'order_id' => $orderID]);


         return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' =>  $data_result], 200);
       
        
           
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

    public function magixCallback(Request $request)
    {
        try
        {
            $data_api_resp = json_decode(file_get_contents('php://input'), true);

            DB::insert('insert into babk_response (response) values (?)', [$data_api_resp['api_response']['pay_status']]);
            return;

           
        }
        catch(\Exception $e)
        {

            return response()->json(['error' => $e->getMessage() ], 500);
        }
    }


}

