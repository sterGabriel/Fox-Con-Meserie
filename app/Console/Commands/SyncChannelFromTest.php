<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncChannelFromTest extends Command
{
    protected $signature = 'channel:sync-from-test {channelId : Live channel id to sync} {--dry-run : Show what would change without writing}';

    protected $description = 'Sync overlay + encoding settings for a live channel from .env.testing DB into the current (production) DB.';

    public function handle(): int
    {
        $channelId = (int) $this->argument('channelId');
        if ($channelId <= 0) {
            $this->error('channelId must be a positive integer.');
            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');

        $testEnv = $this->readEnvFile(base_path('.env.testing'));
        $testDb = (string) ($testEnv['DB_DATABASE'] ?? '');
        if ($testDb === '') {
            $this->error('Missing DB_DATABASE in .env.testing');
            return self::FAILURE;
        }

        try {
            $pdoTest = $this->pdoFromEnv($testEnv);
        } catch (\Throwable $e) {
            $this->error('Failed to connect to TEST DB: ' . $e->getMessage());
            return self::FAILURE;
        }

        $desired = [
            // encode
            'resolution',
            'encode_profile_id',
            'manual_override_encoding',
            'manual_encode_enabled',
            'manual_width',
            'manual_height',
            'manual_fps',
            'manual_codec',
            'manual_preset',
            'manual_bitrate',
            'manual_audio_bitrate',
            'manual_audio_codec',

            // legacy overlay switches
            'overlay_title',
            'overlay_timer',

            // legacy logo fields
            'logo_path',
            'logo_width',
            'logo_height',
            'logo_position_x',
            'logo_position_y',

            // new overlay logo
            'overlay_logo_enabled',
            'overlay_logo_path',
            'overlay_logo_position',
            'overlay_logo_x',
            'overlay_logo_y',
            'overlay_logo_width',
            'overlay_logo_height',
            'overlay_logo_opacity',

            // overlay text
            'overlay_text_enabled',
            'overlay_text_content',
            'overlay_text_custom',
            'overlay_text_font_family',
            'overlay_text_font_size',
            'overlay_text_color',
            'overlay_text_padding',
            'overlay_text_position',
            'overlay_text_x',
            'overlay_text_y',
            'overlay_text_opacity',
            'overlay_text_bg_opacity',
            'overlay_text_bg_color',

            // overlay timer
            'overlay_timer_enabled',
            'overlay_timer_mode',
            'overlay_timer_format',
            'overlay_timer_position',
            'overlay_timer_x',
            'overlay_timer_y',
            'overlay_timer_font_size',
            'overlay_timer_color',
            'overlay_timer_style',
            'overlay_timer_bg',
            'overlay_timer_opacity',

            // safe
            'overlay_safe_margin',
        ];

        $prodCols = Schema::getColumnListing('live_channels');
        $testCols = $this->getTestTableColumns($pdoTest, $testDb, 'live_channels');

        $common = array_values(array_intersect($desired, $prodCols, $testCols));
        if (empty($common)) {
            $this->error('No common columns found to sync.');
            return self::FAILURE;
        }

        $select = 'SELECT ' . implode(',', array_map(fn ($c) => "`{$c}`", $common)) . ' FROM live_channels WHERE id=?';
        $st = $pdoTest->prepare($select);
        $st->execute([$channelId]);
        $testRow = $st->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($testRow) || empty($testRow)) {
            $this->error("Channel id={$channelId} not found in TEST DB ({$testDb}).");
            return self::FAILURE;
        }

        $current = DB::table('live_channels')->where('id', $channelId)->first();
        if (!$current) {
            $this->error("Channel id={$channelId} not found in PROD DB.");
            return self::FAILURE;
        }

        $changes = [];
        foreach ($common as $col) {
            $before = $current->{$col} ?? null;
            $after = $testRow[$col] ?? null;
            if ((string) $before !== (string) $after) {
                $changes[$col] = ['from' => $before, 'to' => $after];
            }
        }

        if (empty($changes)) {
            $this->info('No differences. PROD already matches TEST for these fields.');
            return self::SUCCESS;
        }

        $this->info('Will sync these fields: ' . count($changes));
        foreach (array_slice($changes, 0, 20, true) as $k => $v) {
            $this->line("- {$k}: " . json_encode($v));
        }
        if (count($changes) > 20) {
            $this->line('- ... (more)');
        }

        if ($dryRun) {
            $this->warn('Dry-run: no DB writes performed.');
            return self::SUCCESS;
        }

        $payload = [];
        foreach ($common as $col) {
            $payload[$col] = $testRow[$col] ?? null;
        }

        DB::table('live_channels')->where('id', $channelId)->update($payload);

        $this->info("Synced channel {$channelId} from TEST({$testDb}) -> PROD(" . (string) config('database.connections.mysql.database') . ")");

        return self::SUCCESS;
    }

    private function readEnvFile(string $path): array
    {
        $out = [];
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) return $out;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip surrounding double quotes; ignore single-quoted edge cases.
            if ($value !== '' && str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            }

            $out[$key] = $value;
        }

        return $out;
    }

    private function pdoFromEnv(array $env): \PDO
    {
        $host = (string) ($env['DB_HOST'] ?? '127.0.0.1');
        $port = (string) ($env['DB_PORT'] ?? '3306');
        $db = (string) ($env['DB_DATABASE'] ?? '');
        $user = (string) ($env['DB_USERNAME'] ?? '');
        $pass = (string) ($env['DB_PASSWORD'] ?? '');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        return new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    private function getTestTableColumns(\PDO $pdo, string $db, string $table): array
    {
        $st = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
        $st->execute([$db, $table]);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn ($r) => (string) ($r['COLUMN_NAME'] ?? ''), $rows);
    }
}
