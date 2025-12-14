@extends('layouts.panel')

@section('content')
    <h1 class="text-2xl font-semibold mb-4">Vod Channels (simplu)</h1>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($channels as $channel)
            <tr>
                <td>{{ $channel->id }}</td>
                <td>{{ $channel->name }}</td>
                <td>{{ $channel->status }}</td>
                <td>
                    <a href="{{ route('vod-channels.playlist', $channel) }}">Playlist</a> |
                    <a href="{{ route('vod-channels.settings', $channel) }}">Settings</a> |
                    <span style="color: red; opacity: 0.5;">Delete</span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $channels->links() }}
    </div>
@endsection
