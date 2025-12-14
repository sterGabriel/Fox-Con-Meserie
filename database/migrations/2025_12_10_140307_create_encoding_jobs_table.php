<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encoding_jobs', function (Blueprint $table) {
            $table->id();

            // Canalul pentru care se encodează
            $table->unsignedBigInteger('live_channel_id');

            // Video-ul (sură) care se encodează
            $table->unsignedBigInteger('video_id');

            // pending, running, done, failed
            $table->string('status')->default('pending');

            // progres 0–100 (opțional, pentru viitor)
            $table->unsignedTinyInteger('progress')->default(0);

            // unde a fost salvat rezultatul (TS/HLS sau folder)
            $table->string('output_path')->nullable();

            // setările folosite la encodare (rezoluție, bitrate, logo, etc) ca JSON
            $table->json('settings')->nullable();

            // timpi pentru debugging
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // mesaj de eroare, dacă ffmpeg crapă
            $table->text('error_message')->nullable();

            $table->timestamps();

            // indexuri pentru performanță
            $table->index('live_channel_id');
            $table->index('video_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encoding_jobs');
    }
};
