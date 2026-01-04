@extends('layouts.panel')

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <div>
            <h1 style="margin:0;font-size:24px;font-weight:900;color:#0f172a;">Stream Monitor</h1>
            <div style="margin-top:6px;font-size:13px;color:#64748b;">Real-time monitoring: viewers, bandwidth, bitrate, runtime, and FFmpeg speed.</div>
        </div>
    </div>

    <div class="fox-table-container" style="padding: 16px; margin-top: 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;">
            <div>
                <div style="font-weight:900; color:#111827;">Live Streams Monitor</div>
                <div style="margin-top:2px;font-size:12px;color:#6b7280;">Shows channels that have requests in the last window.</div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="font-size:12px;color:#6b7280;">Channel ID:</div>
                    <input id="liveChannelId" placeholder="(auto)" style="width:90px; padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; font-size: 13px;" />
                    <button id="liveApply" type="button" style="padding:8px 10px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:800;">Apply</button>
                </div>
                <div style="font-size:12px;color:#6b7280;">Updates every 5s • window {{ $activeWindowSeconds ?? 300 }}s • <span id="liveTime">-</span></div>
            </div>
        </div>

        <div id="liveList" style="display:flex; flex-direction:column; gap:12px;">
            <div style="padding:12px;border:1px solid #eef2f7;border-radius:14px;background:#fff;">
                <div style="font-weight:900;">Loading…</div>
                <div style="margin-top:8px;font-size:12px;color:#64748b;">Waiting for data</div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const list = document.getElementById('liveList');
            const timeEl = document.getElementById('liveTime');
            const channelInput = document.getElementById('liveChannelId');
            const applyBtn = document.getElementById('liveApply');
            if (!list) return;

            function esc(s) {
                return String(s ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function fmtBytes(n) {
                const v = Number(n || 0);
                if (!isFinite(v) || v <= 0) return '0 B';
                const units = ['B','KB','MB','GB','TB'];
                let i = 0;
                let x = v;
                while (x >= 1024 && i < units.length - 1) { x /= 1024; i++; }
                return (i === 0 ? String(Math.round(x)) : x.toFixed(2)) + ' ' + units[i];
            }

            function fmtMbps(bps) {
                const v = Number(bps || 0);
                if (!isFinite(v) || v <= 0) return '0 Mbps';
                return (v / 1_000_000).toFixed(2) + ' Mbps';
            }

            function fmtUptime(seconds) {
                const s = Number(seconds);
                if (!isFinite(s) || s < 0) return '-';
                const d = Math.floor(s / 86400);
                const h = Math.floor((s % 86400) / 3600);
                const m = Math.floor((s % 3600) / 60);
                const ss = Math.floor(s % 60);
                if (d > 0) return `${d}d ${h}h ${m}m`;
                if (h > 0) return `${h}h ${m}m ${ss}s`;
                if (m > 0) return `${m}m ${ss}s`;
                return `${ss}s`;
            }

            function getChannelId() {
                const v = (channelInput && channelInput.value ? channelInput.value.trim() : '');
                if (v === '') return '';
                return /^\d+$/.test(v) ? v : '';
            }

            function buildUrl() {
                const base = "{{ route('tools.stream-monitor.live') }}";
                const params = new URLSearchParams();
                const ch = getChannelId();
                if (ch) params.set('channel_id', ch);
                params.set('window', '{{ $activeWindowSeconds ?? 300 }}');
                const qs = params.toString();
                return qs ? (base + '?' + qs) : base;
            }

            function renderChannel(ch) {
                const ips = Array.isArray(ch.ips) ? ch.ips : [];
                const status = esc(ch.channel_status || '-');
                const pid = ch.encoder_pid ? esc(ch.encoder_pid) : '-';
                const running = (ch.is_running === true) ? 'yes' : (ch.is_running === false ? 'no' : '-');
                const startedAt = esc(ch.started_at || '-');
                const uptime = ch.uptime_seconds != null ? fmtUptime(ch.uptime_seconds) : '-';
                const lastStopped = esc(ch.last_stopped_at || '-');
                const lastDowntime = ch.last_downtime_seconds != null ? fmtUptime(ch.last_downtime_seconds) : '-';
                const stoppedSince = ch.stopped_since_seconds != null ? fmtUptime(ch.stopped_since_seconds) : null;
                const ffSpeed = esc(ch.ffmpeg_speed || '-');

                const uniqueIps = Number(ch.unique_ips || 0);
                const req = Number(ch.requests || 0);
                const bytes = Number(ch.bytes_total || 0);
                const mbps = fmtMbps(ch.bitrate_observed_bps || ch.bitrate_bps || 0);
                const lastSeen = esc(ch.last_seen_at || '-');

                const header = `
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                        <div>
                            <div style="font-weight:900; color:#111827;">${esc(ch.channel_name)} <span style="font-weight:800;color:#64748b;">(#${esc(ch.channel_id)})</span></div>
                            <div style="margin-top:6px;font-size:12px;color:#64748b;">IPs: ${uniqueIps} • Req: ${req} • Egress: ${esc(fmtBytes(bytes))} • Out: ${esc(mbps)} • Window: last ${esc(ch.active_window_seconds)}s • Last: ${lastSeen}</div>
                        </div>
                    </div>
                `;

                const meta = `
                    <div style="display:grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap:10px; margin-top:10px; padding-top:10px; border-top:1px solid #f1f5f9;">
                        <div><div style="font-size:11px; font-weight:900; color:#64748b; text-transform:uppercase;">Status</div><div style="margin-top:2px; font-size:13px; font-weight:900; color:#0f172a;">${status}</div></div>
                        <div><div style="font-size:11px; font-weight:900; color:#64748b; text-transform:uppercase;">PID</div><div style="margin-top:2px; font-size:13px; font-weight:900; color:#0f172a;">${pid}</div></div>
                        <div><div style="font-size:11px; font-weight:900; color:#64748b; text-transform:uppercase;">Running</div><div style="margin-top:2px; font-size:13px; font-weight:900; color:#0f172a;">${esc(running)}</div></div>
                        <div><div style="font-size:11px; font-weight:900; color:#64748b; text-transform:uppercase;">Started</div><div style="margin-top:2px; font-size:13px; color:#0f172a; font-variant-numeric: tabular-nums;">${startedAt}</div></div>
                        <div><div style="font-size:11px; font-weight:900; color:#64748b; text-transform:uppercase;">Uptime</div><div style="margin-top:2px; font-size:13px; font-weight:900; color:#0f172a;">${esc(uptime)}</div></div>
                        <div><div style="font-size:11px; font-weight:900; color:#64748b; text-transform:uppercase;">FFmpeg Speed</div><div style="margin-top:2px; font-size:13px; font-weight:900; color:#0f172a;">${ffSpeed}</div></div>
                    </div>
                    <div style="margin-top:8px; font-size:12px; color:#94a3b8;">Last stop: ${lastStopped} • Last downtime: ${esc(lastDowntime)}${stoppedSince ? (' • Stopped since: ' + esc(stoppedSince)) : ''}</div>
                `;

                const tableHead = `
                    <div style="display:grid; grid-template-columns: 180px 84px 120px 170px 1fr; gap:10px; padding:10px 0; color:#64748b; font-size:12px; font-weight:900; border-top:1px solid #f1f5f9; margin-top:10px;">
                        <div>IP</div>
                        <div style="text-align:right;">Hits</div>
                        <div style="text-align:right;">Bytes</div>
                        <div>Last Seen</div>
                        <div>Last File / UA</div>
                    </div>
                `;

                const rows = ips.slice(0, 80).map(r => {
                    const ip = esc(r.ip);
                    const hits = Number(r.hit_count || 0);
                    const b = Number(r.bytes_total || 0);
                    const last = esc(r.last_seen_at || '-');
                    const file = esc(r.last_file || '');
                    const ua = esc(r.user_agent || '');
                    const method = esc(r.method || '');
                    const fileUa = [file, (method ? (method + ' ') : '') + ua].filter(Boolean).join(' — ');

                    return `
                        <div style="display:grid; grid-template-columns: 180px 84px 120px 170px 1fr; gap:10px; padding:10px 0; border-top:1px solid #f1f5f9; align-items:center;">
                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${ip}</div>
                            <div style="text-align:right; font-size:12px; color:#111827; font-weight:900; white-space:nowrap;">${hits}</div>
                            <div style="text-align:right; font-size:12px; color:#111827; font-weight:900; white-space:nowrap;">${esc(fmtBytes(b))}</div>
                            <div style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${last}</div>
                            <div style="font-size:12px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${fileUa || '-'}</div>
                        </div>
                    `;
                }).join('');

                const body = ips.length
                    ? `<div style="margin-top:10px; overflow:auto;">${tableHead}${rows}</div>`
                    : '<div style="margin-top:10px;font-size:12px;color:#94a3b8;">No active IPs</div>';

                return `<div style="padding:12px;border:1px solid #eef2f7;border-radius:14px;background:#fff; overflow:hidden;">
                    ${header}
                    ${meta}
                    ${body}
                </div>`;
            }

            async function refresh() {
                try {
                    const res = await fetch(buildUrl(), { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();

                    if (timeEl) timeEl.textContent = json.server_time || '-';
                    const channels = Array.isArray(json.channels) ? json.channels : [];

                    if (channels.length === 0) {
                        list.innerHTML = '<div style="padding:12px;border:1px solid #eef2f7;border-radius:14px;background:#fff;"><div style="font-weight:900;">No active stream data</div><div style="margin-top:8px;font-size:12px;color:#64748b;">No requests in the last window</div></div>';
                        return;
                    }

                    list.innerHTML = channels.map(renderChannel).join('');
                } catch (e) {
                    if (timeEl) timeEl.textContent = 'error';
                }
            }

            refresh();
            setInterval(refresh, 5000);

            if (applyBtn) {
                applyBtn.addEventListener('click', function () {
                    refresh();
                });
            }
        })();
    </script>
@endsection
