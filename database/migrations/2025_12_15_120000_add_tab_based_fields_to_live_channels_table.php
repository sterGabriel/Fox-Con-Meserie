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
            // General fields
            if (!Schema::hasColumn('live_channels', 'is_24_7_channel')) {
                $table->boolean('is_24_7_channel')->default(true);
            }
            if (!Schema::hasColumn('live_channels', 'description')) {
                $table->text('description')->nullable();
            }

            // Encoding override fields
            if (!Schema::hasColumn('live_channels', 'manual_override_encoding')) {
                $table->boolean('manual_override_encoding')->default(false);
            }
            if (!Schema::hasColumn('live_channels', 'manual_width')) {
                $table->integer('manual_width')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_height')) {
                $table->integer('manual_height')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_fps')) {
                $table->integer('manual_fps')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_codec')) {
                $table->string('manual_codec')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_preset')) {
                $table->string('manual_preset')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_bitrate')) {
                $table->integer('manual_bitrate')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_audio_bitrate')) {
                $table->integer('manual_audio_bitrate')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'manual_audio_codec')) {
                $table->string('manual_audio_codec')->nullable();
            }

            // Overlay: Logo
            if (!Schema::hasColumn('live_channels', 'overlay_logo_enabled')) {
                $table->boolean('overlay_logo_enabled')->default(false);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_path')) {
                $table->string('overlay_logo_path')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_position')) {
                $table->string('overlay_logo_position')->default('TL');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_x')) {
                $table->integer('overlay_logo_x')->default(20);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_y')) {
                $table->integer('overlay_logo_y')->default(20);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_width')) {
                $table->integer('overlay_logo_width')->default(100);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_opacity')) {
                $table->float('overlay_logo_opacity')->default(80);
            }

            // Overlay: Text
            if (!Schema::hasColumn('live_channels', 'overlay_text_enabled')) {
                $table->boolean('overlay_text_enabled')->default(false);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_content')) {
                $table->string('overlay_text_content')->default('channel_name');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_custom')) {
                $table->string('overlay_text_custom')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_font_size')) {
                $table->integer('overlay_text_font_size')->default(24);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_bg_opacity')) {
                $table->float('overlay_text_bg_opacity')->default(50);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_bg_color')) {
                $table->string('overlay_text_bg_color')->default('#000000');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_font_family')) {
                $table->string('overlay_text_font_family')->default('Arial');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_color')) {
                $table->string('overlay_text_color')->default('#FFFFFF');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_padding')) {
                $table->integer('overlay_text_padding')->default(6);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_position')) {
                $table->string('overlay_text_position')->nullable();
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_x')) {
                $table->integer('overlay_text_x')->default(20);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_y')) {
                $table->integer('overlay_text_y')->default(20);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_text_opacity')) {
                $table->float('overlay_text_opacity')->default(100);
            }

            // Overlay: Timer
            if (!Schema::hasColumn('live_channels', 'overlay_timer_enabled')) {
                $table->boolean('overlay_timer_enabled')->default(false);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_format')) {
                $table->string('overlay_timer_format')->default('HH:mm');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_position')) {
                $table->string('overlay_timer_position')->default('TR');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_x')) {
                $table->integer('overlay_timer_x')->default(20);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_y')) {
                $table->integer('overlay_timer_y')->default(20);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_mode')) {
                $table->string('overlay_timer_mode')->default('realtime');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_font_size')) {
                $table->integer('overlay_timer_font_size')->default(24);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_color')) {
                $table->string('overlay_timer_color')->default('#FFFFFF');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_style')) {
                $table->string('overlay_timer_style')->default('normal');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_bg')) {
                $table->string('overlay_timer_bg')->default('none');
            }
            if (!Schema::hasColumn('live_channels', 'overlay_timer_opacity')) {
                $table->float('overlay_timer_opacity')->default(100);
            }
            if (!Schema::hasColumn('live_channels', 'overlay_logo_height')) {
                $table->integer('overlay_logo_height')->default(100);
            }

            // Overlay: Safe margin
            if (!Schema::hasColumn('live_channels', 'overlay_safe_margin')) {
                $table->integer('overlay_safe_margin')->default(20);
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
                'is_24_7_channel',
                'description',
                'manual_override_encoding',
                'manual_width',
                'manual_height',
                'manual_fps',
                'manual_codec',
                'manual_preset',
                'manual_bitrate',
                'manual_audio_bitrate',
                'manual_audio_codec',
                'overlay_logo_enabled',
                'overlay_logo_path',
                'overlay_logo_position',
                'overlay_logo_x',
                'overlay_logo_y',
                'overlay_logo_width',
                'overlay_logo_height',
                'overlay_logo_opacity',
                'overlay_text_enabled',
                'overlay_text_content',
                'overlay_text_custom',
                'overlay_text_font_size',
                'overlay_text_font_family',
                'overlay_text_color',
                'overlay_text_padding',
                'overlay_text_position',
                'overlay_text_x',
                'overlay_text_y',
                'overlay_text_opacity',
                'overlay_text_bg_opacity',
                'overlay_text_bg_color',
                'overlay_timer_enabled',
                'overlay_timer_format',
                'overlay_timer_position',
                'overlay_timer_x',
                'overlay_timer_y',
                'overlay_timer_mode',
                'overlay_timer_font_size',
                'overlay_timer_color',
                'overlay_timer_style',
                'overlay_timer_bg',
                'overlay_timer_opacity',
                'overlay_safe_margin',
            ]);
        });
    }
};
