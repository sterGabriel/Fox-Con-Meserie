@extends('layouts.panel')

@section('content')

<h1 class="text-2xl font-bold mb-6">Channel Settings: {{ $channel->name }}</h1>

<form method="POST" action="{{ route('vod-channels.settings.update', $channel) }}">
    @csrf

    <div class="grid grid-cols-2 gap-6">

        {{-- LOGO SETTINGS --}}
        <div class="bg-slate-800 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3 text-orange-400">Logo Settings</h2>

            <label class="block mb-2">Logo Path</label>
            <input type="text" name="logo_path" value="{{ $channel->logo_path }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Logo Width</label>
            <input type="number" name="logo_width" value="{{ $channel->logo_width }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Logo Height</label>
            <input type="number" name="logo_height" value="{{ $channel->logo_height }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Logo Position X</label>
            <input type="number" name="logo_position_x" value="{{ $channel->logo_position_x }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Logo Position Y</label>
            <input type="number" name="logo_position_y" value="{{ $channel->logo_position_y }}" class="w-full bg-slate-700 p-2 rounded">
        </div>

        {{-- TITLE SETTINGS --}}
        <div class="bg-slate-800 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3 text-orange-400">Movie Title Settings</h2>

            <label class="block mb-2">Title Font Size</label>
            <input type="number" name="title_font_size" value="{{ $channel->title_font_size }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Title Position X</label>
            <input type="number" name="title_position_x" value="{{ $channel->title_position_x }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Title Position Y</label>
            <input type="number" name="title_position_y" value="{{ $channel->title_position_y }}" class="w-full bg-slate-700 p-2 rounded">
        </div>

        {{-- TIMER SETTINGS --}}
        <div class="bg-slate-800 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3 text-orange-400">Timer Settings</h2>

            <label class="block mb-2">Timer Font Size</label>
            <input type="number" name="timer_font_size" value="{{ $channel->timer_font_size }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Timer Position X</label>
            <input type="number" name="timer_position_x" value="{{ $channel->timer_position_x }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Timer Position Y</label>
            <input type="number" name="timer_position_y" value="{{ $channel->timer_position_y }}" class="w-full bg-slate-700 p-2 rounded">
        </div>

        {{-- ENCODING SETTINGS --}}
        <div class="bg-slate-800 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-3 text-orange-400">Encoding Settings</h2>

            <label class="block mb-2">Resolution (Ex: 1920x1080)</label>
            <input type="text" name="resolution" value="{{ $channel->resolution }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Video Bitrate (kbps)</label>
            <input type="number" name="video_bitrate_kbps" value="{{ $channel->video_bitrate_kbps }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Audio Bitrate (kbps)</label>
            <input type="number" name="audio_bitrate_kbps" value="{{ $channel->audio_bitrate_kbps }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">FPS</label>
            <input type="number" name="fps" value="{{ $channel->fps }}" class="w-full bg-slate-700 p-2 rounded">

            <label class="block mt-3">Audio Codec</label>
            <input type="text" name="audio_codec" value="{{ $channel->audio_codec }}" class="w-full bg-slate-700 p-2 rounded">
        </div>

    </div>

    <div class="mt-6">
        <button class="bg-orange-600 px-6 py-3 rounded-lg text-white font-semibold hover:bg-orange-700">Save Settings</button>
    </div>
</form>

@endsection
