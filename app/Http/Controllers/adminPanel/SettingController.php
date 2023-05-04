<?php

namespace App\Http\Controllers\adminPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\GameSettings;
use App\Models\PaytmSettings;
use App\Models\MaintanceModeSettings;
use App\Models\GameResultSetting;
use App\Models\Upi;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use Config;
use DB;

class SettingController extends Controller {

   
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function gameSetting(Request $request) {
       $validator = Validator::make($request->all(), ['min_recharge'=>'required','refer_bonus' => 'required','joining_bonus'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $isExist = GameSettings::first();

        if(!$isExist)
        {
             $GameSettings = GameSettings::create(['min_recharge' => $request->min_recharge, 'refer_bonus'=> $request->refer_bonus]);

        }else{
            
             GameSettings::where('id',$isExist->id)->update(['min_recharge' => $request->min_recharge, 'refer_bonus'=> $request->refer_bonus,'joining_bonus'=>$request->joining_bonus]);
             $GameSettings =  GameSettings::first();

        }

       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' =>  $GameSettings], 200);
    }


     /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiKeySetting(Request $request) {
       $validator = Validator::make($request->all(), ['id'=>'required','token' => 'required','type' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        $isExist = PaytmSettings::where('type', $request->type)->first();

        if(!$isExist)
        {
             $PaytmSettings = PaytmSettings::create(['key_id' => $request->id, 'token'=> $request->token,'type' => $request->type ]);

        }else{
             PaytmSettings::where('type', $request->type)->update(['key_id' => $request->id, 'token'=> $request->token]);
             $PaytmSettings =  PaytmSettings::where('type', $request->type)->first();

        }

       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' =>  $PaytmSettings], 200);
    }


     /* Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMaintenanceMode(Request $request) {
       $validator = Validator::make($request->all(), ['type'=>'required','value' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }

        
        $type = explode(',', $request->type);
       
        foreach($type as $maintainaceType)
        {
            $isExist = MaintanceModeSettings::where('type', $maintainaceType)->get();
             
            if($isExist->isEmpty())
            {
               
                 $maintenanceNode = MaintanceModeSettings::create(['value'=> $request->value,'type' => $maintainaceType ]);

            }else{
                 MaintanceModeSettings::where('type', $maintainaceType)->update(['value'=> $request->value,'type' => $maintainaceType ]);
            }

        }
        

       
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Success'], 200);
    }

     /* Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings(Request $request) {
       

        $gameSettings = GameSettings::all();
        $keySettings = PaytmSettings::all();
        $maintanceSettings = MaintanceModeSettings::all();
        $gameResultSetting = GameResultSetting::all();
        return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'gameSettings' =>  $gameSettings,'keySettings' => $keySettings,'maintanceSettings' => $maintanceSettings,'gameResultSetting'=>$gameResultSetting ], 200);
    }



     /* Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addGameResultSetting(Request $request) {
       $validator = Validator::make($request->all(), ['type'=>'required','value' => 'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        foreach ($request->type as $type){

         $isExist = GameResultSetting::where('game_type', $type)->get();
             
            if($isExist->isEmpty())
            {
               
                 $maintenanceNode = GameResultSetting::create(['win_result'=> $request->value,'game_type' => $type ]);

            }else{
                 GameResultSetting::where('game_type', $type)->update(['win_result'=> $request->value,'game_type' =>$type]);
            }
          }
             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Success'], 200);
        }

      

public function addupi(Request $request){
    
    $validator = Validator::make($request->all(), ['upi_id'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $data=Upi::create(['upi_id'=>$request->upi_id]);
        if($data){
            $allupi=Upi::orderBy('id', 'DESC')->get();

             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' => $allupi], 200);
        }
        else{
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Fails'], 200);
        }
}


public function allupi(Request $request){
    
   
      
        
            $allupi=Upi::orderBy('id', 'DESC')->get();

             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' => $allupi], 200);
        
}
public function deleteupi(Request $request){
    
    $validator = Validator::make($request->all(), ['id'=>'required']);
        if ($validator->fails()) {
            return response()->json(['responseCode' => Config::get('constant.responseCode.exception'), 'showMessage' => Config::get('constant.showMessage.doNotShow'), 'statusCode' => Config::get('constant.statusCode.fail'), 'message' => $validator->errors()->messages() ], 400);
        }
        $data=Upi::where('id',$request->id)->delete();
        if($data){
            //$allupi=Upi::orderBy('id', 'DESC')->get();

             return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'result' => 'Success'], 200);
        }
        else{
            return response()->json(['responseCode' => Config::get('constant.responseCode.success'), 'showMessage' => Config::get('constant.showMessage.show'), 'statusCode' => Config::get('constant.statusCode.success'),'messages' => 'Fails'], 200);
        }
}

  
}
