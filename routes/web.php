<?php

use Illuminate\Support\Facades\Route;

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
Route::post('/generate', "App\Http\Controllers\VideoController@generateBatmanVideoFromRequest")->name("generate");

Route::post("/listen", "App\Http\Controllers\TelegramController@listenMessages");
