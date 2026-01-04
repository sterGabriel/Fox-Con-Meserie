<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('action', 10); // allow | block
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('updated_by_user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['action', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_rules');
    }
};
