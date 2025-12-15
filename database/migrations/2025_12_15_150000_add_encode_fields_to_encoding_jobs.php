<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encoding_jobs', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('encoding_jobs', 'channel_id')) {
                $table->unsignedBigInteger('channel_id')->nullable()->after('live_channel_id');
            }
            if (!Schema::hasColumn('encoding_jobs', 'playlist_item_id')) {
                $table->unsignedBigInteger('playlist_item_id')->nullable()->after('video_id');
            }
            if (!Schema::hasColumn('encoding_jobs', 'input_path')) {
                $table->string('input_path')->nullable()->after('playlist_item_id');
            }
            if (!Schema::hasColumn('encoding_jobs', 'output_path')) {
                $table->string('output_path')->nullable()->after('input_path');
            }
            if (!Schema::hasColumn('encoding_jobs', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('finished_at');
            }
            if (!Schema::hasColumn('encoding_jobs', 'pid')) {
                $table->integer('pid')->nullable()->after('ffmpeg_command');
            }
            if (!Schema::hasColumn('encoding_jobs', 'log_path')) {
                $table->string('log_path')->nullable()->after('pid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('encoding_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'channel_id',
                'playlist_item_id',
                'input_path',
                'output_path',
                'completed_at',
                'pid',
                'log_path',
            ]);
        });
    }
};
