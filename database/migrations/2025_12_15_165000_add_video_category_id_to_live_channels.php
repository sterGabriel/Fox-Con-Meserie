<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (!Schema::hasColumn('live_channels', 'video_category_id')) {
                $table->unsignedBigInteger('video_category_id')->nullable()->after('name');
                $table->foreign('video_category_id')
                    ->references('id')
                    ->on('video_categories')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (Schema::hasColumn('live_channels', 'video_category_id')) {
                $table->dropForeign(['video_category_id']);
                $table->dropColumn('video_category_id');
            }
        });
    }
};
