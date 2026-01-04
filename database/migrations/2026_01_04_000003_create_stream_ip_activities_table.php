<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_ip_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->index();
            $table->string('ip', 45);

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->unsignedBigInteger('hit_count')->default(0);

            $table->string('last_file', 255)->nullable();
            $table->string('last_method', 10)->nullable();
            $table->string('last_user_agent', 255)->nullable();

            $table->timestamps();

            $table->unique(['channel_id', 'ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_ip_activities');
    }
};
