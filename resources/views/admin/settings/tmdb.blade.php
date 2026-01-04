@extends('layouts.panel')

@section('content')
<style>
    .wrap { max-width: 900px; margin: 0 auto; }
    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .card-h { padding: 12px 14px; border-bottom: 1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; }
    .card-t { font-size: 14px; font-weight: 900; color: var(--text-primary); }
    .card-b { padding: 14px; }
    .field label { display:block; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
    .input { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 13px; background: var(--card-bg); color: var(--text-primary); }
    .hint { font-size: 12px; color: var(--text-muted); margin-top: 8px; }
    .btn { padding: 10px 12px; border-radius: 6px; color: #fff; font-weight: 900; font-size: 12px; }
    .btn-save { background: var(--fox-blue); }
    .flash { border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 6px; padding: 12px 14px; box-shadow: var(--shadow-sm); margin: 12px 0 16px; }
    .flash.success { border-left: 4px solid var(--fox-green); }
</style>

<div class="wrap">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 14px;">
        <div>
            <div style="font-size:22px; font-weight:900; color:var(--text-primary);">TMDB Settings</div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:6px;">Cheia este folosită pentru postere/imagine la filme în Import VOD.</div>
        </div>
        <a class="btn btn-save" style="text-decoration:none;" href="{{ route('settings.index') }}">Back</a>
    </div>

    @if(session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-h">
            <div class="card-t">API Key</div>
        </div>
        <div class="card-b">
            <form method="POST" action="/settings/tmdb">
                @csrf
                <div class="field">
                    <label>TMDB API Key</label>
                    <input class="input" type="text" name="tmdb_api_key" value="{{ old('tmdb_api_key', $tmdbKey ?? '') }}" placeholder="ex: 76e0e00b95dc2ccc6fa0d8b8d8c3d89b">
                    <div class="hint">După salvare, mergi la Encoding / Import și reîncarcă lista (auto-scan).</div>
                </div>

                <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                    <button class="btn btn-save" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
