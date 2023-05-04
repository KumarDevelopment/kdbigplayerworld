<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BroadcastController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('paytm', function () {
    return view('paytm');
});

Route::get('response', function () {
    return view('response');
});
Route::get('/broadcast', [BroadcastController::class, 'broadcast']);

Route::post('paytmCallback', [App\Http\Controllers\PaytmController::class, 'paymentCallback'])->name('paytmCallback');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
