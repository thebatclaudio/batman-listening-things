<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('generated_videos', function (Blueprint $table) {
            $table->boolean("generated")->default(false)->after("generated_video_filename");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('generated_videos', function (Blueprint $table) {
            $table->dropColumn("generated");
        });
    }
};
