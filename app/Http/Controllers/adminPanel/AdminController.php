<?php

namespace App\Http\Controllers\adminPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Admin;
use App\Models\User;
use App\Models\Recharge;
use App\Models\Wallet;
use App\Models\Withdraw;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use Config;
use DB;

class AdminController extends Controller {

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */

    public function __construct() {
       $this->middleware('auth:admin', ['except' => ['adminLogin']]);

    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (! $token = auth::guard('admin')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized Admin'], 401);
        }
        return $this->createNewToken($token);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token) {
        $admin = auth::guard('admin')->user();
        $admin->access_token = $token;
        $admin->token_type = 'bearer';
        $admin->expires_in = auth::guard('admin')->factory()->getTTL() * 60;
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'admin' => $admin ]);
    }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard() {

           

        $user = user::select(DB::raw('COUNT(*) AS totalUserCount'))->get();
        $monthUser = user::select(DB::raw('COUNT(*) AS totalMonthCount'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->get();
        $monthRechargeUser = Recharge::select(DB::raw('SUM(amount) AS totalMonthUserRecharge'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->where('status','SUCCESS')
            ->get();
         $totalUser_user_recharge_amount=Wallet::sum('user_recharge_amount');
         $totalUser_user_bonus_amount=Wallet::sum('user_bonus_amount');
         $pendingWithdrowalAmount=Withdraw::where('withdraw_status','PENDING')->sum('withdraw_amount');
          $pendingRechargeAmount=Recharge::where('status','PENDING')->sum('amount');
         $pendingWithdrowalNumber=Withdraw::where('withdraw_status','PENDING')->count();
         $pendingRechargeNumber=Recharge::where('status','PENDING')->count();
         $monthWithdrawAmount = Withdraw::where('withdraw_status','ACCEPTED')->whereMonth('created_at', Carbon::now()->month)->sum('withdraw_amount');
         $monthWithdrawNumber = Withdraw::where('withdraw_status','ACCEPTED')->whereMonth('created_at', Carbon::now()->month)->count();
         $totalUser_user_balance=$totalUser_user_recharge_amount+$totalUser_user_bonus_amount;


        $val = json_encode(array(
               'totalUserCount' => $user[0]['totalUserCount'],
                'monthUserCount' => $monthUser[0]['totalMonthCount'],
                'monthyRecharge' => $monthRechargeUser[0]['totalMonthUserRecharge'],
                'totalUser_user_balance'=>$totalUser_user_balance,
                'pendingWithdrowalAmount'=>$pendingWithdrowalAmount,
                'pendingWithdrowalNumber'=>$pendingWithdrowalNumber,
                "pendingRechargeAmount"=>$pendingRechargeAmount,
                "pendingRechargeNumber"=>$pendingRechargeNumber,
                'monthWithdrawAmount'=>$monthWithdrawAmount,
                'monthWithdrawNumber'=>$monthWithdrawNumber
                
                
            ));

        $data = json_decode($val, true, JSON_UNESCAPED_SLASHES);

        
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' =>$data ]);
    }





    
}
