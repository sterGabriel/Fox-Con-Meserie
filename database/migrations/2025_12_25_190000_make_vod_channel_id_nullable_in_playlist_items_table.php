<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('playlist_items') || !Schema::hasColumn('playlist_items', 'vod_channel_id')) {
            return;
        }

        // Drop any existing FK constraints for vod_channel_id (name can vary).
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'playlist_items'
               AND COLUMN_NAME = 'vod_channel_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        );

        foreach ($constraints as $row) {
            if (!empty($row->name)) {
                DB::statement("ALTER TABLE `playlist_items` DROP FOREIGN KEY `{$row->name}`");
            }
        }

        // Make column nullable so legacy inserts that only set live_channel_id keep working.
        DB::statement('ALTER TABLE `playlist_items` MODIFY `vod_channel_id` BIGINT UNSIGNED NULL');

        // Restore FK to keep integrity when vod_channel_id is provided.
        DB::statement(
            'ALTER TABLE `playlist_items` '
            . 'ADD CONSTRAINT `playlist_items_vod_channel_id_foreign` '
            . 'FOREIGN KEY (`vod_channel_id`) REFERENCES `live_channels`(`id`) ON DELETE CASCADE'
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('playlist_items') || !Schema::hasColumn('playlist_items', 'vod_channel_id')) {
            return;
        }

        // Drop any existing FK constraints for vod_channel_id.
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'playlist_items'
               AND COLUMN_NAME = 'vod_channel_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        );

        foreach ($constraints as $row) {
            if (!empty($row->name)) {
                DB::statement("ALTER TABLE `playlist_items` DROP FOREIGN KEY `{$row->name}`");
            }
        }

        // Prevent failure when making NOT NULL.
        DB::statement('UPDATE `playlist_items` SET `vod_channel_id` = `live_channel_id` WHERE `vod_channel_id` IS NULL');

        DB::statement('ALTER TABLE `playlist_items` MODIFY `vod_channel_id` BIGINT UNSIGNED NOT NULL');

        DB::statement(
            'ALTER TABLE `playlist_items` '
            . 'ADD CONSTRAINT `playlist_items_vod_channel_id_foreign` '
            . 'FOREIGN KEY (`vod_channel_id`) REFERENCES `live_channels`(`id`) ON DELETE CASCADE'
        );
    }
};
