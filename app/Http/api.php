<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\PaytmController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\MagixController;
use App\Http\Controllers\FastparittycronController;
use App\Http\Controllers\HeadAndTailController;
use App\Http\Controllers\WheelocityController;
use App\Http\Controllers\adminPanel\AdminController;
use App\Http\Controllers\adminPanel\SettingController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/sendOtp', [AuthController::class, 'sendOtp']);  
    Route::post('/forgetPassword', [AuthController::class, 'forgetPassword']);   
    Route::post('/resetPassword', [AuthController::class, 'resetPassword']);    
    Route::get('/callback', [PaytmController::class, 'paymentCallback']); 
    Route::get('/recharge/', [PaytmController::class, 'createPaytmOrder']); 
    Route::get('/getupino/', [PaytmController::class, 'getupino'])->middleware('auth:api'); 
    Route::post('/changeUserPassword', [UserController::class, 'changeUserPassword'])->middleware('auth:api');
    Route::post('/gamePlays', [GameController::class, 'gamePlays'])->middleware('auth:api');
    Route::get('/getGameID', [GameController::class, 'getGameID'])->middleware('auth:api');
    Route::post('/getBettingResult', [GameController::class, 'getBettingResult'])->middleware('auth:api');
    Route::get('/getWalletAmount', [GameController::class, 'getWalletAmount'])->middleware('auth:api');
    Route::post('/getUserReferHistory', [UserController::class, 'getUserReferHistory'])->middleware('auth:api');
    Route::post('/manualPayment', [UserController::class, 'manualPayment'])->middleware('auth:api');
    Route::post('/getOrders', [UserController::class, 'getOrders']);
    Route::get('/getUserDetail', [UserController::class, 'getUserDetail'])->middleware('auth:api');
    Route::post('/userProfileUpdate', [UserController::class, 'userProfileUpdate'])->middleware('auth:api');
    Route::post('/getgameIdDetail', [GameController::class, 'getgameIdDetail'])->middleware('auth:api');
    Route::patch('/fcm-token', [AuthController::class, 'updateToken'])->name('fcmToken');
    Route::post('/getUserRechargeHistory', [UserController::class, 'getUserRechargeHistory'])->middleware('auth:api');
    Route::post('/addUpiAddress', [PaytmController::class, 'addUpiAddress'])->middleware('auth:api');
    Route::post('/createPaytmWallet', [PaytmController::class, 'createPaytmWallet'])->middleware('auth:api');
    Route::post('/deleteUser', [UserController::class, 'deleteUser'])->middleware('auth:api');
    Route::get('/getWithdrawlPaymentMethod', [PaytmController::class, 'getWithdrawlPaymentMethod'])->middleware('auth:api');
    Route::post('/sentWithdrawlRequest', [PaytmController::class, 'sentWithdrawlRequest'])->middleware('auth:api');
    Route::post('/createMagixOrder', [MagixController::class, 'createMagixOrder'])->middleware('auth:api');
    Route::get('/magixCallback', [MagixController::class, 'magixCallback']);
    Route::get('/getHeadTailGameID', [HeadAndTailController::class, 'getHeadTailGameID'])->middleware('auth:api');
    Route::post('/headAndTailPlay', [HeadAndTailController::class, 'headAndTailPlay'])->middleware('auth:api');
    Route::post('/getHeadTailBettingResult', [HeadAndTailController::class, 'getHeadTailBettingResult'])->middleware('auth:api');
    Route::post('/getHeadTailOrders', [HeadAndTailController::class, 'getHeadTailOrders'])->middleware('auth:api');
    Route::post('/spinPlay', [SpinController::class, 'spinPlay'])->middleware('auth:api');
    Route::post('/getUserWithdrawlHistory', [UserController::class, 'getUserWithdrawlHistory'])->middleware('auth:api');
    Route::get('/getThreeMinuteGameID', [GameController::class, 'getThreeMinuteGameID'])->middleware('auth:api');
    Route::post('/getThreeMinuteOrders', [UserController::class, 'getThreeMinuteOrders'])->middleware('auth:api');
    Route::post('/wheelocityPlay', [WheelocityController::class, 'wheelocityPlay'])->middleware('auth:api');
    Route::get('/getWheelocityGameID', [WheelocityController::class, 'getWheelocityGameID'])->middleware('auth:api');
    Route::post('/getWheelocityOrders', [WheelocityController::class, 'getWheelocityOrders'])->middleware('auth:api');
    Route::post('/getWheelocityBettingResult', [WheelocityController::class, 'getWheelocityBettingResult'])->middleware('auth:api');
    Route::post('/updateWalletAmount', [UserController::class, 'updateWalletAmount'])->middleware('auth:api');
    Route::post('/updateWalletAmountplus', [UserController::class, 'updateWalletAmountplus'])->middleware('auth:api');
    Route::get('/sseevent', [GameController::class, 'sseevent']);
    Route::get('/getSseData', [GameController::class, 'getSseData']);
    Route::get('/getUpdatedData', [GameController::class, 'getUpdatedData']);
    Route::get('/listen', [GameController::class, 'listen']);
    Route::get('/getgameidcontroller', [FastparittycronController::class, 'getgameidcontroller']);
    Route::get('/getwhollecitycontroller', [FastparittycronController::class, 'getwhollecitycontroller']);
    Route::get('/threeminutegameidfromcron', [FastparittycronController::class, 'threeminutegameidfromcron']);
    Route::get('/createheadandtellgameid', [FastparittycronController::class, 'createheadandtellgameid']);
    
    Route::get('/getHeadTailGameIDLongpool', [HeadAndTailController::class, 'getHeadTailGameIDLongpool']);
    Route::get('/getWheelocityGameIDlongpool', [WheelocityController::class, 'getWheelocityGameIDlongpool']);
    Route::get('/getThreeMinuteGameIDLongpool', [GameController::class, 'getThreeMinuteGameIDLongpool']);
    
      
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'admin',
], function ($router) {
    Route::post('/adminLogin', [AdminController::class, 'adminLogin']);
    Route::get('/getUserList', [App\Http\Controllers\adminPanel\UserController::class, 'getUserList'])->middleware('auth:admin');
    Route::post('/getPendingamount', [App\Http\Controllers\adminPanel\UserController::class, 'getPendingamount'])->middleware('auth:admin');
     Route::post('/updateTopupbalence', [App\Http\Controllers\adminPanel\UserController::class, 'updateTopupbalence'])->middleware('auth:admin');
     Route::post('/updateWiningbalence', [App\Http\Controllers\adminPanel\UserController::class, 'updateWiningbalence'])->middleware('auth:admin');
     Route::post('/updateUserStatus', [App\Http\Controllers\adminPanel\UserController::class, 'updateUserStatus'])->middleware('auth:admin');
     Route::post('/admindeleteUser', [App\Http\Controllers\adminPanel\UserController::class, 'admindeleteUser'])->middleware('auth:admin');
    Route::get('/getUserRechargeList', [App\Http\Controllers\adminPanel\UserController::class, 'getUserRechargeList'])->middleware('auth:admin');
    Route::get('/getUserActivity', [App\Http\Controllers\adminPanel\UserController::class, 'getUserActivity'])->middleware('auth:admin');
    Route::post('/sendNotification', [App\Http\Controllers\adminPanel\UserController::class, 'sendNotification'])->middleware('auth:admin');
    Route::post('/getUserLoginActivity', [App\Http\Controllers\adminPanel\UserController::class, 'getUserLoginActivity'])->middleware('auth:admin');
    Route::post('/parityManagement', [App\Http\Controllers\adminPanel\UserController::class, 'parityManagement'])->middleware('auth:admin');
    Route::post('/updateParityResult', [App\Http\Controllers\adminPanel\UserController::class, 'updateParityResult'])->middleware('auth:admin');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->middleware('auth:admin');
    Route::get('/getWithdrawlList', [App\Http\Controllers\adminPanel\UserController::class, 'getWithdrawlList'])->middleware('auth:admin');
    Route::post('/updateWithdrawlRequest', [App\Http\Controllers\adminPanel\UserController::class, 'updateWithdrawlRequest'])->middleware('auth:admin');
    Route::post('/updatePendingPayment', [App\Http\Controllers\adminPanel\UserController::class, 'updatePendingPayment'])->middleware('auth:admin');
    Route::get('/getPhoneList', [App\Http\Controllers\adminPanel\UserController::class, 'getPhoneList'])->middleware('auth:admin');
    Route::post('/sentRewardAmount', [App\Http\Controllers\adminPanel\UserController::class, 'sentRewardAmount'])->middleware('auth:admin');
    Route::post('/gameSetting', [SettingController::class, 'gameSetting'])->middleware('auth:admin');
    Route::post('/apiKeySetting', [SettingController::class, 'apiKeySetting'])->middleware('auth:admin');
    Route::post('/sendNotification', [App\Http\Controllers\adminPanel\UserController::class, 'sendNotification'])->middleware('auth:admin');
     Route::post('/getTradeHistory', [App\Http\Controllers\adminPanel\UserController::class, 'getTradeHistory'])->middleware('auth:admin');
    //Route::get('/getTradeHistory', [App\Http\Controllers\adminPanel\UserController::class, 'getTradeHistory']);
    Route::post('/addMaintenanceMode', [SettingController::class, 'addMaintenanceMode'])->middleware('auth:admin');
    Route::get('/getSettings', [SettingController::class, 'getSettings'])->middleware('auth:admin');
    Route::post('/getHeadTailHistory', [App\Http\Controllers\adminPanel\UserController::class, 'getHeadTailHistory'])->middleware('auth:admin');
    Route::post('/updateHeadTailResult', [App\Http\Controllers\adminPanel\UserController::class, 'updateHeadTailResult']);
    Route::post('/updateThreeMinutParityResult', [App\Http\Controllers\adminPanel\UserController::class, 'updateThreeMinutParityResult'])->middleware('auth:admin');
    Route::post('/addGameResultSetting', [SettingController::class, 'addGameResultSetting'])->middleware('auth:admin');
    Route::post('/addupi', [SettingController::class, 'addupi'])->middleware('auth:admin');
    Route::get('/allupi', [SettingController::class, 'allupi'])->middleware('auth:admin');
    Route::post('/deleteupi', [SettingController::class, 'deleteupi'])->middleware('auth:admin');
   Route::get('/getamount', [HeadAndTailController::class, 'getamount']);


});
Route::get('test', function () {
    event(new App\Events\Test());
    return "Event has been sent!";
});