<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedVideo extends Model
{
    use HasFactory;

    protected $table = "generated_videos";

    protected $fillable = [
        "audio_sha256", "generated_video_filename", "generated"
    ];

    public function scopeWithSha256(Builder $builder, string $sha256): Builder
    {
        return $builder->where("audio_sha256", $sha256);
    }
}
