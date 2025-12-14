<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_channel_id')->constrained('live_channels')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->integer('sort_order')->default(0);     // ordinea în playlist
            $table->timestamp('start_at')->nullable();     // opțional, ca în FOX (Start)
            $table->timestamp('end_at')->nullable();       // End
            $table->timestamps();                          // created_at = Added Date
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
    }
};
