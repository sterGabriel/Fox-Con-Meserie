<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();     // numele categoriei
            $table->string('slug')->unique();     // slug (ex: action, comedy)
            $table->text('description')->nullable(); // descriere opțională
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_categories');
    }
};
