<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {

            // logo
            if (!Schema::hasColumn('live_channels', 'logo_position')) {
                $table->string('logo_position', 50)->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('live_channels', 'logo_offset_x')) {
                $table->integer('logo_offset_x')->nullable()->default(20)->after('logo_position');
            }
            if (!Schema::hasColumn('live_channels', 'logo_offset_y')) {
                $table->integer('logo_offset_y')->nullable()->default(20)->after('logo_offset_x');
            }

            // title
            if (!Schema::hasColumn('live_channels', 'title_position')) {
                $table->string('title_position', 50)->nullable()->after('overlay_title');
            }
            if (!Schema::hasColumn('live_channels', 'title_offset_x')) {
                $table->integer('title_offset_x')->nullable()->default(30)->after('title_position');
            }
            if (!Schema::hasColumn('live_channels', 'title_offset_y')) {
                $table->integer('title_offset_y')->nullable()->default(50)->after('title_offset_x');
            }

            // timer
            if (!Schema::hasColumn('live_channels', 'timer_position')) {
                $table->string('timer_position', 50)->nullable()->after('overlay_timer');
            }
            if (!Schema::hasColumn('live_channels', 'timer_offset_x')) {
                $table->integer('timer_offset_x')->nullable()->default(30)->after('timer_position');
            }
            if (!Schema::hasColumn('live_channels', 'timer_offset_y')) {
                $table->integer('timer_offset_y')->nullable()->default(20)->after('timer_offset_x');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            if (Schema::hasColumn('live_channels', 'logo_position'))   $table->dropColumn('logo_position');
            if (Schema::hasColumn('live_channels', 'logo_offset_x'))   $table->dropColumn('logo_offset_x');
            if (Schema::hasColumn('live_channels', 'logo_offset_y'))   $table->dropColumn('logo_offset_y');

            if (Schema::hasColumn('live_channels', 'title_position'))  $table->dropColumn('title_position');
            if (Schema::hasColumn('live_channels', 'title_offset_x'))  $table->dropColumn('title_offset_x');
            if (Schema::hasColumn('live_channels', 'title_offset_y'))  $table->dropColumn('title_offset_y');

            if (Schema::hasColumn('live_channels', 'timer_position'))  $table->dropColumn('timer_position');
            if (Schema::hasColumn('live_channels', 'timer_offset_x'))  $table->dropColumn('timer_offset_x');
            if (Schema::hasColumn('live_channels', 'timer_offset_y'))  $table->dropColumn('timer_offset_y');
        });
    }
};
