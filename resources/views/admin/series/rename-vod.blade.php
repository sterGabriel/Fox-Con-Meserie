@extends('layouts.panel')

@section('content')
    <style>
        .wrap { max-width: 1400px; margin: 0 auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-h { padding: 12px 14px; border-bottom: 1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .card-t { font-size: 14px; font-weight: 900; color: var(--text-primary); }
        .card-b { padding: 14px; }
        .field label { display:block; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
        .input { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 13px; background: var(--card-bg); color: var(--text-primary); }
        .btn { padding: 10px 12px; border-radius: 6px; color: #fff; font-weight: 900; font-size: 12px; border: 0; cursor: pointer; }
        .btn-blue { background: var(--fox-blue); }
        .btn-gray { background: #111; }
        .flash { border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 6px; padding: 12px 14px; box-shadow: var(--shadow-sm); margin: 12px 0 16px; }
        .flash.success { border-left: 4px solid var(--fox-green); }
        .flash.error { border-left: 4px solid var(--fox-red); }
        .crumbs a { color: var(--fox-blue); text-decoration: none; font-weight: 800; font-size: 12px; }
        .crumbs span { color: var(--text-muted); font-size: 12px; font-weight: 700; }
        .dirs a { display:inline-flex; align-items:center; gap:8px; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--card-bg); text-decoration: none; color: var(--text-primary); font-weight: 800; font-size: 12px; }
        .dirs a:hover { border-color: var(--fox-blue); }
        .pill { display:inline-flex; align-items:center; gap:6px; padding: 4px 8px; border-radius: 999px; border: 1px solid var(--border-color); font-size: 11px; font-weight: 900; }
        .pill.ok { border-color: var(--fox-green); color: var(--fox-green); }
        .pill.bad { border-color: var(--fox-red); color: var(--fox-red); }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>

    <div class="wrap">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;flex-wrap:wrap;">
            <div>
                <div style="font-size:22px; font-weight:900; color:var(--text-primary);">Rename VOD (Movies + Series)</div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:6px;">Preview TMDb names then apply rename. Seriale: <span class="mono">Show (Year) - S01E02.ext</span></div>
            </div>
        </div>

        @if (session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="flash error">
                <div>{{ session('error') }}</div>
                @if (session('bulk_errors'))
                    <ul style="margin:10px 0 0 0; padding-left: 18px;">
                        @foreach ((array) session('bulk_errors') as $msg)
                            <li style="margin: 4px 0;">{{ $msg }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if (!empty($error))
            <div class="flash error">{{ $error }}</div>
        @endif

        <div class="card" style="margin-bottom: 14px;">
            <div class="card-h">
                <div class="card-t">Folder & Căutare</div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <a class="btn btn-blue" style="text-decoration:none;" href="{{ route('fox.series.rename-vod', ['path' => $basePath]) }}">MEDIA (root)</a>
                    @if (($currentPath ?? $basePath) !== $basePath)
                        <a class="btn btn-gray" style="text-decoration:none;" href="{{ route('fox.series.rename-vod', ['path' => $parentPath, 'q' => $q ?? '', 'sort' => $sort ?? 'name', 'order' => $order ?? 'asc', 'dir_order' => $dir_order ?? 'asc']) }}">↑ Sus</a>
                    @endif
                </div>
            </div>
            <div class="card-b">
                <div class="crumbs" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    @foreach(($breadcrumb ?? []) as $idx => $c)
                        @if($idx > 0)
                            <span>›</span>
                        @endif
                        <a href="{{ route('fox.series.rename-vod', ['path' => $c['path'], 'q' => $q ?? '', 'sort' => $sort ?? 'name', 'order' => $order ?? 'asc', 'dir_order' => $dir_order ?? 'asc']) }}">{{ $c['name'] }}</a>
                    @endforeach
                </div>

                <div style="margin-top:10px; font-size:12px; color: var(--text-muted);">
                    Current path: <span style="font-weight:900; color: var(--text-primary);">{{ $currentPath }}</span>
                </div>

                <form method="GET" action="{{ route('fox.series.rename-vod') }}" style="margin-top:12px; display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                    <input type="hidden" name="path" value="{{ $currentPath }}">
                    <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                    <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                    <input type="hidden" name="dir_order" value="{{ $dir_order ?? 'asc' }}">
                    <div class="field" style="min-width:280px; flex: 1 1 360px;">
                        <label>Căutare în folder</label>
                        <input class="input" type="text" name="q" value="{{ $q ?? '' }}" placeholder="ex: S01E02 or 2024">
                    </div>
                    <div style="font-size:12px; color: var(--text-muted); padding-bottom: 10px;">Enter pentru căutare</div>
                </form>

                <div style="margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <div style="font-size:11px; font-weight:900; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px;">Navigare foldere</div>
                    <form method="GET" action="{{ route('fox.series.rename-vod') }}" style="display:flex; align-items:center; gap:10px;">
                        <input type="hidden" name="path" value="{{ $currentPath }}">
                        <input type="hidden" name="q" value="{{ $q ?? '' }}">
                        <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                        <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                        <div class="field" style="margin:0;">
                            <label style="margin-bottom:4px;">Sortare foldere</label>
                            <select class="input" name="dir_order" onchange="this.form.submit()" style="padding: 8px 10px; width:auto;">
                                <option value="asc" {{ ($dir_order ?? 'asc') === 'asc' ? 'selected' : '' }}>A → Z</option>
                                <option value="desc" {{ ($dir_order ?? 'asc') === 'desc' ? 'selected' : '' }}>Z → A</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="fox-table-container" style="margin-top:10px;">
                    <table class="fox-table">
                        <thead>
                            <tr>
                                <th>Folder</th>
                                <th style="width:160px;">Acțiune</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($dirs ?? []) as $d)
                                <tr>
                                    <td style="font-weight:900;">{{ $d['name'] }}</td>
                                    <td>
                                        <a href="{{ route('fox.series.rename-vod', ['path' => $d['path'], 'q' => $q ?? '', 'sort' => $sort ?? 'name', 'order' => $order ?? 'asc', 'dir_order' => $dir_order ?? 'asc']) }}" style="font-weight:900;color:var(--fox-blue);text-decoration:none;">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="color:var(--text-muted);">No subfolders.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-h">
                <div class="card-t">Files</div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <form method="POST" action="{{ route('fox.series.rename-vod.preview') }}" style="margin:0;">
                        @csrf
                        <input type="hidden" name="path" value="{{ $currentPath }}">
                        <input type="hidden" name="q" value="{{ $q ?? '' }}">
                        <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                        <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                        <input type="hidden" name="dir_order" value="{{ $dir_order ?? 'asc' }}">
                        <button class="btn btn-blue" type="submit">Preview TMDb</button>
                    </form>
                </div>
            </div>
            <div class="card-b">
                <form id="bulkVodForm" method="POST" action="{{ route('fox.series.rename-vod.apply') }}">
                    @csrf
                    <input type="hidden" name="path" value="{{ $currentPath }}">
                    <input type="hidden" name="q" value="{{ $q ?? '' }}">
                    <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                    <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                    <input type="hidden" name="dir_order" value="{{ $dir_order ?? 'asc' }}">

                    <div class="fox-table-container">
                        <table class="fox-table">
                            <thead>
                                <tr>
                                    <th style="width:52px;">Sel</th>
                                    <th>Old</th>
                                    <th>TMDb</th>
                                    <th>New (editable)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($files ?? []) as $idx => $f)
                                    @php
                                        $p = ($preview[$f['filename']] ?? null);
                                        $newVal = '';
                                        if (is_array($p) && ($p['ok'] ?? false) && !empty($p['new'])) {
                                            $newVal = (string) $p['new'];
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="items[{{ $idx }}][selected]" value="1" style="transform:scale(1.2);">
                                            <input type="hidden" name="items[{{ $idx }}][old]" value="{{ $f['filename'] }}">
                                        </td>
                                        <td>
                                            <div style="font-weight:900;">{{ $f['filename'] }}</div>
                                            <div style="font-size:12px; color:var(--text-muted);">{{ $f['size_formatted'] ?? '' }}</div>
                                        </td>
                                        <td>
                                            @if (is_array($p) && ($p['ok'] ?? false))
                                                <span class="pill ok">{{ strtoupper((string) ($p['type'] ?? '')) }}</span>
                                                <div style="margin-top:6px; font-weight:900;">{{ $p['tmdb_title'] ?? '' }}</div>
                                                <div style="font-size:12px; color:var(--text-muted);">ID: {{ $p['tmdb_id'] ?? '—' }} @if(!empty($p['tmdb_year'])) • {{ $p['tmdb_year'] }} @endif</div>
                                            @elseif (is_array($p))
                                                <span class="pill bad">NO MATCH</span>
                                                <div style="margin-top:6px; font-size:12px; color:var(--text-muted);">{{ $p['message'] ?? 'No match' }}</div>
                                            @else
                                                <span class="pill">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input class="input" type="text" name="items[{{ $idx }}][new]" value="{{ $newVal }}" placeholder="Preview TMDb first or type manually">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="color:var(--text-muted);">No files found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
                        <div style="font-size:12px; color:var(--text-muted);">Selectează fișierele și apasă Apply.</div>
                        <button class="btn btn-blue" type="submit">Apply Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
