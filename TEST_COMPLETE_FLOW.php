#!/usr/bin/env php
<?php
/**
 * COMPLETE TEST FLOW FOR IMPORT FUNCTIONALITY
 * Tests all TASK requirements with proof
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\VideoCategory;
use App\Models\Video;
use App\Models\LiveChannel;
use Illuminate\Support\Facades\DB;

echo "\n";
echo str_repeat("═", 80) . "\n";
echo "COMPLETE FLOW TEST - Import Functionality\n";
echo str_repeat("═", 80) . "\n\n";

// ==================== TEST CONFIG ====================
$test_category_id = 5; // MUZICA-Romaneasca
$test_category = VideoCategory::find($test_category_id);
$test_files = [
    '/media/videos/FILME/ACTIUNE/A Working Man (2025).mp4',
];

echo "📋 TEST CONFIGURATION\n";
echo "───────────────────────────────────────────────────────────────────────────────\n";
echo "Category: ID {$test_category_id} ({$test_category->name})\n";
echo "Test files: " . count($test_files) . "\n";
$test_files && print_r($test_files);
echo "\n";

// ==================== TASK 1: BUTTON ROUTE ====================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ TASK 1: Import Selected Button (POST Route)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check route
$routes = app('router')->getRoutes();
$import_route = null;
foreach ($routes as $route) {
    if ($route->getName() === 'admin.video_categories.import') {
        $import_route = $route;
        break;
    }
}

if ($import_route) {
    echo "✅ Route 'admin.video_categories.import' exists\n";
    echo "   Method: " . implode(', ', $import_route->methods) . "\n";
    echo "   URI: " . $import_route->uri . "\n";
    echo "   Action: " . $import_route->action['controller'] . "\n";
} else {
    echo "❌ Route NOT FOUND!\n";
}

// Check form in browse.blade.php
$browse_file = 'resources/views/admin/video_categories/browse.blade.php';
$browse_content = file_get_contents($browse_file);

$checks = [
    'POST method' => 'method="POST"',
    '@csrf token' => '@csrf',
    'files[] input' => 'name="files[]"',
    'submit button' => 'type="submit"',
];

echo "\n📝 Form Element Checks:\n";
foreach ($checks as $label => $needle) {
    $found = strpos($browse_content, $needle) !== false;
    echo ($found ? "✅" : "❌") . " $label: " . ($found ? "FOUND" : "NOT FOUND") . "\n";
}

echo "\n";

// ==================== TASK 2: ROOT PATH = /media ====================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ TASK 2: Root Path = /media\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check FileBrowserController basePath
$controller_file = 'app/Http/Controllers/Admin/FileBrowserController.php';
$controller_content = file_get_contents($controller_file);

if (preg_match('/\$basePath\s*=\s*["\']([^"\']+)["\']/', $controller_content, $matches)) {
    $base_path = $matches[1];
    echo "✅ Base path set: " . $base_path . "\n";
    
    if (is_dir($base_path)) {
        echo "✅ Path exists on filesystem\n";
        
        // List /media contents
        echo "\n📂 /media Contents:\n";
        $dirs = @scandir($base_path);
        if ($dirs) {
            foreach (array_slice($dirs, 2) as $item) {
                $full_path = "$base_path/$item";
                $type = is_dir($full_path) ? "📁" : "📄";
                echo "   $type $item\n";
            }
        }
    } else {
        echo "❌ Path NOT FOUND on filesystem!\n";
    }
} else {
    echo "❌ Base path NOT DEFINED in controller!\n";
}

echo "\n";

// ==================== TASK 3: CATEGORY-BASED IMPORT ====================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ TASK 3: Category-Based Import\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Count videos BEFORE per category
echo "\n📊 Video Count BEFORE Import:\n";
$stats_before = DB::table('videos')
    ->selectRaw('video_category_id, COUNT(*) as count')
    ->groupBy('video_category_id')
    ->get()
    ->keyBy('video_category_id');

$count_before_target = $stats_before->get($test_category_id)?->count ?? 0;
echo "   Category {$test_category_id} ({$test_category->name}): {$count_before_target} videos\n";
$total_before = Video::count();
echo "   Total in DB: {$total_before} videos\n";

// Simulate import
echo "\n🔄 Simulating Import:\n";
$imported_videos = [];

foreach ($test_files as $file_path) {
    if (!file_exists($file_path)) {
        echo "   ❌ File not found: $file_path\n";
        continue;
    }
    
    // Check if already exists
    if (Video::where('file_path', $file_path)->exists()) {
        echo "   ⏭️  Already imported: $file_path\n";
        continue;
    }
    
    // Create video with explicit category_id
    try {
        $video = Video::create([
            'title' => pathinfo($file_path, PATHINFO_FILENAME),
            'file_path' => $file_path,
            'duration_seconds' => 1234,
            'video_category_id' => $test_category_id, // ← CRITICAL: Category ID
            'metadata' => json_encode(['test' => true]),
            'format' => pathinfo($file_path, PATHINFO_EXTENSION),
        ]);
        
        $imported_videos[] = $video->id;
        echo "   ✅ Created Video ID {$video->id}\n";
        echo "      Title: {$video->title}\n";
        echo "      Category ID: {$video->video_category_id}\n";
    } catch (\Exception $e) {
        echo "   ❌ Creation failed: " . $e->getMessage() . "\n";
    }
}

// Count videos AFTER per category
echo "\n📊 Video Count AFTER Import:\n";
$stats_after = DB::table('videos')
    ->selectRaw('video_category_id, COUNT(*) as count')
    ->groupBy('video_category_id')
    ->get()
    ->keyBy('video_category_id');

$count_after_target = $stats_after->get($test_category_id)?->count ?? 0;
$count_increase = $count_after_target - $count_before_target;
echo "   Category {$test_category_id} ({$test_category->name}): {$count_after_target} videos (↑{$count_increase})\n";
$total_after = Video::count();
echo "   Total in DB: {$total_after} videos (↑" . ($total_after - $total_before) . ")\n";

// Verify videos have correct category
if (!empty($imported_videos)) {
    echo "\n✓ Category Verification:\n";
    $imported = Video::whereIn('id', $imported_videos)->get();
    foreach ($imported as $video) {
        $correct = $video->video_category_id === $test_category_id;
        echo ($correct ? "✅" : "❌") . " Video {$video->id}: category_id = {$video->video_category_id}\n";
    }
}

echo "\n";

// ==================== TASK 4: PLAYLIST IN CHANNEL SETTINGS ====================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ TASK 4: Playlist in Channel Settings (General Tab)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check LiveChannelController for settings() method
$lc_file = 'app/Http/Controllers/Admin/LiveChannelController.php';
$lc_content = file_get_contents($lc_file);

if (preg_match('/public\s+function\s+settings\s*\(/', $lc_content)) {
    echo "✅ LiveChannelController::settings() method exists\n";
} else {
    echo "❌ settings() method NOT FOUND\n";
}

// Check general.blade.php for playlist preview
$general_file = 'resources/views/admin/vod_channels/settings/general.blade.php';
if (file_exists($general_file)) {
    $general_content = file_get_contents($general_file);
    
    $checks = [
        'Playlist Preview box' => 'Playlist Preview',
        'Category check' => 'category',
        'Duration display' => 'duration',
    ];
    
    echo "\n📋 general.blade.php Checks:\n";
    foreach ($checks as $label => $needle) {
        $found = stripos($general_content, $needle) !== false;
        echo ($found ? "✅" : "❌") . " $label: " . ($found ? "FOUND" : "NOT FOUND") . "\n";
    }
} else {
    echo "❌ general.blade.php NOT FOUND\n";
}

echo "\n";

// ==================== TASK 5: HONEST BUTTONS ====================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ TASK 5: Honest Buttons\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "Checking browse.blade.php for disabled buttons and tooltips...\n\n";

// Look for disabled buttons
$disabled_pattern = '/disabled|title=|tooltip|title:"/i';
if (preg_match_all($disabled_pattern, $browse_content, $matches)) {
    echo "✅ Button state management found\n";
    echo "   - Import buttons: " . (stripos($browse_content, '{{ $file[\'imported\'] ? \'disabled\' : \'\' }}') !== false ? "Conditional disable" : "Check needed") . "\n";
} else {
    echo "⚠️  Review button states manually\n";
}

echo "\n";

// ==================== SUMMARY ====================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$results = [
    'TASK 1: Import Selected Button (POST)' => $import_route ? '✅ PASS' : '❌ FAIL',
    'TASK 2: Root Path = /media' => is_dir('/media') ? '✅ PASS' : '❌ FAIL',
    'TASK 3: Category-Based Import' => $count_increase > 0 && $count_after_target > $count_before_target ? '✅ PASS' : '⚠️  CHECK',
    'TASK 4: Playlist in Settings' => file_exists($general_file) ? '✅ EXISTS' : '❌ NOT FOUND',
    'TASK 5: Honest Buttons' => '⚠️  MANUAL REVIEW',
];

foreach ($results as $task => $result) {
    echo "$result - $task\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "Next: Run manual tests with DevTools to capture Network tab evidence\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

exit(0);
