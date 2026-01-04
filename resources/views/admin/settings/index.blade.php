@extends('layouts.panel')

@section('content')
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <div>
            <h1 style="margin:0;font-size:24px;font-weight:900;color:#0f172a;">Settings</h1>
            <div style="margin-top:6px;font-size:13px;color:#64748b;">Core configuration for this panel (streaming, TMDB, DNS).</div>
        </div>
    </div>

    @if (session('success'))
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
            <div style="font-weight:900; margin-bottom:6px;">Please fix the errors:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="font-size:13px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf

        <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:14px;">
            <div class="fox-table-container" style="padding: 16px;">
                <div style="font-weight:900; color:#0f172a; margin-bottom:10px;">Streaming</div>
                <div style="font-size:12px; color:#64748b; margin-bottom:10px;">Used to generate public stream URLs (HLS/TS) in playlists/EPG.</div>

                <div style="margin-bottom:10px;">
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Streaming Domain (override)</div>
                    <input name="streaming_domain" value="{{ old('streaming_domain', $streamingDomain ?? '') }}" placeholder="https://your-domain:2090" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                    <div style="margin-top:8px; font-size:12px; color:#94a3b8;">If empty, defaults to config/app.php: <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">{{ $defaultStreamingDomain ?? '' }}</span></div>
                </div>
            </div>

            <div class="fox-table-container" style="padding: 16px;">
                <div style="font-weight:900; color:#0f172a; margin-bottom:10px;">TMDB</div>
                <div style="font-size:12px; color:#64748b; margin-bottom:10px;">Used for posters/images during VOD import and metadata fetch.</div>

                <div>
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">TMDB API Key</div>
                    <input name="tmdb_api_key" value="{{ old('tmdb_api_key', $tmdbKey ?? '') }}" placeholder="ex: 76e0e00b95dc2ccc6fa0d8b8d8c3d89b" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                </div>
            </div>

            <div class="fox-table-container" style="padding: 16px; grid-column: 1 / -1;">
                <div style="font-weight:900; color:#0f172a; margin-bottom:10px;">DNS</div>
                <div style="font-size:12px; color:#64748b; margin-bottom:10px;">Store the DNS servers you want this server to use (manual apply on OS level).</div>

                <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:14px;">
                    <div>
                        <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Desired DNS Servers</div>
                        <textarea name="dns_servers" rows="6" placeholder="1.1.1.1\n8.8.8.8" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">{{ old('dns_servers', $dnsServers ?? '') }}</textarea>
                        <div style="margin-top:8px; font-size:12px; color:#94a3b8;">One per line or comma-separated.</div>
                    </div>

                    <div>
                        <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Current System /etc/resolv.conf</div>
                        <textarea rows="6" readonly style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#f8fafc; font-size:13px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; color:#475569;">{{ $systemDns ?? '' }}</textarea>
                        <div style="margin-top:8px; font-size:12px; color:#94a3b8;">Read-only (OS-level).</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:14px; display:flex; justify-content:flex-end;">
            <button type="submit" style="padding:10px 12px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:900;">Save Settings</button>
        </div>
    </form>
@endsection
