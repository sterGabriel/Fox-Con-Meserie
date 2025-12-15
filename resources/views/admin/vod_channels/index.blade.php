@extends('layouts.panel')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-black text-slate-100">
                📺 VOD Channels
            </h1>
            <p class="text-slate-400 mt-2">Manage all your streaming channels</p>
        </div>
        <button onclick="location.href='{{ route('vod-channels.create') }}'" 
                class="inline-flex items-center gap-2 rounded-xl bg-blue-500/15 px-4 py-2 text-sm font-medium text-blue-200 ring-1 ring-inset ring-blue-400/25 hover:bg-blue-500/20 transition">
            ✨ New Channel
        </button>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4 mb-8">
        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5 backdrop-blur-sm">
            <div class="text-xs uppercase tracking-wide text-slate-300/70">Total Channels</div>
            <div class="mt-3 text-3xl font-semibold text-slate-100">{{ $channels->total() ?? count($channels) }}</div>
        </div>

        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5 backdrop-blur-sm">
            <div class="text-xs uppercase tracking-wide text-slate-300/70">Active</div>
            <div class="mt-3 text-3xl font-semibold text-slate-100">
                {{ collect($channels->getCollection())->where('enabled', true)->count() }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5 backdrop-blur-sm">
            <div class="text-xs uppercase tracking-wide text-slate-300/70">Running</div>
            <div class="mt-3 text-3xl font-semibold text-slate-100">
                {{ collect($channels->getCollection())->where('status', 'running')->count() }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5 backdrop-blur-sm">
            <div class="text-xs uppercase tracking-wide text-slate-300/70">Idle</div>
            <div class="mt-3 text-3xl font-semibold text-slate-100">
                {{ collect($channels->getCollection())->where('status', 'idle')->count() }}
            </div>
        </div>
    </div>

    {{-- CHANNELS TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-500/20 bg-slate-900/40 backdrop-blur-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-950/40 text-slate-300/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide">Logo</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide">Channel</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide">Category</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide">Bitrate</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($channels as $channel)
                    <tr class="border-t border-slate-500/10 hover:bg-slate-800/40 transition">
                        <td class="px-6 py-4">
                            @if ($channel->logo_path)
                                <div class="w-12 h-12 rounded-lg bg-slate-950/50 overflow-hidden border border-slate-600/30 hover:border-blue-500/50 transition-all">
                                    <img src="{{ route('vod-channels.logo.preview', $channel) }}" 
                                         alt="{{ $channel->name }}" 
                                         class="w-full h-full object-cover">
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-lg bg-slate-950/50 border border-slate-600/30 flex items-center justify-center text-slate-500">
                                    📷
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-slate-100">{{ $channel->name }}</div>
                            <div class="text-xs text-slate-400 mt-1">ID: {{ $channel->id }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs rounded-full border border-slate-400/20 bg-slate-400/10 px-2 py-1 text-slate-200/80">
                                {{ $channel->video_category ?? '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-blue-300">{{ $channel->video_bitrate ?? '—' }} kbps</div>
                            <div class="text-xs text-slate-400">{{ $channel->resolution ?? '—' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $status = $channel->status ?? 'idle';
                                $badge = match($status) {
                                    'running' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                                    'idle'    => 'border-slate-400/20 bg-slate-400/10 text-slate-200/80',
                                    'error'   => 'border-red-500/30 bg-red-500/10 text-red-300',
                                    default   => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                                };
                            @endphp
                            <span class="text-xs rounded-full border px-2 py-1 {{ $badge }}">
                                {{ strtoupper($status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <a href="{{ route('vod-channels.playlist', $channel) }}" 
                                   class="inline-flex items-center gap-1 rounded-lg bg-blue-500/15 px-3 py-2 text-xs font-medium text-blue-200 ring-1 ring-inset ring-blue-400/25 hover:bg-blue-500/20 transition">
                                    🎵
                                </a>
                                <a href="{{ route('vod-channels.settings', $channel) }}" 
                                   class="inline-flex items-center gap-1 rounded-lg bg-slate-500/15 px-3 py-2 text-xs font-medium text-slate-200 ring-1 ring-inset ring-slate-400/25 hover:bg-slate-500/20 transition">
                                    ⚙️
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-6">
        {{ $channels->links() }}
    </div>
@endsection
