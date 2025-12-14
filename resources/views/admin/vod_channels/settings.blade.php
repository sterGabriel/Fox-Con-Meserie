@extends('layouts.panel')

@section('content')
    <h1 class="text-2xl font-bold mb-6">
        Settings [{{ $channel->name }}]
    </h1>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 border border-green-300 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 border border-red-300 rounded">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('vod-channels.settings.update', $channel) }}"
          enctype="multipart/form-data"
          class="space-y-8 pb-24">
        @csrf

        {{-- 1) Channel Info --}}
        <div class="border rounded p-4">
            <h2 class="font-semibold mb-3">Channel Info</h2>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Channel name</label>
                <div class="text-sm text-gray-700">
                    {{ $channel->name }} (ID: {{ $channel->id }})
                </div>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Video Category</label>
                <select name="video_category" class="border rounded w-full px-2 py-1">
                    <option value="">-- no category --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ (int)old('video_category', $channel->video_category) === (int)$cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Categoriile vin din tabela <code>video_categories</code>.
                </p>
            </div>
        </div>

        {{-- 2) Encoding profile --}}
        <div class="border rounded p-4">
            <h2 class="font-semibold mb-3">Encoding profile</h2>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Resolution (ex. 1280x720, 1920x1080)</label>
                    <input type="text" name="resolution"
                           value="{{ old('resolution', $channel->resolution ?? '1280x720') }}"
                           class="border rounded w-full px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Video Bitrate (kbps)</label>
                    <input type="number" name="video_bitrate" min="200" max="50000"
                           value="{{ old('video_bitrate', $channel->video_bitrate ?? 1500) }}"
                           class="border rounded w-full px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Audio Bitrate (kbps)</label>
                    <input type="number" name="audio_bitrate" min="32" max="1024"
                           value="{{ old('audio_bitrate', $channel->audio_bitrate ?? 128) }}"
                           class="border rounded w-full px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">FPS</label>
                    <input type="number" name="fps" min="10" max="120"
                           value="{{ old('fps', $channel->fps ?? 25) }}"
                           class="border rounded w-full px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Audio Codec</label>
                    <select name="audio_codec" class="border rounded w-full px-2 py-1">
                        @php $currentCodec = old('audio_codec', $channel->audio_codec ?? 'aac'); @endphp
                        <option value="aac" {{ $currentCodec === 'aac' ? 'selected' : '' }}>AAC</option>
                        <option value="mp3" {{ $currentCodec === 'mp3' ? 'selected' : '' }}>MP3</option>
                        <option value="ac3" {{ $currentCodec === 'ac3' ? 'selected' : '' }}>AC3</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 3) Overlay --}}
        <div class="border rounded p-4">
            <h2 class="font-semibold mb-3">Overlay (logo, title, timer)</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Upload logo (PNG transparent) from your PC</label>
                <input type="file" name="logo_upload" accept="image/png"
                       class="border rounded w-full px-2 py-2">
                <p class="text-xs text-gray-500 mt-1">
                    Selectezi fișier din Windows și la Save se salvează pe server și se pune automat în logo_path.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Logo path (saved)</label>
                <input type="text" name="logo_path"
                       value="{{ old('logo_path', $channel->logo_path ?? '') }}"
                       class="border rounded w-full px-2 py-1">
                <p class="text-xs text-gray-500 mt-1">
                    Recomandat: lasă-l să fie setat automat (format: <code>private/logos/vod_channels/{id}/...</code>).
                </p>

                @php
                    $p = $channel->logo_path;
                    $abs = null;
                    if (!empty($p)) {
                        $abs = \Illuminate\Support\Str::startsWith($p, '/') ? $p : storage_path('app/'.ltrim($p,'/'));
                    }
                @endphp

                @if(!empty($abs) && file_exists($abs))
                    <div class="mt-3">
                        <div class="text-sm text-gray-600 mb-1">Saved logo preview:</div>
                        <img src="{{ route('vod-channels.logo.preview', $channel) }}"
                             style="max-height:80px; background:#111; padding:6px; border-radius:6px;">
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $channel->logo_path }}
                        </div>
                    </div>
                @endif
            </div>

            {{-- Dacă vrei păstrezi și position/offsets în UI, dar DB-ul tău NU are coloane pentru ele.
                 Deci nu le mai afișăm aici ca să nu mai crape salvarea. --}}
            <div class="flex items-center space-x-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="overlay_title" value="1"
                           {{ old('overlay_title', $channel->overlay_title) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm">Show movie title overlay</span>
                </label>

                <label class="inline-flex items-center">
                    <input type="checkbox" name="overlay_timer" value="1"
                           {{ old('overlay_timer', $channel->overlay_timer) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm">Show remaining time overlay</span>
                </label>
            </div>
        </div>

        {{-- 4) Output paths --}}
        <div class="border rounded p-4">
            <h2 class="font-semibold mb-3">Output paths</h2>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Encoded output path</label>
                <input type="text" name="encoded_output_path"
                       value="{{ old('encoded_output_path', $channel->encoded_output_path ?? '/home/encoded/channel-'.$channel->id) }}"
                       class="border rounded w-full px-2 py-1">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">HLS output path</label>
                <input type="text" name="hls_output_path"
                       value="{{ old('hls_output_path', $channel->hls_output_path ?? '/home/hls/channel-'.$channel->id) }}"
                       class="border rounded w-full px-2 py-1">
            </div>
        </div>

        {{-- Save bar jos (mereu vizibil) --}}
        <div style="position:fixed; left:0; right:0; bottom:0; z-index:999999; background:#fff; border-top:1px solid #ddd; padding:12px;">
            <div style="max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between;">
                <div style="font-size:14px; color:#333;">
                    Channel: <b>{{ $channel->name }}</b>
                </div>

                <div style="display:flex; align-items:center; gap:16px;">
                    <a href="{{ route('vod-channels.playlist', $channel) }}"
                       style="font-size:14px; color:#0b5ed7; text-decoration:underline;">
                        Back to Playlist
                    </a>

                    <button type="submit"
                            style="padding:10px 16px; background:#0d6efd; color:#fff; border:0; border-radius:6px; cursor:pointer;">
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
