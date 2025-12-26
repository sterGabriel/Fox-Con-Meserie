<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (!Schema::hasColumn('videos', 'tmdb_id')) {
                $table->unsignedBigInteger('tmdb_id')->nullable()->index()->after('format');
            }
            if (!Schema::hasColumn('videos', 'tmdb_poster_path')) {
                $table->string('tmdb_poster_path')->nullable()->after('tmdb_id');
            }
            if (!Schema::hasColumn('videos', 'tmdb_backdrop_path')) {
                $table->string('tmdb_backdrop_path')->nullable()->after('tmdb_poster_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $cols = [];
            foreach (['tmdb_id', 'tmdb_poster_path', 'tmdb_backdrop_path'] as $c) {
                if (Schema::hasColumn('videos', $c)) {
                    $cols[] = $c;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
