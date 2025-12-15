@extends('layouts.panel')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Vod Channels</h1>

        <a href="{{ route('vod-channels.create') }}"
           class="px-4 py-2 rounded bg-green-600 hover:bg-green-700 text-white">
            + Create Vod Channel
        </a>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-900/80">
                <tr>
                    <th class="px-4 py-3 text-left text-slate-400">ID</th>
                    <th class="px-4 py-3 text-left text-slate-400">Name</th>
                    <th class="px-4 py-3 text-left text-slate-400">Status</th>
                    <th class="px-4 py-3 text-left text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($channels as $channel)
                    <tr class="border-t border-slate-800">
                        <td class="px-4 py-2">{{ $channel->id }}</td>
                        <td class="px-4 py-2">{{ $channel->name }}</td>
                        <td class="px-4 py-2">{{ $channel->status }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('vod-channels.playlist', $channel) }}"
                               class="px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs">
                                Playlist
                            </a>

                            <a href="{{ route('vod-channels.settings-public', $channel) }}"
                               class="px-3 py-1 rounded bg-yellow-600 hover:bg-yellow-700 text-white text-xs">
                                Settings
                            </a>

                            <button class="px-3 py-1 rounded bg-red-600 text-white text-xs opacity-40 cursor-not-allowed">
                                Delete
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $channels->links() }}
    </div>
@endsection
