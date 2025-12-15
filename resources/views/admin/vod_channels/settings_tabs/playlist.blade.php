<!-- PLAYLIST TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-slate-100">🎬 Playlist Videos</h2>
        <a href="{{ route('vod-channels.playlist', $channel) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
            + Add Video
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-500/20">
                    <th class="text-left py-3 px-4 text-slate-400">#</th>
                    <th class="text-left py-3 px-4 text-slate-400">Title</th>
                    <th class="text-left py-3 px-4 text-slate-400">Duration</th>
                    <th class="text-left py-3 px-4 text-slate-400">Status</th>
                    <th class="text-left py-3 px-4 text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channel->playlistItems as $item)
                    <tr class="border-b border-slate-500/10 hover:bg-slate-800/20 transition">
                        <td class="py-3 px-4 text-slate-300">{{ $loop->iteration }}</td>
                        <td class="py-3 px-4 text-slate-100">{{ $item->video->title ?? 'Unknown' }}</td>
                        <td class="py-3 px-4 text-slate-400">
                            {{ $item->video->duration ?? '--:--:--' }}
                        </td>
                        <td class="py-3 px-4">
                            @if($item->video->encoding_status === 'completed')
                                <span class="inline-block px-2 py-1 bg-green-500/20 text-green-300 text-xs rounded">✅ Encoded</span>
                            @else
                                <span class="inline-block px-2 py-1 bg-yellow-500/20 text-yellow-300 text-xs rounded">⏳ Pending</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('vod-channels.playlist.remove', [$channel, $item]) }}" class="text-red-400 hover:text-red-300 text-xs" onclick="return confirm('Remove from playlist?')">Remove</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-400">No videos in playlist yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 pt-6 border-t border-slate-500/20">
        <button type="button" class="px-6 py-2 bg-blue-600/20 text-blue-300 rounded-lg hover:bg-blue-600/30 transition text-sm font-medium">
            🎬 Queue Encode (All Pending)
        </button>
    </div>
</div>
