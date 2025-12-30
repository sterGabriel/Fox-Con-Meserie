<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (!Schema::hasColumn('videos', 'tmdb_type')) {
                $table->string('tmdb_type', 16)->nullable()->index()->after('tmdb_id');
            }
            if (!Schema::hasColumn('videos', 'tmdb_genres')) {
                $table->string('tmdb_genres', 255)->nullable()->after('tmdb_backdrop_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $cols = [];
            foreach (['tmdb_type', 'tmdb_genres'] as $c) {
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
