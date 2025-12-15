<!-- Profile Form -->
<div class="rounded-lg border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Name -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-300 mb-2">Profile Name</label>
            <input type="text" name="name" value="{{ old('name', $profile->name ?? '') }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-400/50" 
                placeholder="e.g., 720p FAST LIVE"
                required>
            @error('name')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Resolution: Width & Height -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Width (px)</label>
            <input type="number" name="width" value="{{ old('width', $profile->width ?? 1280) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="480" max="3840" required>
            @error('width')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Height (px)</label>
            <input type="number" name="height" value="{{ old('height', $profile->height ?? 720) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="270" max="2160" required>
            @error('height')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- FPS -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">FPS</label>
            <input type="number" name="fps" value="{{ old('fps', $profile->fps ?? 60) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="15" max="60" required>
            @error('fps')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Video Codec -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Video Codec</label>
            <select name="video_codec" class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" required>
                <option value="libx264" {{ old('video_codec', $profile->video_codec ?? 'libx264') === 'libx264' ? 'selected' : '' }}>H.264 (libx264)</option>
                <option value="libx265" {{ old('video_codec', $profile->video_codec ?? '') === 'libx265' ? 'selected' : '' }}>H.265 (libx265)</option>
            </select>
            @error('video_codec')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Video Bitrate -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Video Bitrate (kbps)</label>
            <input type="number" name="video_bitrate_k" value="{{ old('video_bitrate_k', $profile->video_bitrate_k ?? 2500) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="500" max="10000" required>
            @error('video_bitrate_k')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Preset -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Preset</label>
            <select name="preset" class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" required>
                <option value="ultrafast" {{ old('preset', $profile->preset ?? '') === 'ultrafast' ? 'selected' : '' }}>Ultra Fast</option>
                <option value="superfast" {{ old('preset', $profile->preset ?? '') === 'superfast' ? 'selected' : '' }}>Super Fast</option>
                <option value="veryfast" {{ old('preset', $profile->preset ?? 'veryfast') === 'veryfast' ? 'selected' : '' }}>Very Fast</option>
                <option value="faster" {{ old('preset', $profile->preset ?? '') === 'faster' ? 'selected' : '' }}>Faster</option>
                <option value="fast" {{ old('preset', $profile->preset ?? '') === 'fast' ? 'selected' : '' }}>Fast</option>
                <option value="medium" {{ old('preset', $profile->preset ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="slow" {{ old('preset', $profile->preset ?? '') === 'slow' ? 'selected' : '' }}>Slow</option>
                <option value="slower" {{ old('preset', $profile->preset ?? '') === 'slower' ? 'selected' : '' }}>Slower</option>
                <option value="veryslow" {{ old('preset', $profile->preset ?? '') === 'veryslow' ? 'selected' : '' }}>Very Slow</option>
            </select>
            @error('preset')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- GOP / Keyint -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">GOP / Keyint (frames)</label>
            <input type="number" name="gop" value="{{ old('gop', $profile->gop ?? 50) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="25" max="300" required>
            @error('gop')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Max Rate -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Max Rate (kbps)</label>
            <input type="number" name="maxrate_k" value="{{ old('maxrate_k', $profile->maxrate_k ?? 2500) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="500" max="10000" required>
            @error('maxrate_k')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Buffer Size -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Buffer Size (kbps)</label>
            <input type="number" name="bufsize_k" value="{{ old('bufsize_k', $profile->bufsize_k ?? 5000) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="500" max="20000" required>
            @error('bufsize_k')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Audio Codec -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Audio Codec</label>
            <select name="audio_codec" class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" required>
                <option value="aac" {{ old('audio_codec', $profile->audio_codec ?? 'aac') === 'aac' ? 'selected' : '' }}>AAC</option>
                <option value="libmp3lame" {{ old('audio_codec', $profile->audio_codec ?? '') === 'libmp3lame' ? 'selected' : '' }}>MP3</option>
                <option value="libopus" {{ old('audio_codec', $profile->audio_codec ?? '') === 'libopus' ? 'selected' : '' }}>Opus</option>
            </select>
            @error('audio_codec')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Audio Bitrate -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Audio Bitrate (kbps)</label>
            <input type="number" name="audio_bitrate_k" value="{{ old('audio_bitrate_k', $profile->audio_bitrate_k ?? 128) }}" 
                class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" 
                min="64" max="320" required>
            @error('audio_bitrate_k')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Audio Channels -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Audio Channels</label>
            <select name="audio_channels" class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" required>
                <option value="1" {{ old('audio_channels', $profile->audio_channels ?? 2) == 1 ? 'selected' : '' }}>Mono</option>
                <option value="2" {{ old('audio_channels', $profile->audio_channels ?? 2) == 2 ? 'selected' : '' }}>Stereo</option>
            </select>
            @error('audio_channels')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Audio Sample Rate -->
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Sample Rate (Hz)</label>
            <select name="audio_sample_rate" class="w-full px-4 py-2 bg-slate-800/50 border border-slate-600/30 rounded-lg text-slate-100" required>
                <option value="22050" {{ old('audio_sample_rate', $profile->audio_sample_rate ?? 48000) == 22050 ? 'selected' : '' }}>22.05 kHz</option>
                <option value="44100" {{ old('audio_sample_rate', $profile->audio_sample_rate ?? 48000) == 44100 ? 'selected' : '' }}>44.1 kHz</option>
                <option value="48000" {{ old('audio_sample_rate', $profile->audio_sample_rate ?? 48000) == 48000 ? 'selected' : '' }}>48 kHz</option>
            </select>
            @error('audio_sample_rate')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Mode (LIVE / VOD) -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-300 mb-3">Mode</label>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="mode" value="LIVE" {{ old('mode', $profile->container ?? 'mpegts') === 'mpegts' || old('mode') === 'LIVE' ? 'checked' : '' }} 
                        class="w-4 h-4 accent-blue-500">
                    <span class="text-slate-300">🔴 LIVE (MPEGTS + Headers)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="mode" value="VOD" {{ old('mode', $profile->container ?? 'mpegts') === 'mp4' || old('mode') === 'VOD' ? 'checked' : '' }} 
                        class="w-4 h-4 accent-blue-500">
                    <span class="text-slate-300">📹 VOD (Standard H.264/H.265)</span>
                </label>
            </div>
            @error('mode')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Buttons -->
    <div class="flex gap-4 mt-8 pt-6 border-t border-slate-500/20">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            {{ isset($profile) ? 'Update Profile' : 'Create Profile' }}
        </button>
        <a href="{{ route('encode-profiles.index') }}" class="px-6 py-2 bg-slate-800/50 text-slate-300 rounded-lg hover:bg-slate-700/50 transition">
            Cancel
        </a>
    </div>
</div>
