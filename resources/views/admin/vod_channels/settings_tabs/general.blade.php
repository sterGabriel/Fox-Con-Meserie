<!-- GENERAL TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">📋 Channel General Info</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Channel Name</label>
            <div class="px-4 py-3 rounded-lg bg-slate-950/30 border border-slate-500/20 text-slate-200 font-semibold">
                {{ $channel->name }}
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
            <select name="video_category" class="w-full px-4 py-2 rounded-lg border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200">
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (int)old('video_category', $channel->video_category ?? 0) === (int)$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_24_7_channel" value="1" {{ old('is_24_7_channel', $channel->is_24_7_channel ?? true) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
                <span class="text-slate-300">🔴 24/7 Channel (Loop VOD Playlist)</span>
            </label>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-300 mb-2">Description (optional)</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-lg border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 placeholder-slate-500">{{ old('description', $channel->description ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="auto_sync_playlist" value="1" {{ old('auto_sync_playlist', $channel->auto_sync_playlist ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-green-500">
                <span class="text-slate-300">🔁 Auto-sync playlist from category</span>
            </label>
            <p class="text-xs text-slate-400 mt-2">When enabled, playlist will always match category videos</p>
        </div>
    </div>
</div>

<!-- CATEGORY PLAYLIST PREVIEW -->
@if($channel->video_category_id && $categoryStats['total_videos'] > 0)
<div class="mt-6 rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-100">✅ Category Playlist Preview</h3>
            <p class="text-sm text-slate-400 mt-1">Category: <strong>{{ $channel->videoCategory->name ?? 'N/A' }}</strong></p>
        </div>
        <button type="button" onclick="syncPlaylistFromCategory()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
            🔵 Sync Now
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-950/30 rounded-lg p-4 border border-slate-500/20">
            <p class="text-xs text-slate-400 uppercase tracking-wide">Total Videos</p>
            <p class="text-2xl font-bold text-slate-100 mt-1">{{ $categoryStats['total_videos'] }}</p>
        </div>
        <div class="bg-slate-950/30 rounded-lg p-4 border border-slate-500/20">
            <p class="text-xs text-slate-400 uppercase tracking-wide">Total Duration</p>
            <p class="text-2xl font-bold text-slate-100 mt-1">{{ floor($categoryStats['total_duration'] / 3600) }}h {{ floor(($categoryStats['total_duration'] % 3600) / 60) }}m</p>
        </div>
        <div class="bg-slate-950/30 rounded-lg p-4 border border-slate-500/20">
            <p class="text-xs text-slate-400 uppercase tracking-wide">In Playlist</p>
            <p class="text-2xl font-bold text-slate-100 mt-1" id="playlist-count">0</p>
        </div>
    </div>

    <!-- Videos Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-700/30 bg-slate-800/50">
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-12">#</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium">Title</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-24">Duration</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-24">Resolution</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-16">FPS</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-20">Codec</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-24">Audio</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-32">Imported</th>
                    <th class="px-4 py-3 text-left text-slate-400 font-medium w-20">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categoryVideos as $index => $video)
                <tr class="border-b border-slate-700/20 hover:bg-slate-700/10 transition">
                    <td class="px-4 py-3 text-slate-400">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 text-slate-200 font-medium truncate max-w-xs" title="{{ $video->title }}">{{ $video->title }}</td>
                    <td class="px-4 py-3 text-slate-400">
                        @php
                            $h = floor($video->duration_seconds / 3600);
                            $m = floor(($video->duration_seconds % 3600) / 60);
                            $s = $video->duration_seconds % 60;
                        @endphp
                        {{ sprintf('%02d:%02d:%02d', $h, $m, $s) }}
                    </td>
                    <td class="px-4 py-3 text-slate-400">
                        @php
                            $meta = json_decode($video->metadata, true);
                            $res = ($meta['video']['width'] ?? '?') . 'x' . ($meta['video']['height'] ?? '?');
                        @endphp
                        <span class="bg-slate-700/30 px-2 py-1 rounded text-xs">{{ $res }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-sm">
                        @php
                            $fps = $meta['video']['r_frame_rate'] ?? 'N/A';
                            if (is_string($fps) && str_contains($fps, '/')) {
                                [$num, $den] = explode('/', $fps);
                                $fps = round($num / $den, 2);
                            }
                        @endphp
                        {{ $fps }}
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-sm">
                        <span class="bg-blue-500/20 text-blue-300 px-2 py-1 rounded text-xs">
                            {{ $meta['video']['codec'] ?? 'unknown' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-sm">
                        {{ ($meta['audio']['channels'] ?? 0) }} ch
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ $video->created_at->format('Y-m-d H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-green-500/20 text-green-300 px-2 py-1 rounded text-xs font-medium">
                            ✓ Imported
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-6 text-center text-slate-400">
                        No videos in category
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($categoryVideos->count() < $categoryStats['total_videos'])
    <p class="mt-4 text-xs text-slate-400">
        Showing first {{ $categoryVideos->count() }} of {{ $categoryStats['total_videos'] }} videos. All videos will be synced.
    </p>
    @endif
</div>
@elseif($channel->video_category_id)
<div class="mt-6 rounded-2xl border border-yellow-500/20 bg-yellow-900/20 p-6 backdrop-blur-sm">
    <p class="text-yellow-300">📭 Category has no videos yet.</p>
</div>
@else
<div class="mt-6 rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <p class="text-slate-400">📌 Select a category above to see playlist preview and sync options.</p>
</div>
@endif

<script>
function syncPlaylistFromCategory() {
    if (!confirm('Replace entire playlist with all videos from category?')) {
        return;
    }

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ Syncing...';

    fetch('{{ route("vod-channels.sync-playlist-from-category", $channel) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ ' + data.message);
            btn.disabled = false;
            btn.textContent = '🔵 Sync Now';
        }
    })
    .catch(e => {
        alert('❌ Error: ' + e.message);
        btn.disabled = false;
        btn.textContent = '🔵 Sync Now';
    });
}

// Update playlist count on page load
function updatePlaylistCount() {
    const totalVideos = {{ $categoryStats['total_videos'] }};
    // This will be updated after sync
    const el = document.getElementById('playlist-count');
    if (el) el.textContent = totalVideos;
}

updatePlaylistCount();
</script>
