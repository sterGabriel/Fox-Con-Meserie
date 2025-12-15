<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('encode_profiles', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('name')->unique(); // "720p FAST", "1080p BALANCED", etc
            $table->enum('type', ['h264_cpu', 'h265_cpu', 'h264_nvenc', 'h265_nvenc'])->default('h264_cpu');
            $table->text('description')->nullable();

            // Video properties
            $table->unsignedSmallInteger('width')->nullable(); // null = keep source
            $table->unsignedSmallInteger('height')->nullable();
            $table->enum('fps_mode', ['source', 'cfr'])->default('source');
            $table->unsignedSmallInteger('fps')->nullable(); // 25, 30, 60, etc
            $table->unsignedSmallInteger('video_bitrate_k')->default(4500); // kbps
            $table->unsignedSmallInteger('maxrate_k')->default(5400); // 120% of bitrate
            $table->unsignedSmallInteger('bufsize_k')->default(10800); // 2x maxrate

            // Encoding options
            $table->decimal('crf', 3, 1)->nullable(); // 0-51 for x264/x265 (lower = better)
            $table->string('preset')->default('medium'); // veryfast/fast/medium/slow or p1-p7
            $table->enum('profile', ['baseline', 'main', 'high'])->default('high');
            $table->string('pix_fmt')->default('yuv420p');
            $table->unsignedSmallInteger('gop')->default(50); // keyframe interval

            // Audio
            $table->enum('audio_codec', ['aac', 'copy'])->default('aac');
            $table->unsignedSmallInteger('audio_bitrate_k')->default(128);
            $table->unsignedTinyInteger('audio_channels')->default(2);

            // Container & extra
            $table->enum('container', ['mp4', 'mkv', 'mpegts'])->default('mp4');
            $table->text('extra_ffmpeg')->nullable(); // custom args
            $table->boolean('is_system')->default(true); // system preset vs user-created

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encode_profiles');
    }
};
