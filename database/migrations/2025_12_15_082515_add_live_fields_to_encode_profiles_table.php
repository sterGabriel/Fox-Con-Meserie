<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('encode_profiles', function (Blueprint $table) {
            $table->enum('mode', ['vod', 'live'])->default('vod')->after('is_system');

            // LIVE-specific fields
            $table->string('ts_service_name')->nullable()->after('mode');
            $table->string('ts_service_provider')->nullable()->after('ts_service_name');
            $table->unsignedSmallInteger('pcr_period_ms')->default(20)->after('ts_service_provider');
            $table->unsignedSmallInteger('pat_period_ms')->default(100)->after('pcr_period_ms');
            $table->unsignedSmallInteger('pmt_period_ms')->default(100)->after('pat_period_ms');
            $table->unsignedSmallInteger('muxrate_k')->nullable()->after('pmt_period_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encode_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'mode',
                'ts_service_name',
                'ts_service_provider',
                'pcr_period_ms',
                'pat_period_ms',
                'pmt_period_ms',
                'muxrate_k'
            ]);
        });
    }
};
