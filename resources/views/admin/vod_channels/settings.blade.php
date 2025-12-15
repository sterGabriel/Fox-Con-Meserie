@extends('layouts.panel')

@section('content')
    <div class="mb-8">
        <h1 class="text-4xl font-black text-slate-100">
            ⚙️ Settings
        </h1>
        <p class="text-slate-400 mt-2">Configure {{ $channel->name }}</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 border border-emerald-500/30 bg-emerald-500/10 rounded-xl flex items-start gap-3 animate-slideIn">
            <span class="text-2xl">✅</span>
            <div>
                <div class="font-bold text-emerald-300">Settings saved!</div>
                <div class="text-sm text-emerald-200">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 border border-red-500/30 bg-red-500/10 rounded-xl animate-slideIn">
            <div class="font-bold text-red-300 mb-2">❌ Validation Errors</div>
            <ul class="list-disc pl-6 space-y-1 text-red-200 text-sm">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('vod-channels.settings.update', $channel) }}"
          enctype="multipart/form-data"
          class="space-y-6 pb-32">
        @csrf

        {{-- CHANNEL INFO CARD --}}
        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
            <h2 class="text-lg font-semibold mb-6 text-slate-100">
                📋 Channel Info
            </h2>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">Channel Name</label>
                    <div class="px-4 py-3 rounded-xl bg-slate-950/30 border border-slate-500/20 text-slate-200 font-semibold">
                        {{ $channel->name }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">Video Category</label>
                    <select name="video_category" class="w-full px-4 py-3 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ (int)old('video_category', $channel->video_category ?? 0) === (int)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ENCODING PROFILE CARD --}}
        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
            <h2 class="text-lg font-semibold mb-6 text-slate-100">
                ⚙️ Encoding Profile
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">📐 Resolution</label>
                    <input type="text" name="resolution" required
                           value="{{ old('resolution', $channel->resolution ?? '1280x720') }}"
                           placeholder="1280x720"
                           class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">📹 Video Bitrate</label>
                    <div class="relative">
                        <input type="number" name="video_bitrate" required min="200" max="50000"
                               value="{{ old('video_bitrate', $channel->video_bitrate ?? 1500) }}"
                               class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                        <span class="absolute right-4 top-2.5 text-slate-500 text-sm">kbps</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">🔊 Audio Bitrate</label>
                    <div class="relative">
                        <input type="number" name="audio_bitrate" required min="32" max="1024"
                               value="{{ old('audio_bitrate', $channel->audio_bitrate ?? 128) }}"
                               class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                        <span class="absolute right-4 top-2.5 text-slate-500 text-sm">kbps</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">⏱️ FPS</label>
                    <input type="number" name="fps" required min="10" max="120"
                           value="{{ old('fps', $channel->fps ?? 25) }}"
                           class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-300 mb-2">🎵 Audio Codec</label>
                    <select name="audio_codec" required class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                        @php $codec = old('audio_codec', $channel->audio_codec ?? 'aac'); @endphp
                        <option value="aac" {{ $codec === 'aac' ? 'selected' : '' }}>AAC</option>
                        <option value="mp3" {{ $codec === 'mp3' ? 'selected' : '' }}>MP3</option>
                        <option value="ac3" {{ $codec === 'ac3' ? 'selected' : '' }}>AC3</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- LIVE ENCODING PROFILE CARD (NEW) --}}
        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
            <h2 class="text-lg font-semibold mb-6 text-slate-100">
                📡 LIVE Streaming Profile
            </h2>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">🎬 Select Preset Profile</label>
                    <select name="encode_profile_id" class="w-full px-4 py-3 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                        <option value="">-- Use Default (LIVE 720p) --</option>
                        @foreach($liveProfiles as $profile)
                            <option value="{{ $profile->id }}" 
                                {{ (int)old('encode_profile_id', $channel->encode_profile_id ?? 0) === (int)$profile->id ? 'selected' : '' }}>
                                {{ $profile->name }} ({{ $profile->video_bitrate_k }}kbps {{ $profile->width }}x{{ $profile->height }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-2">💡 Presets optimized for 24/7 streaming (MPEGTS TS format, CBR bitrate, 48kHz audio)</p>
                </div>

                <div id="manual-mode-section" style="display: none;">
                    <h3 class="text-sm font-semibold text-amber-300 mb-3">⚠️ Manual Override (Advanced)</h3>
                    <div class="space-y-3 p-4 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-slate-300">Manual Bitrate (kbps)</label>
                                <input type="number" name="manual_bitrate" min="500" max="20000"
                                       value="{{ old('manual_bitrate', 2500) }}"
                                       class="w-full px-3 py-2 rounded text-xs bg-slate-950/30 border border-slate-500/20 text-slate-200">
                            </div>
                            <div>
                                <label class="text-xs text-slate-300">Manual Preset</label>
                                <select name="manual_preset" class="w-full px-3 py-2 rounded text-xs bg-slate-950/30 border border-slate-500/20 text-slate-200">
                                    <option>superfast</option>
                                    <option selected>veryfast</option>
                                    <option>fast</option>
                                    <option>medium</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="flex items-center p-3 border border-slate-500/20 rounded-xl hover:bg-slate-800/40 hover:border-amber-400/30 cursor-pointer transition-all">
                    <input type="checkbox" id="manual-override-toggle" name="manual_encode_enabled" value="1"
                           {{ old('manual_encode_enabled', $channel->manual_encode_enabled) ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-amber-500 focus:ring-amber-500/40">
                    <span class="ml-3 text-sm font-semibold text-slate-200">🔧 Manual Override (Advanced)</span>
                </label>

                <div id="preview-section" class="p-4 bg-slate-800/30 border border-slate-500/30 rounded-xl">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-semibold text-slate-300">📜 FFmpeg Command Preview</span>
                        <button type="button" id="preview-btn" class="text-xs px-3 py-1 rounded bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition">
                            🔄 Refresh
                        </button>
                    </div>
                    <pre id="preview-output" class="text-xs bg-slate-900/50 border border-slate-600/50 rounded p-3 text-green-300 font-mono overflow-auto max-h-40" style="line-height: 1.4;">
ffmpeg -re -i input.mp4 ... (loading)
                    </pre>
                </div>
            </div>
        </div>

        {{-- LOGO & OVERLAYS CARD --}}
        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
            <h2 class="text-lg font-semibold mb-6 text-slate-100">
                🎨 Logo & Overlays
            </h2>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-300 mb-3">🖼️ Upload New Logo (PNG)</label>
                <input type="file" name="logo_upload" accept="image/png"
                       class="w-full px-4 py-3 rounded-xl border-2 border-dashed border-slate-500/30 hover:border-blue-400/50 bg-slate-950/30 text-slate-300 cursor-pointer transition-all file:rounded-lg file:border-0 file:bg-blue-500/20 file:text-blue-300 file:font-semibold file:px-4 file:py-2">
                <p class="text-xs text-slate-400 mt-2">📁 Max 5MB. Transparent PNG recommended.</p>
            </div>

            @if($channel->logo_path)
                <div class="mb-6 p-4 border border-blue-500/30 bg-blue-500/10 rounded-xl">
                    <p class="text-sm font-semibold text-blue-300 mb-3">✅ Current Logo:</p>
                    <img src="{{ route('vod-channels.logo.preview', $channel) }}"
                         style="max-height: 150px; background: linear-gradient(135deg, #1e293b, #0f172a); padding: 12px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3);">
                    <p class="text-xs text-blue-300 mt-3 font-mono break-all">{{ $channel->logo_path }}</p>
                </div>
            @else
                <div class="mb-6 p-4 border border-amber-500/30 bg-amber-500/10 rounded-xl">
                    <p class="text-sm font-semibold text-amber-300">⚠️ No logo uploaded yet</p>
                </div>
            @endif

            <div class="space-y-3">
                <label class="flex items-center p-4 border border-slate-500/20 rounded-xl hover:bg-slate-800/40 hover:border-blue-400/30 cursor-pointer transition-all">
                    <input type="checkbox" name="overlay_title" value="1"
                           {{ old('overlay_title', $channel->overlay_title) ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-blue-500 focus:ring-blue-500/40">
                    <span class="ml-3 text-sm font-semibold text-slate-200">📝 Show Title Overlay</span>
                </label>

                <label class="flex items-center p-4 border border-slate-500/20 rounded-xl hover:bg-slate-800/40 hover:border-blue-400/30 cursor-pointer transition-all">
                    <input type="checkbox" name="overlay_timer" value="1"
                           {{ old('overlay_timer', $channel->overlay_timer) ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-blue-500 focus:ring-blue-500/40">
                    <span class="ml-3 text-sm font-semibold text-slate-200">⏱️ Show Timer Overlay</span>
                </label>
            </div>
        </div>

        {{-- OUTPUT PATHS CARD --}}
        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
            <h2 class="text-lg font-semibold mb-6 text-slate-100">
                📁 Output Paths
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">📤 Encoded Output Path</label>
                    <input type="text" name="encoded_output_path"
                           value="{{ old('encoded_output_path', $channel->encoded_output_path ?? '/home/encoded/channel-'.$channel->id) }}"
                           class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 font-mono text-xs focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2">📤 HLS Output Path</label>
                    <input type="text" name="hls_output_path"
                           value="{{ old('hls_output_path', $channel->hls_output_path ?? '/home/hls/channel-'.$channel->id) }}"
                           class="w-full px-4 py-2 rounded-xl border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 font-mono text-xs focus:outline-none transition-all focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex items-center justify-between pt-6">
            <a href="{{ route('vod-channels.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-slate-500/15 px-4 py-2 text-sm font-medium text-slate-200 ring-1 ring-inset ring-slate-400/25 hover:bg-slate-500/20 transition">
                ← Back to Channels
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-500/15 px-4 py-2 text-sm font-medium text-blue-200 ring-1 ring-inset ring-blue-400/25 hover:bg-blue-500/20 transition">
                💾 Save Settings
            </button>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const manualToggle = document.getElementById('manual-override-toggle');
        const manualSection = document.getElementById('manual-mode-section');
        const profileSelect = document.querySelector('select[name="encode_profile_id"]');
        const previewBtn = document.getElementById('preview-btn');
        const previewOutput = document.getElementById('preview-output');
        const channelId = '{{ $channel->id }}';

        // Toggle manual section visibility
        if (manualToggle) {
            manualToggle.addEventListener('change', function() {
                manualSection.style.display = this.checked ? 'block' : 'none';
            });
            // Initial state
            manualSection.style.display = manualToggle.checked ? 'block' : 'none';
        }

        // Preview ffmpeg command
        if (previewBtn) {
            previewBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                previewBtn.disabled = true;
                previewBtn.textContent = '⏳ Loading...';
                
                const formData = new FormData();
                formData.append('profile_id', profileSelect.value);
                formData.append('manual_enabled', manualToggle.checked ? 1 : 0);
                if (manualToggle.checked) {
                    formData.append('manual_bitrate', document.querySelector('input[name="manual_bitrate"]').value);
                    formData.append('manual_preset', document.querySelector('select[name="manual_preset"]').value);
                }

                try {
                    const response = await fetch(`/vod-channels/${channelId}/preview-ffmpeg`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        },
                        body: formData
                    });

                    const data = await response.json();
                    if (data.command) {
                        // Format command with wrapping
                        const formatted = data.command
                            .split(' -')
                            .join(' \\\n    -');
                        previewOutput.textContent = 'ffmpeg ' + formatted;
                    } else {
                        previewOutput.textContent = '❌ Error: ' + (data.error || 'Unknown error');
                    }
                } catch (err) {
                    previewOutput.textContent = '❌ Request failed: ' + err.message;
                } finally {
                    previewBtn.disabled = false;
                    previewBtn.textContent = '🔄 Refresh';
                }
            });

            // Auto-preview on profile change
            if (profileSelect) {
                profileSelect.addEventListener('change', () => {
                    previewBtn.click();
                });
            }
        }
    });

    // ═══════════════════════════════════════════════════════════
    // VIDEO INFO MODAL
    // ═══════════════════════════════════════════════════════════
    window.showVideoInfo = async function(videoId) {
        if (!videoId) {
            alert('Invalid video ID');
            return;
        }

        try {
            const response = await fetch(`/videos/${videoId}/info`);
            const data = await response.json();

            if (!data.success) {
                alert('Error loading video info: ' + data.error);
                return;
            }

            const video = data.video;
            const modal = document.getElementById('videoInfoModal');
            const content = document.getElementById('videoInfoContent');

            let videoHtml = '';
            let audioHtml = '';

            if (video.metadata && video.metadata.video) {
                const v = video.metadata.video;
                videoHtml = `
                    <div class="mt-4">
                        <h4 class="font-semibold text-slate-300 mb-2">🎬 Video Stream</h4>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <dt class="text-slate-400">Codec:</dt>
                            <dd class="text-white font-mono">${v.codec || 'unknown'}</dd>
                            <dt class="text-slate-400">Resolution:</dt>
                            <dd class="text-white font-mono">${v.width || 0}×${v.height || 0}</dd>
                            <dt class="text-slate-400">FPS:</dt>
                            <dd class="text-white font-mono">${v.fps || 0}</dd>
                            <dt class="text-slate-400">Bitrate:</dt>
                            <dd class="text-white font-mono">${v.bitrate ? Math.round(v.bitrate/1000) + ' kbps' : 'N/A'}</dd>
                            <dt class="text-slate-400">Pixel Format:</dt>
                            <dd class="text-white font-mono">${v.pix_fmt || 'unknown'}</dd>
                        </dl>
                    </div>
                `;
            }

            if (video.metadata && video.metadata.audio) {
                const a = video.metadata.audio;
                audioHtml = `
                    <div class="mt-4">
                        <h4 class="font-semibold text-slate-300 mb-2">🔊 Audio Stream</h4>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <dt class="text-slate-400">Codec:</dt>
                            <dd class="text-white font-mono">${a.codec || 'unknown'}</dd>
                            <dt class="text-slate-400">Channels:</dt>
                            <dd class="text-white font-mono">${a.channels || 0}</dd>
                            <dt class="text-slate-400">Sample Rate:</dt>
                            <dd class="text-white font-mono">${a.sample_rate || 'unknown'}</dd>
                            <dt class="text-slate-400">Bitrate:</dt>
                            <dd class="text-white font-mono">${a.bitrate || 'N/A'}</dd>
                        </dl>
                    </div>
                `;
            }

            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">${video.title}</h3>
                        <p class="text-xs text-slate-500 mb-2">ID: ${video.id}</p>
                        <p class="text-sm text-slate-400">Duration: <span class="text-white font-mono">${video.duration || '--:--:--'}</span></p>
                        <p class="text-sm text-slate-400">Category: <span class="text-white font-mono">${video.category}</span></p>
                    </div>
                    <div class="border-t border-slate-700/50 pt-4">
                        <p class="text-xs text-slate-500 mb-2">File path:</p>
                        <p class="text-xs text-slate-300 font-mono bg-slate-900/50 p-2 rounded overflow-x-auto">${video.file_path}</p>
                    </div>
                    ${videoHtml}
                    ${audioHtml}
                </div>
            `;

            modal.classList.remove('hidden');
        } catch (error) {
            alert('Error fetching video info: ' + error.message);
        }
    };

    document.getElementById('videoInfoModal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            e.currentTarget.classList.add('hidden');
        }
    });

    document.getElementById('closeVideoInfoBtn')?.addEventListener('click', () => {
        document.getElementById('videoInfoModal').classList.add('hidden');
    });
    </script>

    <!-- Video Info Modal -->
    <div id="videoInfoModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-800 border border-slate-700 rounded-lg max-w-2xl w-full my-6">
            <div class="sticky top-0 bg-slate-800 border-b border-slate-700 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">📊 Video Information</h3>
                <button 
                    id="closeVideoInfoBtn"
                    type="button"
                    class="text-slate-400 hover:text-white text-2xl transition"
                >×</button>
            </div>
            <div id="videoInfoContent" class="p-6 max-h-96 overflow-y-auto">
                <!-- Content loaded by JS -->
            </div>
        </div>
    </div>
@endsection

