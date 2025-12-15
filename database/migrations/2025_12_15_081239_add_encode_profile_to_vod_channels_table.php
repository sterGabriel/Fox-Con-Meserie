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
        Schema::table('live_channels', function (Blueprint $table) {
            $table->foreignId('encode_profile_id')->nullable()->constrained('encode_profiles')->nullOnDelete();
            $table->boolean('manual_encode_enabled')->default(false);
            $table->json('manual_encode_config')->nullable(); // Override config
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->dropForeignIdFor('encode_profiles', 'encode_profile_id');
            $table->dropColumn(['encode_profile_id', 'manual_encode_enabled', 'manual_encode_config']);
        });
    }
};
