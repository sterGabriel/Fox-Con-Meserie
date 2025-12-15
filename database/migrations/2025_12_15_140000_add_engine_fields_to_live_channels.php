<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (!Schema::hasColumn('live_channels', 'encoder_pid')) {
                $table->integer('encoder_pid')->nullable()->after('status');
            }
            if (!Schema::hasColumn('live_channels', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('encoder_pid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->dropColumn(['encoder_pid', 'started_at']);
        });
    }
};
