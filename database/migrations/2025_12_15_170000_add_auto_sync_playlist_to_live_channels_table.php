<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (!Schema::hasColumn('live_channels', 'auto_sync_playlist')) {
                $table->boolean('auto_sync_playlist')->default(false)->after('is_24_7_channel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (Schema::hasColumn('live_channels', 'auto_sync_playlist')) {
                $table->dropColumn('auto_sync_playlist');
            }
        });
    }
};
