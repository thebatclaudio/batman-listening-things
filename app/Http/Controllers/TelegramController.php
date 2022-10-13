<?php

namespace App\Http\Controllers;

use App\Models\GeneratedVideo;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
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
                $exploded = explode(".", $url);
                $extension = end($exploded);
                if ($extension === "oga") $extension = "ogg";

                $fileContent = file_get_contents($url);
                $tmpFilename = "tmp-audio-" . now()->timestamp . "-" . random_int(0, 999999) . "." . $extension;
                file_put_contents(storage_path("app/tmp-audio/$tmpFilename"), $fileContent);

                if (GeneratedVideo::withSha256(hash("sha256", $fileContent))->notGenerated()->exists()) {
                    $bot->reply("Wait... Batman is busy...");
                } else {
                    $bot->reply("Sending audio to Batman...");

                    $generatedVideoFilename = VideoController::generateBatmanVideo(storage_path("app/tmp-audio/$tmpFilename"), true);

                    // Create attachment
                    $attachment = new Video(env("APP_URL") . $generatedVideoFilename);

                    // Build message object
                    $message = OutgoingMessage::create('Batman is listening...')
                        ->withAttachment($attachment);

                    // Reply message object
                    $bot->reply($message);
                }
            }
        });

        // Start listening
        $bot->listen();
    }
}
