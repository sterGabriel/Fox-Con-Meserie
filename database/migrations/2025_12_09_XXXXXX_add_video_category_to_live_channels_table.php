<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->string('video_category')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->dropColumn('video_category');
        });
    }
};
