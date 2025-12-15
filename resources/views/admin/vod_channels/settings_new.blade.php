@extends('layouts.panel')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-black text-slate-100">
        ⚙️ Channel Settings
    </h1>
    <p class="text-slate-400 mt-2">Configure {{ $channel->name }}</p>
</div>

@if(session('success'))
    <div class="mb-6 p-4 border border-emerald-500/30 bg-emerald-500/10 rounded-xl flex items-start gap-3">
        <span class="text-2xl">✅</span>
        <div>
            <div class="font-bold text-emerald-300">Settings saved!</div>
            <div class="text-sm text-emerald-200">{{ session('success') }}</div>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 p-4 border border-red-500/30 bg-red-500/10 rounded-xl">
        <div class="font-bold text-red-300 mb-2">❌ Validation Errors</div>
        <ul class="list-disc pl-6 space-y-1 text-red-200 text-sm">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Tab Navigation -->
<div class="flex gap-2 mb-6 border-b border-slate-500/20 overflow-x-auto">
    <button type="button" class="tab-btn active px-6 py-3 font-semibold text-slate-300 border-b-2 border-blue-500 hover:text-slate-100 transition" data-tab="general">
        📋 General
    </button>
    <button type="button" class="tab-btn px-6 py-3 font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-200 transition" data-tab="playlist">
        🎬 Playlist
    </button>
    <button type="button" class="tab-btn px-6 py-3 font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-200 transition" data-tab="encoding">
        ⚙️ Encoding
    </button>
    <button type="button" class="tab-btn px-6 py-3 font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-200 transition" data-tab="overlay">
        🎨 Overlay
    </button>
    <button type="button" class="tab-btn px-6 py-3 font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-200 transition" data-tab="info">
        📊 Stream Info
    </button>
    <button type="button" class="tab-btn px-6 py-3 font-semibold text-slate-400 border-b-2 border-transparent hover:text-slate-200 transition" data-tab="outputs">
        📤 Outputs
    </button>
</div>

<form method="POST" action="{{ route('vod-channels.settings.update', $channel) }}" enctype="multipart/form-data" class="space-y-6 pb-32">
    @csrf

    <!-- TAB: GENERAL -->
    <div id="tab-general" class="tab-content block">
        @include('admin.vod_channels.settings_tabs.general')
    </div>

    <!-- TAB: PLAYLIST -->
    <div id="tab-playlist" class="tab-content hidden">
        @include('admin.vod_channels.settings_tabs.playlist')
    </div>

    <!-- TAB: ENCODING -->
    <div id="tab-encoding" class="tab-content hidden">
        @include('admin.vod_channels.settings_tabs.encoding')
    </div>

    <!-- TAB: OVERLAY -->
    <div id="tab-overlay" class="tab-content hidden">
        @include('admin.vod_channels.settings_tabs.overlay')
    </div>

    <!-- TAB: STREAM INFO -->
    <div id="tab-info" class="tab-content hidden">
        @include('admin.vod_channels.settings_tabs.stream_info')
    </div>

    <!-- TAB: OUTPUTS -->
    <div id="tab-outputs" class="tab-content hidden">
        @include('admin.vod_channels.settings_tabs.outputs')
    </div>

    <!-- Save Button -->
    <div class="flex gap-4 fixed bottom-0 left-0 right-0 p-6 bg-slate-900/80 backdrop-blur-sm border-t border-slate-500/20">
        <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
            💾 Save All Changes
        </button>
        <a href="{{ route('vod-channels.index') }}" class="px-8 py-3 bg-slate-800/50 text-slate-300 rounded-lg hover:bg-slate-700/50 transition">
            Cancel
        </a>
    </div>
</form>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.getAttribute('data-tab');
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('border-blue-500', 'text-slate-100');
            el.classList.add('border-transparent', 'text-slate-400');
        });
        
        // Show selected tab
        document.getElementById('tab-' + tab).classList.remove('hidden');
        btn.classList.remove('border-transparent', 'text-slate-400');
        btn.classList.add('border-blue-500', 'text-slate-100');
    });
});
</script>
@endsection
