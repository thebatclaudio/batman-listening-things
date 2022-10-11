<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

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

Route::get('/', "App\Http\Controllers\VideoController@homepage");
Route::post('/generate', "App\Http\Controllers\VideoController@generateBatmanVideo")->name("generate");

Route::get('/test', function () {
    $response = Telegram::getMe();

    $botId = $response->getId();
    $firstName = $response->getFirstName();
    $username = $response->getUsername();

    dd(compact("botId", "firstName", "username"));
});
