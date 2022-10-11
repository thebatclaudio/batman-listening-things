<?php

namespace App\Http\Controllers;

use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;

class TelegramController extends Controller
{
    public function listenMessages()
    {
        $config = [
            "telegram" => [
                "token" => env("TELEGRAM_TOKEN")
            ]
        ];

        // Load the driver(s) you want to use
        DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);

        // Create an instance
        $bot = BotManFactory::create($config);

        $bot->receivesAudio(function ($bot, $audios) {

            foreach ($audios as $audio) {
                $url = $audio->getUrl(); // The original url
                $filename = "tmp-audio-" . now()->timestamp . "-" . random_int(0, 999999) . ".ogg";
                file_put_contents(storage_path("app/tmp-audio/$filename"), file_get_contents($url));

                $video = VideoController::generateBatmanVideo(storage_path("app/tmp-audio/$filename"), true);

                // Create attachment
                $attachment = new Video(env("APP_URL").$video);

                // Build message object
                $message = OutgoingMessage::create('Here it is')
                    ->withAttachment($attachment);

                // Reply message object
                $bot->reply($message);
            }
        });

        // Start listening
        $bot->listen();
    }
}
