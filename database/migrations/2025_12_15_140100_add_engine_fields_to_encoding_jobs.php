<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encoding_jobs', function (Blueprint $table) {
            // Add channel_id if not exists
            if (!Schema::hasColumn('encoding_jobs', 'channel_id')) {
                $table->unsignedBigInteger('channel_id')->nullable()->after('id');
                $table->foreign('channel_id')->references('id')->on('live_channels')->onDelete('cascade');
            }
            
            // Add pid for process tracking
            if (!Schema::hasColumn('encoding_jobs', 'pid')) {
                $table->integer('pid')->nullable()->after('status');
            }
            
            // Add log_path for file-based logging
            if (!Schema::hasColumn('encoding_jobs', 'log_path')) {
                $table->string('log_path')->nullable()->after('pid');
            }
            
            // Add ended_at (different from finished_at if that exists)
            if (!Schema::hasColumn('encoding_jobs', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('finished_at');
            }
            
            // Add exit_code for process exit status
            if (!Schema::hasColumn('encoding_jobs', 'exit_code')) {
                $table->integer('exit_code')->nullable()->after('ended_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('encoding_jobs', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn(['channel_id', 'pid', 'log_path', 'ended_at', 'exit_code']);
        });
    }
};
