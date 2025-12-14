@extends('layouts.panel')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Live Channels</h1>
            <p class="text-sm text-slate-400">Management pentru stream-urile live.</p>
        </div>
        <a href="{{ route('live-channels.create') }}" class="px-4 py-2 rounded bg-black text-white text-sm">
            + Add Live Channel
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    @if ($channels->isEmpty())
        <p class="text-slate-400">
            Nu există încă niciun canal live.
        </p>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-slate-400">ID</th>
                        <th class="px-4 py-3 text-left text-slate-400">Name</th>
                        <th class="px-4 py-3 text-left text-slate-400">Input URL</th>
                        <th class="px-4 py-3 text-left text-slate-400">Profile</th>
                        <th class="px-4 py-3 text-left text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($channels as $channel)
                        <tr class="border-t border-slate-800">
                            <td class="px-4 py-2">{{ $channel->id }}</td>
                            <td class="px-4 py-2">{{ $channel->name }}</td>
                            <td class="px-4 py-2 truncate max-w-xs">{{ $channel->input_url }}</td>
                            <td class="px-4 py-2">{{ $channel->encoder_profile }}</td>
                            <td class="px-4 py-2">{{ $channel->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $channels->links() }}
        </div>
    @endif
@endsection
