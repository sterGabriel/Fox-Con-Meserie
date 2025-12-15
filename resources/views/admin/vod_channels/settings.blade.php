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
@endsection

