<?php

use Illuminate\Support\Facades\Route;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;

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

Route::post("/listen", function () {
    $config = [
        "telegram" => [
            "token" => env("TELEGRAM_BOT_TOKEN")
        ]
    ];

// Load the driver(s) you want to use
    DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);

// Create an instance
    $botman = BotManFactory::create($config);

// Give the bot something to listen for.
    $botman->hears('hello', function (BotMan $bot) {
        $bot->reply('Hello yourself.');
    });

// Start listening
    $botman->listen();
});
