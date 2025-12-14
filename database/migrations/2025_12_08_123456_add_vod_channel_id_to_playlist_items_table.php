<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlist_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vod_channel_id')->after('id');

            $table->foreign('vod_channel_id')
                ->references('id')
                ->on('live_channels')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('playlist_items', function (Blueprint $table) {
            $table->dropForeign(['vod_channel_id']);
            $table->dropColumn('vod_channel_id');
        });
    }
};
