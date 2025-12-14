<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            // video output settings
            $table->string('resolution')->default('1280x720')->after('status'); // e.g. 1280x720
            $table->integer('video_bitrate')->default(1500)->after('resolution'); // kbps
            $table->integer('audio_bitrate')->default(128)->after('video_bitrate'); // kbps
            $table->integer('fps')->default(25)->after('audio_bitrate'); // frames per second
            $table->string('audio_codec')->default('aac')->after('fps');

            // overlay settings
            $table->boolean('overlay_title')->default(true)->after('audio_codec'); // show movie title
            $table->boolean('overlay_timer')->default(true)->after('overlay_title'); // show remaining time

            // output paths (for encoded/hls files)
            $table->string('encoded_output_path')->nullable()->after('overlay_timer'); // e.g. /home/encoded/drama_tv
            $table->string('hls_output_path')->nullable()->after('encoded_output_path'); // e.g. /home/hls/drama_tv
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->dropColumn([
                'resolution',
                'video_bitrate',
                'audio_bitrate',
                'fps',
                'audio_codec',
                'overlay_title',
                'overlay_timer',
                'encoded_output_path',
                'hls_output_path',
            ]);
        });
    }
};
