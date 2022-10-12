<?php

namespace App\Http\Controllers;

use App\Models\GeneratedVideo;
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
            $bot->reply("Sending audio to Batman...");

            foreach ($audios as $audio) {
                $url = $audio->getUrl(); // The original url

                \Log::info("File url: " . $url);

                $exploded = explode(".", $url);
                $extension = end($exploded);

                \Log::info("File extension: " . $extension);

                $fileContent = file_get_contents($url);

                $audioSha256 = hash("sha256", $fileContent);

                if ($generatedVideo = GeneratedVideo::withSha256($audioSha256)->first()) {
                    $generatedVideoFilename = $generatedVideo->generated_video_filename;
                } else {
                    $tmpFilename = "tmp-audio-" . now()->timestamp . "-" . random_int(0, 999999) . "." . $extension;
                    file_put_contents(storage_path("app/tmp-audio/$tmpFilename"), $fileContent);

                    $generatedVideoFilename = VideoController::generateBatmanVideo(storage_path("app/tmp-audio/$tmpFilename"), true);

                    GeneratedVideo::create([
                        "audio_sha256" => $audioSha256,
                        "generated_video_filename" => $generatedVideoFilename
                    ]);
                }

                // Create attachment
                $attachment = new Video(env("APP_URL") . $generatedVideoFilename);

                // Build message object
                $message = OutgoingMessage::create('Batman is listening...')
                    ->withAttachment($attachment);

                // Reply message object
                $bot->reply($message);
            }
        });

        // Start listening
        $bot->listen();
    }
}
