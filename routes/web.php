<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LiveChannelController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\FileBrowserController;
use App\Http\Controllers\Admin\VideoCategoryController;
use App\Http\Controllers\Admin\EncodingJobController;

// ROOT → redirect to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ═══════════════════════════════════════════════════════════════
// 🔓 PUBLIC ROUTES (NO AUTH REQUIRED) - ONLY LOGO PREVIEW
// ═══════════════════════════════════════════════════════════════

Route::get('/vod-channels/{channel}/logo-preview', [LiveChannelController::class, 'logoPreview'])
    ->name('vod-channels.logo.preview');

// ═══════════════════════════════════════════════════════════════
// 🔒 PROTECTED ROUTES (AUTH + VERIFIED REQUIRED)
// ═══════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified'])->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ───────────── VOD CHANNELS ─────────────

    Route::get('/vod-channels', [LiveChannelController::class, 'index'])
        ->name('vod-channels.index');

    Route::get('/vod-channels/create', [LiveChannelController::class, 'create'])
        ->name('vod-channels.create');

    Route::post('/vod-channels', [LiveChannelController::class, 'store'])
        ->name('vod-channels.store');

    // PLAYLIST - PROTECTED
    Route::get('/vod-channels/{channel}/playlist', [LiveChannelController::class, 'playlist'])
        ->name('vod-channels.playlist');

    Route::post('/vod-channels/{channel}/playlist', [LiveChannelController::class, 'addToPlaylist'])
        ->name('vod-channels.playlist.add');

    Route::post('/vod-channels/{channel}/playlist/add-bulk', [LiveChannelController::class, 'addToPlaylistBulk'])
        ->name('vod-channels.playlist.add-bulk');

    Route::delete('/vod-channels/{channel}/playlist/{item}', [LiveChannelController::class, 'removeFromPlaylist'])
        ->name('vod-channels.playlist.remove');

    Route::post('/vod-channels/{channel}/playlist/{item}/move-up', [LiveChannelController::class, 'moveUp'])
        ->name('vod-channels.playlist.move-up');

    Route::post('/vod-channels/{channel}/playlist/{item}/move-down', [LiveChannelController::class, 'moveDown'])
        ->name('vod-channels.playlist.move-down');

    Route::post('/vod-channels/{channel}/playlist/reorder', [LiveChannelController::class, 'reorderPlaylist'])
        ->name('vod-channels.playlist.reorder');

    // SETTINGS - PROTECTED (both view and update)
    Route::get('/vod-channels/{channel}/settings', [LiveChannelController::class, 'settings'])
        ->name('vod-channels.settings');

    Route::post('/vod-channels/{channel}/settings', [LiveChannelController::class, 'updateSettings'])
        ->name('vod-channels.settings.update');

    // ───────────── VIDEO CATEGORIES ─────────────
    Route::get('/video-categories', [VideoCategoryController::class, 'index'])
        ->name('video-categories.index');

    Route::post('/video-categories', [VideoCategoryController::class, 'store'])
        ->name('video-categories.store');

    Route::get('/video-categories/{category}/edit', [VideoCategoryController::class, 'edit'])
        ->name('video-categories.edit');

    Route::patch('/video-categories/{category}', [VideoCategoryController::class, 'update'])
        ->name('video-categories.update');

    Route::delete('/video-categories/{category}', [VideoCategoryController::class, 'destroy'])
        ->name('video-categories.destroy');

    // ───────────── VIDEO LIBRARY ─────────────
    Route::get('/videos', [VideoController::class, 'index'])
        ->name('videos.index');

    Route::get('/videos/create', [VideoController::class, 'create'])
        ->name('videos.create');

    Route::post('/videos', [VideoController::class, 'store'])
        ->name('videos.store');

    Route::get('/videos/{video}/edit', [VideoController::class, 'edit'])
        ->name('videos.edit');

    Route::patch('/videos/{video}', [VideoController::class, 'update'])
        ->name('videos.update');

    Route::post('/videos/bulk-category', [VideoController::class, 'bulkCategory'])
        ->name('videos.bulk-category');

    // ───────────── ENCODING QUEUE ─────────────

    Route::get('/encoding-jobs', [EncodingJobController::class, 'index'])
        ->name('encoding-jobs.index');

    Route::post('/vod-channels/{channel}/queue-encoding', [EncodingJobController::class, 'queueChannel'])
        ->name('encoding-jobs.queue-channel');

    // FILE BROWSER
    Route::get('/file-browser', [FileBrowserController::class, 'index'])
        ->name('file-browser.index');

    // PROFILE
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
