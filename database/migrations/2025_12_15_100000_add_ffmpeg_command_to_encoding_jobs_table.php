<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encoding_jobs', function (Blueprint $table) {
            $table->text('ffmpeg_command')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('encoding_jobs', function (Blueprint $table) {
            $table->dropColumn('ffmpeg_command');
        });
    }
};
