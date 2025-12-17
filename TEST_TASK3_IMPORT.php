#!/usr/bin/env php
<?php
/**
 * TEST TASK 3: Category-Based Import
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VideoCategory;
use App\Models\Video;

// Test file
$test_file = '/media/videos/ActiuneSkylineTV/Guardian Of Graveyard (2025).mp4';
$test_category_id = 5; // MUZICA-Romaneasca
$test_category = VideoCategory::find($test_category_id);

echo "\n";
echo str_repeat("═", 70) . "\n";
echo "TEST TASK 3: Category-Based Import\n";
echo str_repeat("═", 70) . "\n";

echo "\n📝 Test Setup:\n";
echo "   File: $test_file\n";
echo "   Category: ID $test_category_id ({$test_category->name})\n";

// Check if file exists
if (!file_exists($test_file)) {
    echo "❌ Test file not found!\n";
    exit(1);
}
echo "   ✅ File exists\n";

// Delete if already imported
$existing = Video::where('file_path', $test_file)->first();
if ($existing) {
    echo "   ⚠️  Deleting previous import (ID {$existing->id})...\n";
    $existing->delete();
    echo "   ✅ Cleaned up\n";
}

// Count BEFORE
$count_before = Video::where('video_category_id', $test_category_id)->count();
echo "\n📊 BEFORE Import:\n";
echo "   Videos in category $test_category_id: $count_before\n";

// IMPORT
echo "\n🔄 Importing Video...\n";
try {
    $video = Video::create([
        'title' => pathinfo($test_file, PATHINFO_FILENAME),
        'file_path' => $test_file,
        'duration_seconds' => 5678,
        'video_category_id' => $test_category_id,
        'metadata' => json_encode(['test' => true, 'resolution' => '1920x1080']),
        'format' => pathinfo($test_file, PATHINFO_EXTENSION),
    ]);
    
    echo "   ✅ Video created: ID {$video->id}\n";
    echo "   Title: {$video->title}\n";
    echo "   Category ID: {$video->video_category_id}\n";
} catch (\Exception $e) {
    echo "   ❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Count AFTER
$count_after = Video::where('video_category_id', $test_category_id)->count();
echo "\n📊 AFTER Import:\n";
echo "   Videos in category $test_category_id: $count_after\n";
echo "   Increase: " . ($count_after - $count_before) . "\n";

// Verify
$check = Video::where('file_path', $test_file)->where('video_category_id', $test_category_id)->first();

if ($check) {
    echo "\n✅ TASK 3 PASS: Category-based import working!\n";
    echo "   Video {$check->id} correctly assigned to category $test_category_id\n";
} else {
    echo "\n❌ TASK 3 FAIL: Video not in correct category!\n";
}

echo "\n" . str_repeat("═", 70) . "\n\n";
exit(0);
