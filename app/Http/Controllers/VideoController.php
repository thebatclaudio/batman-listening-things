<?php

namespace App\Http\Controllers;

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

        $filename = "generated/batman-listening-" . now()->timestamp . "-" . random_int(0, 999999) . ".webm";
        $tempfilename = "generated/tmp-batman-listening-" . now()->timestamp . "-" . random_int(0, 999999) . ".webm";

        if($public_url) {
            $filename = "public/".$filename;
        }

        // if audio duration > video duration then generate a loop video, otherwise clip video to audio duration
        if ($audioDuration < $videoDuration) {
            $clipFilter = [
                "start" => TimeCode::fromSeconds(0),
                "duration" => TimeCode::fromSeconds($audioDuration)
            ];

            FFMpeg::open('batmanlistening.mp4')->addFilter(new ClipFilter($clipFilter["start"], $clipFilter["duration"]))->export()
                ->inFormat(new \FFMpeg\Format\Video\WebM)
                ->save($tempfilename);
        } else {
            $loopTimes = (int)($audioDuration / $videoDuration) + 1;
            $inputFiles = array_fill(0, $loopTimes, 'batmanlistening.mp4');

            FFMpeg::open($inputFiles)->export()
                ->inFormat(new \FFMpeg\Format\Video\WebM)
                ->concatWithTranscoding(true, false)
                ->save($tempfilename);
        }

        // open temp generated file and apply audio on it
        $ffmpeg = FFMpeg::open($tempfilename);
        $ffmpeg
            ->addFilter(new SimpleFilter(["-i", $audioFile]))
            ->export()
            ->inFormat(new \FFMpeg\Format\Video\WebM)
            ->save($filename);

        // delete temp file
        Storage::delete($tempfilename);

        if($public_url) {
            return Storage::url($filename);
        }

        return storage_path("app/$filename");
    }
}
