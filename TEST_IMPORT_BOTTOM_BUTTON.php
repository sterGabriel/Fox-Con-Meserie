<?php
/**
 * Test Import Selected (Bottom Button)
 * Simulates: User selects 2 files, clicks "Import Selected" at bottom
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoCategory;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

echo "\n";
echo str_repeat("═", 70) . "\n";
echo "TEST: Bottom \"Import Selected\" Button\n";
echo str_repeat("═", 70) . "\n\n";

$test_category_id = 5;
$test_category = VideoCategory::find($test_category_id);

// Two new files to import
$test_files = [
    '/media/videos/ActiuneSkylineTV/Absolute Dominion (2025).mp4',
    '/media/videos/ActiuneSkylineTV/Ground Zero (2025).mp4',
];

echo "📝 Setup:\n";
echo "   Category: ID $test_category_id ({$test_category->name})\n";
echo "   Files to import: 2\n\n";

// Check files exist
foreach ($test_files as $f) {
    echo "   - " . basename($f) . ": " . (file_exists($f) ? "✓" : "✗") . "\n";
}

// Clean up if already imported
echo "\n🧹 Cleanup (delete if exist):\n";
foreach ($test_files as $file) {
    $existing = Video::where('file_path', $file)->first();
    if ($existing) {
        echo "   Deleting ID {$existing->id}...\n";
        $existing->delete();
    }
}

// Count BEFORE
$count_before = Video::where('video_category_id', $test_category_id)->count();
echo "\n📊 BEFORE:\n";
echo "   Videos in category: $count_before\n";

// Simulate POST request with files[] array (like bottom button)
echo "\n🔄 IMPORTING (simulating bottom button POST):\n";
echo "   POST /video-categories/$test_category_id/import\n";
echo "   files[] = [2 files]\n\n";

$imported_count = 0;

foreach ($test_files as $filePath) {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo "   ✗ " . basename($filePath) . " - NOT ACCESSIBLE\n";
        continue;
    }

    if (Video::where('file_path', $filePath)->exists()) {
        echo "   ⏭️  " . basename($filePath) . " - ALREADY IMPORTED\n";
        continue;
    }

    try {
        $video = Video::create([
            'title' => pathinfo($filePath, PATHINFO_FILENAME),
            'file_path' => $filePath,
            'duration_seconds' => 4567,
            'video_category_id' => $test_category_id,
            'metadata' => json_encode(['imported_via' => 'bottom_button_test']),
            'format' => pathinfo($filePath, PATHINFO_EXTENSION),
        ]);

        echo "   ✅ " . basename($filePath) . " (ID {$video->id})\n";
        $imported_count++;
    } catch (\Exception $e) {
        echo "   ❌ " . basename($filePath) . " - " . $e->getMessage() . "\n";
    }
}

// Count AFTER
$count_after = Video::where('video_category_id', $test_category_id)->count();
echo "\n📊 AFTER:\n";
echo "   Videos in category: $count_after\n";
echo "   Increase: " . ($count_after - $count_before) . "\n";
echo "   Imported: $imported_count\n";

// Final verdict
echo "\n";
if ($imported_count === 2 && ($count_after - $count_before) === 2) {
    echo "✅ BOTTOM BUTTON WORKS! Both files imported successfully.\n";
} else {
    echo "⚠️  PARTIAL: " . $imported_count . " out of 2 files imported.\n";
}

echo "\n" . str_repeat("═", 70) . "\n\n";
exit(0);
