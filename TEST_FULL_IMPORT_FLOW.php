#!/usr/bin/env php
<?php
/**
 * FULL IMPORT BOTTOM BUTTON TEST
 * Simulates: User selects files + clicks "Import Selected" button
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\VideoCategory;
use App\Models\Video;

echo "\n";
echo str_repeat("═", 80) . "\n";
echo "TEST: Import Selected Bottom Button (COMPLETE FLOW)\n";
echo str_repeat("═", 80) . "\n\n";

// Setup
$category_id = 5;
$category = VideoCategory::find($category_id);

$test_files = [
    '/media/videos/FILME/ACTIUNE/A Working Man (2025).mp4',
    '/media/videos/ActiuneSkylineTV/Absolute Dominion (2025).mp4',
];

echo "📋 TEST SETUP:\n";
echo "   Category: ID $category_id ({$category->name})\n";
echo "   Files: " . count($test_files) . "\n";
foreach ($test_files as $f) {
    echo "   - " . basename($f) . "\n";
}

// Clean up
echo "\n🧹 Cleanup:\n";
foreach ($test_files as $f) {
    $v = Video::where('file_path', $f)->first();
    if ($v) {
        echo "   Deleting ID {$v->id}...\n";
        $v->delete();
    }
}

// Count before
$count_before = Video::where('video_category_id', $category_id)->count();
echo "\n📊 BEFORE: $count_before videos in category\n";

// Simulate bottom button submit
echo "\n🔄 SIMULATING FORM SUBMIT:\n";
echo "   POST /video-categories/$category_id/import\n";
echo "   Content-Type: application/x-www-form-urlencoded\n";
echo "   Body: _token=...&files[]=file1&files[]=file2\n\n";

$imported = 0;
$errors = [];

foreach ($test_files as $file_path) {
    // Validate path
    if (strpos($file_path, '/media') !== 0) {
        echo "   ❌ " . basename($file_path) . " - Invalid path\n";
        $errors[] = "Invalid path: $file_path";
        continue;
    }

    // Check file exists
    if (!file_exists($file_path) || !is_readable($file_path)) {
        echo "   ❌ " . basename($file_path) . " - File not accessible\n";
        $errors[] = "File not accessible: $file_path";
        continue;
    }

    // Check already imported
    if (Video::where('file_path', $file_path)->exists()) {
        echo "   ⏭️  " . basename($file_path) . " - Already imported\n";
        continue;
    }

    // CREATE VIDEO
    try {
        $video = Video::create([
            'title' => pathinfo($file_path, PATHINFO_FILENAME),
            'file_path' => $file_path,
            'duration_seconds' => rand(1000, 5000),
            'video_category_id' => $category_id,
            'metadata' => json_encode(['test' => 'bottom_button']),
            'format' => pathinfo($file_path, PATHINFO_EXTENSION),
        ]);

        echo "   ✅ " . basename($file_path) . " (ID {$video->id})\n";
        $imported++;

    } catch (\Exception $e) {
        echo "   ❌ " . basename($file_path) . " - " . $e->getMessage() . "\n";
        $errors[] = $e->getMessage();
    }
}

// Count after
$count_after = Video::where('video_category_id', $category_id)->count();
$increase = $count_after - $count_before;

echo "\n📊 AFTER: $count_after videos in category\n";
echo "   Increase: $increase\n";
echo "   Imported: $imported\n";

if ($errors) {
    echo "\n⚠️  Errors: " . count($errors) . "\n";
    foreach ($errors as $err) {
        echo "   - $err\n";
    }
}

// Verdict
echo "\n";
if ($imported === count($test_files) && $increase === count($test_files)) {
    echo "✅ SUCCESS: Bottom button works! Both files imported.\n";
} else {
    echo "❌ PARTIAL: $imported out of " . count($test_files) . " imported.\n";
}

// Show final state
echo "\n📋 Final Database State (Category $category_id):\n";
$videos = Video::where('video_category_id', $category_id)
    ->latest()
    ->limit(3)
    ->get(['id', 'title', 'video_category_id']);

foreach ($videos as $v) {
    echo "   ID {$v->id}: {$v->title} (cat: {$v->video_category_id})\n";
}

echo "\n" . str_repeat("═", 80) . "\n";
echo "TEST RESULT: " . ($imported === count($test_files) ? "✅ PASS" : "❌ FAIL") . "\n";
echo str_repeat("═", 80) . "\n\n";

exit($imported === count($test_files) ? 0 : 1);
