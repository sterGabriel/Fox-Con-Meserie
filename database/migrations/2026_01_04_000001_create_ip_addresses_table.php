<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();

            $table->string('label', 120)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->unsignedBigInteger('hit_count')->default(0);

            $table->string('last_path', 255)->nullable();
            $table->string('last_method', 10)->nullable();
            $table->unsignedBigInteger('last_user_id')->nullable()->index();
            $table->string('last_user_agent', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
