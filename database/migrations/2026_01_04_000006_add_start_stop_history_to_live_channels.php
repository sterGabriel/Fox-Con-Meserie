<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (!Schema::hasColumn('live_channels', 'last_started_at')) {
                $table->timestamp('last_started_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('live_channels', 'last_stopped_at')) {
                $table->timestamp('last_stopped_at')->nullable()->after('last_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('live_channels', 'last_started_at')) {
                $drop[] = 'last_started_at';
            }
            if (Schema::hasColumn('live_channels', 'last_stopped_at')) {
                $drop[] = 'last_stopped_at';
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
