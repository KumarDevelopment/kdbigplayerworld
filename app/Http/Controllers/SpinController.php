<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use App\Models\Spin;
use Validator;
use DB;
use URL;
use Config;


class SpinController extends Controller
{

    protected $user;

    public function __construct()
    {
       $this->user = auth()->user();
    }


    /** gamePlay
     *
     * @return \Illuminate\Http\JsonResponse
    */

    public function spinPlay(Request $request) {
      $user = $this->user;
      $storedValue ='0123456789';
      $aRandomValue= $storedValue[rand(0,strlen($storedValue) - 1)];
      $SixDigitRandomNumber = rand(100000,999999);
      $validator = Validator::make($request->all(), ['value'=>'required']);
      if ($validator->fails()) {
          return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
      }

      
      // Check status
      $existData = Spin::where('user_id',$user->id)->where('status',0)->first();
      if($existData)
      {
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'spin'], 201);
       
      }else
      {
        $spin = Spin::create(['user_id' => $user->id, 'value'=> $request->value,'spin_id' => $SixDigitRandomNumber,'status' => 0]);
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'), 'message' => 'Game successfully registered', 'result' => $spin], 201);
      }
      
    }

   

}

