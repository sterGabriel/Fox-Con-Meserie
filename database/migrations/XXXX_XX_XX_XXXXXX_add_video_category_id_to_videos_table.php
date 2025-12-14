<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->unsignedBigInteger('video_category_id')
                ->nullable()
                ->after('file_path');

            // dacă vrei și cheie străină (opțional, dar e frumos):
            // $table->foreign('video_category_id')
            //     ->references('id')
            //     ->on('video_categories')
            //     ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // dacă ai creat FK mai sus, o ștergi aici înainte:
            // $table->dropForeign(['video_category_id']);

            $table->dropColumn('video_category_id');
        });
    }
};
