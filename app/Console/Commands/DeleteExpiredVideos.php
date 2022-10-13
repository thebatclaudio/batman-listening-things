<?php

namespace App\Console\Commands;

use App\Models\GeneratedVideo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DeleteExpiredVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:delete {hours?} {prefix?}';

    private $hours;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete videos created before {X} hours';

    /**
     * Find list of expired videos
     */
    private function getVideos($date) {
      return GeneratedVideo::where('created_at', '>', $date);
    }

    /**
     * Delete videos from file system
     * @return void
     */
    private function deleteVideosFromFS($videos) {
        $paths = collect($videos)->each(function ($video) {
            $path = $this->argument('prefix').
                str_replace("\/", "/", $video->generated_video_filename);

            if (Storage::exists($path)) Storage::delete($path);
        });
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = Carbon::now()->subHours($this->hours)->toDateTimeString();
        $this->hours = $this->argument('hours');
        if (!$this->hours) {
          $this->hours = config('app.videos.lifetime');
        }
        $criteria = $this->getVideos($date);
        if (($numOfVideos = $criteria->count()) == 0) {
            $this->info("nothing to delete.");
            return Command::SUCCESS;
        }

        $this->info($numOfVideos . ' videos will be deleted.');
        $videos = $criteria->get();
        $this->deleteVideosFromFS($videos);
        $criteria->delete();

        return Command::SUCCESS;
    }
}
