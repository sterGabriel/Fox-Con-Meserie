<!-- STREAM INFO TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">📊 Playlist Media Analysis</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-500/20">
                    <th class="text-left py-3 px-4 text-slate-400">File</th>
                    <th class="text-left py-3 px-4 text-slate-400">Codec</th>
                    <th class="text-left py-3 px-4 text-slate-400">FPS</th>
                    <th class="text-left py-3 px-4 text-slate-400">Bitrate</th>
                    <th class="text-left py-3 px-4 text-slate-400">Resolution</th>
                    <th class="text-left py-3 px-4 text-slate-400">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channel->playlistItems as $item)
                    <tr class="border-b border-slate-500/10 hover:bg-slate-800/20">
                        <td class="py-3 px-4 text-slate-100">{{ $item->video->title ?? 'Unknown' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $item->video->video_codec ?? 'h264' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $item->video->fps ?? '--' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $item->video->video_bitrate ?? '--' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $item->video->resolution ?? '1280x720' }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 bg-green-500/20 text-green-300 text-xs rounded">✅ OK</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-400">No videos in playlist</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 bg-blue-500/10 border border-blue-400/30 rounded-lg">
        <p class="text-sm text-blue-300">
            <strong>Legend:</strong>
            <span class="inline-block px-2 py-1 bg-green-500/20 text-green-300 text-xs rounded ml-2">✅ MATCH PROFILE</span>
            <span class="inline-block px-2 py-1 bg-yellow-500/20 text-yellow-300 text-xs rounded ml-2">⚠️ NEEDS SCALE</span>
            <span class="inline-block px-2 py-1 bg-red-500/20 text-red-300 text-xs rounded ml-2">❌ WEIRD FPS</span>
        </p>
    </div>
</div>
