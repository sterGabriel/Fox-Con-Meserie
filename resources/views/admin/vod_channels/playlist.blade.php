@extends('layouts.app')

@section('content')
    <h1 class="mb-3">
        Playlist [{{ $channel->name }}]
    </h1>

    {{-- Mesaje succes / eroare --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        {{-- STÂNGA: PLAYLIST EXISTENT --}}
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">
                    Current Playlist
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>Name</th>
                            <th style="width: 80px">Order</th>
                            <th style="width: 220px">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($playlistItems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ optional($item->video)->title ?? 'Unknown' }}</td>
                                <td>{{ $item->sort_order }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        {{-- UP --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.move-up', [$channel, $item]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary">↑</button>
                                        </form>

                                        {{-- DOWN --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.move-down', [$channel, $item]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary">↓</button>
                                        </form>

                                        {{-- DELETE --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.remove', [$channel, $item]) }}"
                                              onsubmit="return confirm('Remove this item from playlist?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    No items in this playlist yet.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- DREAPTA: INFO CANAL + LISTĂ VIDEOS CU "SELECT" --}}
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">
                    Channel Info
                </div>
                <div class="card-body">
                    <p><strong>Channel:</strong> {{ $channel->name }}</p>

                    @php
                        $rawCatId = $channel->video_category ?? null;
                        $category = $rawCatId ? \App\Models\VideoCategory::find($rawCatId) : null;
                    @endphp

                    <p>
                        <strong>Video Category:</strong>
                        {{ $category?->name ?? '— no category —' }}
                    </p>

                    <p style="font-size: 12px; color: #888;">
                        (debug) channel->video_category = {{ $rawCatId ?? 'NULL' }}
                    </p>

                    <a href="{{ route('vod-channels.settings-public', $channel) }}"
                       class="btn btn-outline-primary btn-sm mb-2">
                        Open Settings
                    </a>

                    {{-- BUTON: pune la encodat tot playlist-ul --}}
                    <form method="POST"
                          action="{{ route('encoding-jobs.queue-channel', $channel) }}"
                          class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            Queue encoding for this playlist
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Available Videos</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>Video Name</th>
                            <th style="width: 140px">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($videos as $video)
                            <tr>
                                <td>{{ $video->id }}</td>
                                <td>{{ $video->title }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        {{-- SELECT → adaugă în playlist --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.add', $channel) }}">
                                            @csrf
                                            <input type="hidden" name="video_id" value="{{ $video->id }}">
                                            <button type="submit" class="btn btn-success">
                                                Select
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-3">
                                    No videos found.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
