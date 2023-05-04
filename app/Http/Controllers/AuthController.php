<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\UserLoginActivity;
use App\Models\GameSettings;
use App\Models\Wallet;
use GuzzleHttp\Client;
use Validator;
use Config;

class AuthController extends Controller {

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */

    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'sendOtp','forgetPassword','resetPassword']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request) {
        $validator = Validator::make($request->all(), ['mobile' => 'required|numeric|digits:10', 'password' => 'required|string|min:6', ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Invalid User'], 404);
        }
        return $this->createNewToken($token,$request->fcmToken);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function register(Request $request) {
        $validator = Validator::make($request->all(), ['mobile' => 'required|numeric|digits:10|unique:users', 'code' => 'required|numeric|digits:6', 'password' => 'required|string|confirmed|min:6', 'name' => 'nullable|string|between:2,100', 'email' => 'nullable|string|email|max:100|unique:users', 'share_referral_code' => 'nullable']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        /*if(bcrypt($request->code) != $request->cryptOtp){
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Invalid Otp'], 400);
        }*/
       
         $joining_bonus=0;

        // Check referral code is exist or not
        if(isset($request->share_referral_code))
        {
             $gameSettings=GameSettings::first();
            $isExist = User::where('referral_code', $request->share_referral_code)->first();
            if (!$isExist) {
                return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Invalid Refer code'], 400);
            }
            else{
                $idUser=$isExist->id;
               
                $referalBonus=$gameSettings->refer_bonus;
                $joining_bonus=$gameSettings->joining_bonus;
                $referWalet=Wallet::where('user_id',$idUser)->first();
                $topbalance=$referWalet->user_recharge_amount;
                $winingbalance=$referWalet->user_bonus_amount;
                $newtopbalance=$topbalance+$referalBonus;
                $totalbalance=$newtopbalance+$winingbalance;
                Wallet::where('user_id',$idUser)->update(['user_recharge_amount'=>$newtopbalance,'total_amount'=>$totalbalance]);
                
                
                
            }
        }
       
        $referralCode = generateReferralCode($request->mobile);
        $user = User::create(array_merge($validator->validated(), ['password' => bcrypt($request->password), 'referral_code' => $referralCode]));
        Wallet::create(['user_id' => $user->id,'user_recharge_amount' => $joining_bonus,'total_amount' => $joining_bonus]);

        if($request->fcmToken)
        {
            User::whereId($user->id)->update([
            'fcmToken' => $request->fcmToken
            ]);
        }
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'User successfully registered', 'user' => $user], 201);
    }

    /**
     * send otp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request) {
        $validator = Validator::make($request->all(), ['mobile' => 'required|numeric|digits:10', ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $otp = rand(100000, 999999);
        $fields = array("sender_id" => "FSTSMS", "message" => "Your verification code is " . $otp . "", "language" => "english", "route" => "p", "numbers" => $request->mobile, "flash" => "1");
        $headers = ['authorization' => 'LN1sEYHDfwdqHrYQWNHqenu63d2uQIj7hkqm30HuSVO7Cuwtt5jgA8PftQV3', 'Accept' => 'application/json', ];
        $client = new client();
        $res = $client->post('https://www.fast2sms.com/dev/bulk', ['headers' => $headers, 'json' => $fields, ]);
        $res->getStatusCode();
        // "200"
        $res->getHeader('content-type') [0];
        // 'application/json; charset=utf8'
        $data = $res->getBody();
        // Converts it into a PHP object
        $data = json_decode($data);
        // $data->code = bcrypt($otp);
        $data->code = $otp;
         // Check mobile num code is exist or not
        $isExist = User::where('mobile', $request->mobile)->select('id','mobile')->first();
        if($isExist)
        {
            $data->user_id =  $isExist->id;
        }
        return response()->json(['result' => $data]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token,$fcmToken= null) {
        $user = auth()->user();
        $user->access_token = $token;
        $user->token_type = 'bearer';
        $user->expires_in = auth()->factory()->getTTL() * 60;
        if($fcmToken)
        {
            User::whereId($user->id)->update([
            'fcmToken' => $fcmToken
            ]);
        }
        UserLoginActivity::create(['user_id' => $user->id,'last_login_at' => now(),'last_login_ip' => request()->getClientIp()]);
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'user' => $user ]);
    }

     /**
     * forgetPassword
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function forgetPassword(Request $request) {
    
        // Check mobile num code is exist or not
        $isExist = User::where('mobile', $request->mobile)->select('id','mobile')->first();
        if (!$isExist) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => 'Invalid Mobile Number'], 400);
        }else{
            $this->sendOtp($request);
        }
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'OTP sent successfully','result' => $isExist], 201);
    }


     /**
     * forgetPassword
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function resetPassword(Request $request) {
    
        $validator = Validator::make($request->all(), ['password' => 'required|string|confirmed|min:6']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $password = bcrypt($request->password);
        $user = User::where('id', $request->user_id)->first();
        $user->password = $password;
        $user->save();
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Password reset successfully'], 201);
    }
}
