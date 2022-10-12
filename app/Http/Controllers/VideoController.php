<?php

namespace App\Http\Controllers;

use App\VideoFormats\X265;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Audio\CustomFilter;
use FFMpeg\Filters\Video\ClipFilter;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Filters\Audio\SimpleFilter;

class VideoController extends Controller
{
    public function homepage(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view("homepage");
    }

    public function generateBatmanVideoFromRequest(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $audioFile = $request->file("audio")->getRealPath();

        return response()->download($this->generateBatmanVideo($audioFile));
    }

    public static function generateBatmanVideo(string $audioFile, bool $public_url = false): string
    {
        $ffprobe = \FFMpeg\FFProbe::create();
        $audioDuration = $ffprobe->format($audioFile)->get('duration');
        $videoDuration = $ffprobe->format(storage_path('app/batmanlistening.mp4'))->get('duration');

        $filename = "generated/batman-listening-" . now()->timestamp . "-" . random_int(0, 999999) . ".mp4";
        $tempfilename = "generated/tmp-batman-listening-" . now()->timestamp . "-" . random_int(0, 999999) . ".mp4";

        if ($public_url) {
            $filename = "public/" . $filename;
        }

        // if audio duration > video duration then generate a loop video, otherwise clip video to audio duration
        if ($audioDuration < $videoDuration) {
            $clipFilter = [
                "start" => TimeCode::fromSeconds(0),
                "duration" => TimeCode::fromSeconds($audioDuration)
            ];

            FFMpeg::open('batmanlistening.mp4')->addFilter(new ClipFilter($clipFilter["start"], $clipFilter["duration"]))->export()
                ->inFormat(new \FFMpeg\Format\Video\X264)
                ->save($tempfilename);
        } else {
            $loopTimes = (int)($audioDuration / $videoDuration) + 1;
            $inputFiles = array_fill(0, $loopTimes, 'batmanlistening.mp4');

            FFMpeg::open($inputFiles)->export()
                ->inFormat(new \FFMpeg\Format\Video\X264)
                ->concatWithTranscoding(true, false)
                ->save($tempfilename);
        }

        // open temp generated file and apply audio on it
        $ffmpeg = FFMpeg::open($tempfilename);
        $ffmpeg
            ->addFilter(new SimpleFilter(["-i", $audioFile]))
            ->export()
            ->inFormat(new X265)
            ->save($filename);

        $filesize = filesize(storage_path("app/" . $filename));

        $width = 500;
        $height = 376;
        while ($filesize > 10 * 1000 * 1000) {
            \Log::info("Filesize: " . $filesize);
            $width = $width / 2;
            $height = $height / 2;

            $filenameToResize = str_replace(".mp4", "-to-resize.mp4", $filename);
            Storage::move($filename, $filenameToResize);

            FFMpeg::open($filenameToResize)
                ->resize($width, $height)
                ->addFilter(new SimpleFilter(["-r", 5]))
                ->export()
                ->inFormat(new X265)
                ->save($filename);

            Storage::delete($filenameToResize);

            $filesize = filesize(storage_path("app/" . $filename));
        }

        // delete temp file
        Storage::delete($tempfilename);

        if ($public_url) {
            return Storage::url($filename);
        }

        return storage_path("app/$filename");
    }
}
