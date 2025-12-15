<!-- ENCODING TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">⚙️ Encoding Profile</h2>

    <div class="space-y-6">
        <!-- Profile Dropdown -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Select Profile</label>
            <select name="encode_profile_id" id="profile-select" class="w-full px-4 py-3 rounded-lg border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200">
                <option value="">-- Select a profile --</option>
                @foreach($profiles as $prof)
                    <option value="{{ $prof->id }}" {{ old('encode_profile_id', $channel->encode_profile_id) == $prof->id ? 'selected' : '' }}>
                        {{ $prof->name }} ({{ $prof->width }}x{{ $prof->height }}, {{ $prof->fps }}fps, {{ $prof->video_bitrate_k }}k)
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Manual Override -->
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="manual_override_encoding" id="manual-override" value="1" {{ old('manual_override_encoding', $channel->manual_override_encoding ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
            <span class="text-slate-300">🔧 Manual Override (Advanced)</span>
        </label>

        <!-- Manual Fields (hidden by default) -->
        <div id="manual-fields" class="space-y-4 p-4 bg-slate-800/20 rounded-lg border border-slate-600/20 {{ old('manual_override_encoding', $channel->manual_override_encoding ?? false) ? '' : 'hidden' }}">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Width (px)</label>
                    <input type="number" name="manual_width" value="{{ old('manual_width', $channel->manual_width ?? 1280) }}" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Height (px)</label>
                    <input type="number" name="manual_height" value="{{ old('manual_height', $channel->manual_height ?? 720) }}" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">FPS</label>
                    <input type="number" name="manual_fps" value="{{ old('manual_fps', $channel->manual_fps ?? 60) }}" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Video Codec</label>
                    <select name="manual_codec" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                        <option value="libx264" {{ old('manual_codec', $channel->manual_codec ?? 'libx264') === 'libx264' ? 'selected' : '' }}>H.264</option>
                        <option value="libx265" {{ old('manual_codec', $channel->manual_codec ?? '') === 'libx265' ? 'selected' : '' }}>H.265</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Preset</label>
                    <select name="manual_preset" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                        <option value="veryfast">Very Fast</option>
                        <option value="fast">Fast</option>
                        <option value="medium" selected>Medium</option>
                        <option value="slow">Slow</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Video Bitrate (k)</label>
                    <input type="number" name="manual_bitrate" value="{{ old('manual_bitrate', $channel->manual_bitrate ?? 2500) }}" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Audio Bitrate (k)</label>
                    <input type="number" name="manual_audio_bitrate" value="{{ old('manual_audio_bitrate', $channel->manual_audio_bitrate ?? 128) }}" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Audio Codec</label>
                    <select name="manual_audio_codec" class="w-full px-3 py-2 text-sm bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                        <option value="aac" selected>AAC</option>
                        <option value="libmp3lame">MP3</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- FFmpeg Preview -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">FFmpeg Command (Preview)</label>
            <div class="p-3 bg-slate-950/50 border border-slate-600/20 rounded-lg font-mono text-xs text-slate-300 h-32 overflow-y-auto">
                <div id="ffmpeg-preview">
                    ffmpeg -re -i input.mp4 ...
                </div>
            </div>
            <button type="button" id="preview-btn" class="mt-2 px-4 py-2 bg-slate-800/50 text-slate-300 text-sm rounded-lg hover:bg-slate-700/50 transition">
                🔄 Refresh Preview
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('manual-override').addEventListener('change', function() {
    document.getElementById('manual-fields').classList.toggle('hidden');
});

document.getElementById('preview-btn').addEventListener('click', function(e) {
    e.preventDefault();
    fetch('{{ route("vod-channels.preview-ffmpeg", $channel) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            profile_id: document.getElementById('profile-select').value,
            manual_override: document.getElementById('manual-override').checked
        })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('ffmpeg-preview').textContent = data.command || 'Error generating command';
    });
});
</script>
