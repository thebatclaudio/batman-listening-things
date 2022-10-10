<?php

namespace App\Http\Controllers;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Audio\CustomFilter;
use FFMpeg\Filters\Video\ClipFilter;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Http\Request;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Filters\Audio\SimpleFilter;

class VideoController extends Controller
{
    public function homepage()
    {
        return view("homepage");
    }

    public function generateBatmanVideo(Request $request)
    {
        $audioFile = $request->file("audio")->getRealPath();

        $ffprobe = \FFMpeg\FFProbe::create();
        $audioDuration = $ffprobe->format($audioFile)->get('duration');
        $videoDuration = $ffprobe->format(storage_path('app/batmanlistening.mp4'))->get('duration');

        $filename = "generated/batman-listening-" . now()->timestamp . "-" . random_int(0, 999999) . ".mp4";
        $tempfilename = "generated/tmp-batman-listening-" . now()->timestamp . "-" . random_int(0, 999999) . ".mp4";

        $ffmpeg = FFMpeg::open(['batmanlistening.mp4', 'batmanlistening.mp4']);

        if ($audioDuration < $videoDuration) {
            $clipFilter = [
                "start" => TimeCode::fromSeconds(0),
                "duration" => TimeCode::fromSeconds($audioDuration)
            ];

            $ffmpeg = FFMpeg::open('batmanlistening.mp4')->addFilter(new ClipFilter($clipFilter["start"], $clipFilter["duration"]))->export()
                ->inFormat(new \FFMpeg\Format\Video\X264)
                ->save("temp-video.mp4");
        } else {
            $loopTimes = (int)($audioDuration/$videoDuration) + 1;
            $inputFiles = array_fill(0, $loopTimes, 'batmanlistening.mp4');

            $ffmpeg = FFMpeg::open($inputFiles)->export()->inFormat(new \FFMpeg\Format\Video\X264)->concatWithTranscoding(true, false)->save($tempfilename);
        }

        $ffmpeg = FFMpeg::open($tempfilename);

        $ffmpeg
            ->addFilter(new SimpleFilter(["-i", $audioFile]))
            ->export()
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($filename);

        return response()->download(storage_path("app/$filename"));
    }
}
