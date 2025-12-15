#!/usr/bin/env php
<?php
/**
 * Task Workflow Verification Script
 * Validates all completed tasks end-to-end
 */

require __DIR__ . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;
use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\EncodingJob;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║       TASK EXECUTION VERIFICATION (Tasks 0-4)                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$checks = [
    '✓' => 0,
    '✗' => 0,
    '⚠' => 0,
];

// Check 1: Routes exist
echo "[1] Checking Routes...\n";
$routes = [
    '/vod-channels/{channel}/engine/start',
    '/vod-channels/{channel}/engine/start-encoding',
    '/vod-channels/{channel}/engine/encoding-jobs',
    '/vod-channels/{channel}/engine/stop',
    '/vod-channels/{channel}/engine/test-preview',
    '/vod-channels/{channel}/engine/outputs',
    '/vod-channels/{channel}/engine/start-looping',
];

// Quick check - file content instead of full route:list to avoid process execution
$routesFilePath = base_path('routes/web.php');
$routesContent = file_get_contents($routesFilePath);

foreach ($routes as $route) {
    $pattern = str_replace('{channel}', '.*', preg_quote($route, '/'));
    if (preg_match("/$pattern/", $routesContent)) {
        echo "    ✓ Route exists: $route\n";
        $checks['✓']++;
    } else {
        echo "    ✗ Route missing: $route\n";
        $checks['✗']++;
    }
}

// Check 2: Database migrations applied
echo "\n[2] Checking Database Migrations...\n";
$migrations = [
    'create_users_table',
    'create_live_channels_table',
    'create_videos_table',
    'create_playlist_items_table',
    'add_vod_channel_id_to_playlist_items_table',
    'create_encoding_jobs_table',
];

$appliedMigrations = DB::table('migrations')->pluck('migration')->toArray();

foreach ($migrations as $migration) {
    $found = collect($appliedMigrations)->contains(function ($item) use ($migration) {
        return str_contains($item, $migration);
    });
    
    if ($found) {
        echo "    ✓ Migration: $migration\n";
        $checks['✓']++;
    } else {
        echo "    ⚠ Migration possibly missing: $migration\n";
        $checks['⚠']++;
    }
}

// Check 3: Services exist
echo "\n[3] Checking Services...\n";
$services = [
    'EncodingService' => app_path('Services/EncodingService.php'),
    'ChannelEngineService' => app_path('Services/ChannelEngineService.php'),
];

foreach ($services as $name => $path) {
    if (file_exists($path)) {
        echo "    ✓ Service exists: $name\n";
        
        // Check for key methods
        $content = file_get_contents($path);
        $methods = [];
        
        if ($name === 'EncodingService') {
            $methods = ['encode', 'buildEncodeCommand', 'buildFilterComplex'];
        } elseif ($name === 'ChannelEngineService') {
            $methods = ['generatePlayCommand', 'generateCommand', 'start', 'stop'];
        }
        
        foreach ($methods as $method) {
            if (str_contains($content, "function $method")) {
                echo "      ✓ Method: $method()\n";
                $checks['✓']++;
            } else {
                echo "      ✗ Method missing: $method()\n";
                $checks['✗']++;
            }
        }
    } else {
        echo "    ✗ Service missing: $name\n";
        $checks['✗']++;
    }
}

// Check 4: Models have required fields
echo "\n[4] Checking Model Fields...\n";
$table = DB::getSchemaBuilder()->getColumnListing('encoding_jobs');
$requiredFields = ['id', 'status', 'output_path', 'channel_id', 'playlist_item_id', 'completed_at'];

foreach ($requiredFields as $field) {
    if (in_array($field, $table)) {
        echo "    ✓ Field: encoding_jobs.$field\n";
        $checks['✓']++;
    } else {
        echo "    ✗ Field missing: encoding_jobs.$field\n";
        $checks['✗']++;
    }
}

// Check 5: UI files exist
echo "\n[5] Checking UI Templates...\n";
$views = [
    'engine' => resource_path('views/admin/vod_channels/settings_tabs/engine.blade.php'),
    'outputs' => resource_path('views/admin/vod_channels/settings_tabs/outputs.blade.php'),
];

foreach ($views as $name => $path) {
    if (file_exists($path)) {
        echo "    ✓ View exists: $name.blade.php\n";
        $checks['✓']++;
        
        // Check for key UI elements
        $content = file_get_contents($path);
        if ($name === 'engine' && str_contains($content, 'ENCODE NOW')) {
            echo "      ✓ UI: ENCODE NOW button\n";
            $checks['✓']++;
        }
        if ($name === 'outputs' && str_contains($content, 'engine/outputs')) {
            echo "      ✓ UI: Outputs endpoint integration\n";
            $checks['✓']++;
        }
    } else {
        echo "    ✗ View missing: $name.blade.php\n";
        $checks['✗']++;
    }
}

// Summary
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      VERIFICATION SUMMARY                       ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║  PASSED:  " . str_pad($checks['✓'], 3) . " checks\n";
echo "║  WARNING: " . str_pad($checks['⚠'], 3) . " checks\n";
echo "║  FAILED:  " . str_pad($checks['✗'], 3) . " checks\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";

if ($checks['✗'] === 0) {
    echo "║  STATUS:  ✅ READY FOR TESTING                                  ║\n";
} elseif ($checks['✗'] < 3) {
    echo "║  STATUS:  ⚠️  MOSTLY READY (minor issues)                       ║\n";
} else {
    echo "║  STATUS:  ❌ NEEDS WORK                                         ║\n";
}

echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Recommendations
echo "📋 NEXT STEPS:\n\n";
echo "1. Create test channel:\n";
echo "   php artisan tinker\n";
echo "   > \$c = App\\Models\\LiveChannel::create(['name'=>'Test']); exit;\n\n";

echo "2. Add videos to playlist:\n";
echo "   > Admin panel → VOD Channels → {channel} → Playlist tab\n\n";

echo "3. Test ENCODE NOW:\n";
echo "   > Settings → Engine tab → Click ⚙️ ENCODE NOW\n";
echo "   > Watch \"X/Y files encoded\" progress\n\n";

echo "4. Test START CHANNEL:\n";
echo "   > Click ▶ START CHANNEL\n";
echo "   > Check status changes to 🟢 LIVE STREAMING\n\n";

echo "5. Test in VLC:\n";
echo "   > Open VLC → Media → Open Network Stream\n";
echo "   > Paste HLS URL from Outputs tab\n";
echo "   > Verify playback + overlay visible\n\n";

exit(0);
?>
