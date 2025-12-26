@extends('layouts.panel')

@section('content')
<style>
    .wrap { max-width: 980px; margin: 0 auto; }
    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .card-h { padding: 12px 14px; border-bottom: 1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .card-t { font-size: 14px; font-weight: 900; color: var(--text-primary); }
    .card-b { padding: 14px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-1 { grid-template-columns: 1fr; }
    .field label { display:block; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
    .input, .select { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 13px; background: var(--card-bg); color: var(--text-primary); }
    .hint { font-size: 12px; color: var(--text-muted); margin-top: 8px; }
    .btn { padding: 10px 12px; border-radius: 6px; color: #fff; font-weight: 900; font-size: 12px; border: 0; cursor: pointer; }
    .btn-save { background: var(--fox-blue); }
    .btn-ghost { background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); }
    .flash { border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 6px; padding: 12px 14px; box-shadow: var(--shadow-sm); margin: 12px 0 16px; }
    .flash.error { border-left: 4px solid var(--fox-red); }
    .flash.success { border-left: 4px solid var(--fox-green); }
    .kv { display:flex; flex-wrap:wrap; gap: 10px 14px; font-size: 12px; color: var(--text-muted); }
    .kv b { color: var(--text-primary); }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
</style>

@php
    $durationSeconds = (int) ($video->duration_seconds ?? 0);
    $durationText = $durationSeconds > 0 ? gmdate('H:i:s', $durationSeconds) : '—';
    $sizeBytes = (int) ($video->size_bytes ?? 0);
    $sizeText = $sizeBytes > 0 ? number_format($sizeBytes / 1024 / 1024, 1) . ' MB' : '—';
@endphp

<div class="wrap">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size:22px; font-weight:900; color:var(--text-primary);">Edit Video</div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:6px;">ID: <span class="mono">{{ (int) $video->id }}</span></div>
        </div>
        <div style="display:flex; gap:10px;">
            <a class="btn btn-ghost" style="text-decoration:none;" href="{{ route('videos.index') }}">Back</a>
            <button form="videoEditForm" class="btn btn-save" type="submit">Save</button>
        </div>
    </div>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash error">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="margin-bottom: 14px;">
        <div class="card-h">
            <div class="card-t">Video Details</div>
            <div class="kv">
                <span><b>Duration</b> {{ $durationText }}</span>
                <span><b>Resolution</b> {{ $video->resolution ?: '—' }}</span>
                <span><b>Format</b> {{ $video->format ?: '—' }}</span>
                <span><b>Bitrate</b> {{ $video->bitrate_kbps ? ((int)$video->bitrate_kbps . ' kbps') : '—' }}</span>
                <span><b>Size</b> {{ $sizeText }}</span>
                <span><b>TMDB</b> {{ $video->tmdb_id ? ('#' . (int)$video->tmdb_id) : '—' }}</span>
            </div>
        </div>
        <div class="card-b">
            <form id="videoEditForm" method="POST" action="{{ route('videos.update', $video) }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-1" style="margin-bottom: 12px;">
                    <div class="field">
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" class="input" value="{{ old('title', $video->title) }}" required>
                    </div>
                </div>

                <div class="grid" style="margin-bottom: 12px;">
                    <div class="field">
                        <label for="file_path">File Path</label>
                        <input id="file_path" name="file_path" type="text" class="input" value="{{ old('file_path', $video->file_path) }}" required>
                        <div class="hint">Used for encoding and probing. Must exist on server.</div>
                    </div>

                    <div class="field">
                        <label for="video_category_id">Video Category</label>
                        <select id="video_category_id" name="video_category_id" class="select">
                            <option value="">-- no category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string)old('video_category_id', $video->video_category_id) === (string)$cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="hint">Tip: Use TMDB auto-scan from VOD Channel Settings to attach poster + TMDB ID.</div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-h">
            <div class="card-t">Quick Actions</div>
            <div style="display:flex; gap:10px;">
                <a class="btn btn-ghost" style="text-decoration:none;" href="{{ route('videos.play', $video) }}" target="_blank" rel="noopener">Play Original</a>
                <a class="btn btn-ghost" style="text-decoration:none;" href="{{ route('videos.probe', $video) }}" target="_blank" rel="noopener">Probe (ffprobe)</a>
            </div>
        </div>
        <div class="card-b">
            <div class="kv">
                <span><b>File</b> <span class="mono">{{ basename((string)($video->file_path ?? '')) ?: '—' }}</span></span>
                <span><b>Category</b> {{ $video->category?->name ?? 'Uncategorized' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
