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
            // Missing Logo fields
            if (!Schema::hasColumn('live_channels', 'overlay_logo_height')) {
                $table->integer('overlay_logo_height')->default(100)->after('overlay_logo_width');
            }

            // Missing Text fields
            if (!Schema::hasColumn('live_channels', 'overlay_text_font_family')) {
                $table->string('overlay_text_font_family')->default('Arial')->after('overlay_text_font_size');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_color')) {
                $table->string('overlay_text_color')->default('#FFFFFF')->after('overlay_text_font_family');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_padding')) {
                $table->integer('overlay_text_padding')->default(6)->after('overlay_text_color');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_position')) {
                $table->string('overlay_text_position')->nullable()->after('overlay_text_padding');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_x')) {
                $table->integer('overlay_text_x')->default(20)->after('overlay_text_position');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_y')) {
                $table->integer('overlay_text_y')->default(20)->after('overlay_text_x');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_opacity')) {
                $table->float('overlay_text_opacity')->default(100)->after('overlay_text_y');
            }

            // Missing Timer fields
            if (!Schema::hasColumn('live_channels', 'overlay_timer_mode')) {
                $table->string('overlay_timer_mode')->default('realtime')->after('overlay_timer_format');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_font_size')) {
                $table->integer('overlay_timer_font_size')->default(24)->after('overlay_timer_y');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_color')) {
                $table->string('overlay_timer_color')->default('#FFFFFF')->after('overlay_timer_font_size');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_style')) {
                $table->string('overlay_timer_style')->default('normal')->after('overlay_timer_color');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_bg')) {
                $table->string('overlay_timer_bg')->default('none')->after('overlay_timer_style');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_opacity')) {
                $table->float('overlay_timer_opacity')->default(100)->after('overlay_timer_bg');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_channels', function (Blueprint $table) {
            $table->dropColumn([
                'overlay_logo_height',
                'overlay_text_font_family',
                'overlay_text_color',
                'overlay_text_padding',
                'overlay_text_position',
                'overlay_text_x',
                'overlay_text_y',
                'overlay_text_opacity',
                'overlay_timer_mode',
                'overlay_timer_font_size',
                'overlay_timer_color',
                'overlay_timer_style',
                'overlay_timer_bg',
                'overlay_timer_opacity',
            ]);
        });
    }
};
