<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CreateVideoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LiveChannelController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\FileBrowserController;
use App\Http\Controllers\Admin\VideoCategoryController;
use App\Http\Controllers\Admin\EncodingJobController;
use App\Http\Controllers\Admin\EncodeProfileController;
use App\Http\Controllers\Admin\MediaImportController;
use App\Http\Controllers\Admin\CategoryScanController;
use App\Http\Controllers\Api\VideoApiController;
use App\Http\Controllers\Api\EncodingJobApiController;
use App\Http\Controllers\Api\LiveChannelApiController;
use App\Http\Controllers\Admin\TmdbSettingsController;
use App\Http\Controllers\Admin\SeriesRenameController;
use App\Http\Controllers\EpgController;

// ROOT → redirect to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ═══════════════════════════════════════════════════════════════
// 🔓 PUBLIC ROUTES (NO AUTH REQUIRED) - ONLY LOGO PREVIEW
// ═══════════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════════
// 🔓 PUBLIC ROUTES (NO AUTH REQUIRED) - STREAMING + LOGO
// ═══════════════════════════════════════════════════════════════

Route::get('/vod-channels/{channel}/logo-preview', [LiveChannelController::class, 'logoPreview'])
    ->name('vod-channels.logo.preview');

// Master M3U8 playlist (all VOD channels) for external players / Xtream apps
Route::get('/streams/all.m3u8', function () {
    $domain = rtrim((string) config('app.streaming_domain', ''), '/');
    if ($domain === '' || str_contains($domain, 'localhost')) {
        $domain = rtrim((string) request()->getSchemeAndHttpHost(), '/');
    }
    $channels = \App\Models\LiveChannel::query()
        ->orderBy('id')
        ->get(['id', 'name']);

    $out = "#EXTM3U\n";
    foreach ($channels as $ch) {
        $name = str_replace(["\n", "\r"], ' ', (string) $ch->name);
        $url = $domain . "/streams/{$ch->id}/hls/stream.m3u8";
        $out .= "#EXTINF:-1," . $name . "\n";
        $out .= $url . "\n";
    }

    return response($out, 200, [
        'Content-Type' => 'application/x-mpegURL; charset=utf-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Access-Control-Allow-Origin' => '*',
    ]);
});

// Streaming outputs (TS + HLS)
Route::get('/streams/{channel}/{file}', function ($channel, $file) {
    $path = storage_path("app/streams/{$channel}/{$file}");
    if (file_exists($path)) {
        $mime = 'application/octet-stream';
        if (str_ends_with($file, '.ts')) $mime = 'video/mp2t';
        if (str_ends_with($file, '.m3u8')) $mime = 'application/vnd.apple.mpegurl';
        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
    abort(404);
});

// HLS segments in subdirectories
Route::get('/streams/{channel}/{subdir}/{file}', function ($channel, $subdir, $file) {
    $path = storage_path("app/streams/{$channel}/{$subdir}/{$file}");
    if (file_exists($path)) {
        $mime = 'application/octet-stream';
        if (str_ends_with($file, '.ts')) $mime = 'video/mp2t';
        if (str_ends_with($file, '.m3u8')) $mime = 'application/vnd.apple.mpegurl';
        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
    abort(404);
});

// XMLTV EPG (All channels) — dynamic 7-day rolling window
Route::get('/epg/all.xml', [EpgController::class, 'all'])
    ->name('epg.all');


Route::middleware(['auth', 'verified'])->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ───────────── FOX NAV PLACEHOLDERS (Spec parity) ─────────────
    Route::view('/users', 'admin.fox.placeholder', ['title' => 'Users'])->name('fox.users');
    Route::view('/fonts', 'admin.fox.placeholder', ['title' => 'Fonts'])->name('fox.fonts');
    Route::view('/advertisements', 'admin.fox.placeholder', ['title' => 'Advertisements'])->name('fox.advertisements');
    Route::view('/broadcast', 'admin.fox.placeholder', ['title' => 'Broadcast'])->name('fox.broadcast');

    Route::view('/vod-radio-channels', 'admin.fox.placeholder', ['title' => 'Vod Radio Channels'])->name('fox.vod-radio-channels');
    Route::view('/live-radio-channels', 'admin.fox.placeholder', ['title' => 'Live Radio Channels'])->name('fox.live-radio-channels');
    Route::view('/live-channels', 'admin.fox.placeholder', ['title' => 'Live Channels'])->name('fox.live-channels');
    Route::view('/youtube-video-channels', 'admin.fox.placeholder', ['title' => 'Youtube Video Channels'])->name('fox.youtube-video-channels');
    Route::view('/youtube-live-channels', 'admin.fox.placeholder', ['title' => 'Youtube Live Channels'])->name('fox.youtube-live-channels');
    Route::view('/codec-channels', 'admin.fox.placeholder', ['title' => 'Codec Channels'])->name('fox.codec-channels');
    Route::view('/vod-movies', 'admin.fox.placeholder', ['title' => 'Vod Movies'])->name('fox.vod-movies');
    Route::view('/series', 'admin.fox.placeholder', ['title' => 'Series'])->name('fox.series');

    // Series tools (rename)
    Route::get('/series/rename-muzica', [SeriesRenameController::class, 'muzica'])
        ->name('fox.series.rename-muzica');

    Route::post('/series/rename-muzica', [SeriesRenameController::class, 'renameMuzica'])
        ->name('fox.series.rename-muzica.rename');

    Route::post('/series/rename-muzica/bulk', [SeriesRenameController::class, 'bulkRenameMuzica'])
        ->name('fox.series.rename-muzica.bulk');

    // ───────────── VOD CHANNELS ─────────────

    Route::get('/vod-channels', [LiveChannelController::class, 'index'])
        ->name('vod-channels.index');

    // API endpoint for new HTML/JS interface
    Route::get('/api/vod/channels', [LiveChannelController::class, 'apiIndex'])
        ->name('api.vod-channels');

    Route::get('/vod-channels/create', [LiveChannelController::class, 'create'])
        ->name('vod-channels.create-old');

    Route::get('/vod-channels/create-new', [LiveChannelController::class, 'createChannel'])
        ->name('vod-channels.create-new');

    Route::post('/vod-channels', [LiveChannelController::class, 'store'])
        ->name('vod-channels.store');

    Route::delete('/vod-channels/{channel}', [LiveChannelController::class, 'destroy'])
        ->name('vod-channels.destroy');

    // PLAYLIST - PROTECTED
    Route::get('/vod-channels/{channel}/playlist', [LiveChannelController::class, 'playlist'])
        ->name('vod-channels.playlist');

    // Popup player (encoded TS)
    Route::get('/vod-channels/{channel}/playlist/{item}/player', [LiveChannelController::class, 'playlistPlayer'])
        ->name('vod-channels.playlist.player');

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

    // ENCODING / IMPORT - PROTECTED
    Route::get('/vod-channels/{channel}/encoding', [LiveChannelController::class, 'encoding'])
        ->name('vod-channels.encoding')
        ->missing(function () {
            return redirect()
                ->route('vod-channels.index')
                ->with('error', 'Canalul nu a fost găsit (posibil a fost șters).');
        });

    Route::post('/vod-channels/{channel}/settings', [LiveChannelController::class, 'updateSettings'])
        ->name('vod-channels.settings.update');

    // CREATE VIDEO (NEW)
    Route::get('/create-video/{channel}', [CreateVideoController::class, 'show'])
        ->name('create-video.show');

    // ENGINE CONTROL - START / STOP / STATUS
    Route::post('/vod-channels/{channel}/engine/start', [LiveChannelController::class, 'startChannel'])
        ->name('vod-channels.engine.start');

    Route::post('/vod-channels/{channel}/engine/stop', [LiveChannelController::class, 'stopChannel'])
        ->name('vod-channels.engine.stop');

    Route::get('/vod-channels/{channel}/engine/status', [LiveChannelController::class, 'channelStatus'])
        ->name('vod-channels.engine.status');

    Route::post('/vod-channels/{channel}/engine/test-preview', [LiveChannelController::class, 'testPreview'])
        ->name('vod-channels.engine.test-preview');

    Route::post('/vod-channels/{channel}/engine/test-encode', [LiveChannelController::class, 'testEncode'])
        ->name('vod-channels.engine.test-encode');

    Route::post('/vod-channels/{channel}/engine/delete-test', [LiveChannelController::class, 'deleteTestOutput'])
        ->name('vod-channels.engine.delete-test');

    Route::get('/vod-channels/{channel}/engine/outputs', [LiveChannelController::class, 'outputStreams'])
        ->name('vod-channels.engine.outputs');

    // NOW PLAYING (for EPG helpers)
    Route::get('/vod-channels/{channel}/now-playing', [LiveChannelController::class, 'nowPlaying'])
        ->name('vod-channels.now-playing');

    Route::post('/vod-channels/{channel}/engine/start-looping', [LiveChannelController::class, 'startChannelWithLooping'])
        ->name('vod-channels.engine.start-looping');

    Route::post('/vod-channels/{channel}/engine/start-encoding', [LiveChannelController::class, 'startEncoding'])
        ->name('vod-channels.engine.start-encoding');

    Route::get('/vod-channels/{channel}/engine/encoding-jobs', [LiveChannelController::class, 'getEncodingJobs'])
        ->name('vod-channels.engine.encoding-jobs');

    Route::post('/vod-channels/{channel}/engine/encoding-jobs/{job}/cancel', [LiveChannelController::class, 'cancelEncodingJob'])
        ->name('vod-channels.engine.encoding-jobs.cancel');

    Route::get('/vod-channels/{channel}/engine/check-encoded', [LiveChannelController::class, 'checkEncodedFiles'])
        ->name('vod-channels.engine.check-encoded');

    // PREVIEW FFMPEG COMMAND
    Route::post('/vod-channels/{channel}/preview-ffmpeg', [LiveChannelController::class, 'previewFFmpeg'])
        ->name('vod-channels.preview-ffmpeg');

    // SYNC PLAYLIST FROM CATEGORY
    Route::post('/vod-channels/{channel}/sync-playlist-from-category', [LiveChannelController::class, 'syncPlaylistFromCategory'])
        ->name('vod-channels.sync-playlist-from-category');

    // ───────────── VIDEO CATEGORIES ─────────────
    // Compatibility redirects (old URLs)
    Route::redirect('/category', '/video-categories', 302)->name('category.redirect');
    Route::redirect('/categories', '/video-categories', 302);
    Route::redirect('/video_categories', '/video-categories', 302);

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

    // ───────────── FILE BROWSER & IMPORT ─────────────
    Route::get('/video-categories/{category}/browse', [FileBrowserController::class, 'browse'])
        ->name('admin.video_categories.browse');

    Route::post('/video-categories/{category}/import', [FileBrowserController::class, 'import'])
        ->name('admin.video_categories.import');

    Route::post('/video-categories/preview', [FileBrowserController::class, 'generatePreview'])
        ->name('video-categories.preview');

    // ───────────── CATEGORY SCAN (FOX) ─────────────
    Route::get('/video-categories/{category}/scan', [CategoryScanController::class, 'showCategory'])
        ->name('admin.video_categories.scan');

    Route::post('/video-categories/{category}/scan', [CategoryScanController::class, 'scan'])
        ->name('admin.video_categories.scan.run');

    Route::post('/video-categories/{category}/scan/import', [CategoryScanController::class, 'import'])
        ->name('admin.video_categories.scan.import');

    Route::post('/video-categories/{category}/scan/delete-file', [CategoryScanController::class, 'deleteFile'])
        ->name('admin.video_categories.scan.delete-file');
    // ───────────── VIDEO LIBRARY ─────────────
    Route::get('/videos', [VideoController::class, 'index'])
        ->name('videos.index');

    Route::get('/videos/{video}/info', [VideoController::class, 'getInfo'])
        ->name('videos.info');

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

    // FFPROBE - Get video metadata
    Route::get('/videos/{video}/probe', [VideoController::class, 'probe'])
        ->name('videos.probe');

    // PLAY - Stream original video file (auth protected)
    Route::get('/videos/{video}/play', [VideoController::class, 'play'])
        ->name('videos.play');

    // ───────────── ENCODING QUEUE ─────────────

    Route::get('/encoding-jobs', [EncodingJobController::class, 'index'])
        ->name('encoding-jobs.index');

    Route::post('/vod-channels/{channel}/queue-encoding', [EncodingJobController::class, 'queueChannel'])
        ->name('encoding-jobs.queue-channel');

    // FILE BROWSER
    Route::get('/file-browser', [FileBrowserController::class, 'index'])
        ->name('file-browser.index');

    // ───────────── ENCODE PROFILES ─────────────
    Route::get('/encode-profiles', [EncodeProfileController::class, 'index'])
        ->name('encode-profiles.index');

    Route::get('/encode-profiles/create', [EncodeProfileController::class, 'create'])
        ->name('encode-profiles.create');

    Route::post('/encode-profiles', [EncodeProfileController::class, 'store'])
        ->name('encode-profiles.store');

    Route::get('/encode-profiles/{profile}/edit', [EncodeProfileController::class, 'edit'])
        ->name('encode-profiles.edit');

    Route::patch('/encode-profiles/{profile}', [EncodeProfileController::class, 'update'])
        ->name('encode-profiles.update');

    Route::post('/encode-profiles/{profile}/duplicate', [EncodeProfileController::class, 'duplicate'])
        ->name('encode-profiles.duplicate');

    Route::delete('/encode-profiles/{profile}', [EncodeProfileController::class, 'destroy'])
        ->name('encode-profiles.destroy');

    // MEDIA IMPORT
    Route::get('/media/import', [MediaImportController::class, 'index'])
        ->name('media.import');

    Route::post('/media/import', [MediaImportController::class, 'import'])
        ->name('media.import.store');

    // PROFILE
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SETTINGS
    Route::get('/settings/tmdb', [TmdbSettingsController::class, 'edit'])
        ->name('settings.tmdb');
    Route::post('/settings/tmdb', [TmdbSettingsController::class, 'update'])
        ->name('settings.tmdb.update');

    // ═══════════════════════════════════════════════════════════════
    // CREATE VIDEO API ENDPOINTS (NEW)
    // ═══════════════════════════════════════════════════════════════

    // Get videos by category
    Route::get('/api/videos', [VideoApiController::class, 'index'])
        ->name('api.videos.index');

    Route::post('/api/videos/probe', [VideoApiController::class, 'probe'])
        ->name('api.videos.probe');

    Route::post('/api/videos/tmdb-scan', [VideoApiController::class, 'tmdbScan'])
        ->name('api.videos.tmdb-scan');

    Route::post('/api/videos/tmdb-scan-all', [VideoApiController::class, 'tmdbScanAll'])
        ->name('api.videos.tmdb-scan-all');

    // Delete video
    Route::delete('/api/videos/{video}', [VideoApiController::class, 'destroy'])
        ->name('api.videos.destroy');

    // Encoding jobs API
    Route::get('/api/encoding-jobs', [EncodingJobApiController::class, 'index'])
        ->name('api.encoding-jobs.index');
    Route::post('/api/encoding-jobs', [EncodingJobApiController::class, 'store'])
        ->name('api.encoding-jobs.store');
    Route::post('/api/encoding-jobs/bulk', [EncodingJobApiController::class, 'bulk'])
        ->name('api.encoding-jobs.bulk');
    Route::post('/api/encoding-jobs/{job}/test', [EncodingJobApiController::class, 'test'])
        ->name('api.encoding-jobs.test');
    Route::delete('/api/encoding-jobs/{job}', [EncodingJobApiController::class, 'destroy'])
        ->name('api.encoding-jobs.destroy');

    // Live channels API
    Route::post('/api/live-channels', [LiveChannelApiController::class, 'store'])
        ->name('api.live-channels.store');
    Route::post('/api/live-channels/{channel}/settings', [LiveChannelApiController::class, 'saveSettings'])
        ->name('api.live-channels.save-settings');
});

require __DIR__.'/auth.php';
