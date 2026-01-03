@extends('layouts.panel')

@section('full_width', true)

@section('content')
@php
    $fmtPct = function ($value): string {
        if (!is_numeric($value)) return '—';
        return number_format((float) $value, 1, '.', '') . '%';
    };

    $diskFreePctText = '—';
    if (is_array($diskStats) && isset($diskStats['free_pct'])) {
        $diskFreePctText = $fmtPct($diskStats['free_pct']);
    } elseif (is_numeric($diskUsedPct)) {
        $diskFreePctText = $fmtPct(max(0, 100 - (float) $diskUsedPct));
    }

    $cpuText = $cpuUsage !== null ? $fmtPct($cpuUsage) : '—';
    $ramText = is_numeric($ramUsage) ? $fmtPct($ramUsage) : '—';

    $uptimeText = is_string($uptime) ? $uptime : '—';

    $uptimeSecondsFromRaw = function (string $raw): ?int {
        $raw = trim($raw);
        if ($raw === '' || $raw === 'N/A') return null;

        // /proc/uptime style: "12345.67 890.12"
        if (preg_match('/^\d+(?:\.\d+)?\s+\d+(?:\.\d+)?$/', $raw)) {
            return (int) floor((float) explode(' ', $raw)[0]);
        }

        // uptime -p style: "up 3 weeks, 2 days, 19 hours, 39 minutes"
        $raw2 = preg_replace('/^up\s+/i', '', $raw);
        $chunks = preg_split('/,\s*/', (string) $raw2);
        $unitSeconds = [
            'week' => 604800,
            'weeks' => 604800,
            'day' => 86400,
            'days' => 86400,
            'hour' => 3600,
            'hours' => 3600,
            'minute' => 60,
            'minutes' => 60,
        ];

        $acc = 0;
        $matched = false;
        foreach ($chunks as $chunk) {
            if (preg_match('/(\d+)\s+([a-zA-Z]+)/', (string) $chunk, $m)) {
                $n = (int) $m[1];
                $u = strtolower($m[2]);
                if (isset($unitSeconds[$u])) {
                    $acc += $n * $unitSeconds[$u];
                    $matched = true;
                }
            }
        }

        return $matched ? $acc : null;
    };

    $formatUptimeHuman = function (?int $seconds): string {
        if (!is_int($seconds) || $seconds < 0) return '—';
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);
        if ($days > 0) return $days . ' days ' . $hours . ' hours';
        if ($hours > 0) return $hours . ' hours ' . $minutes . ' min';
        return $minutes . ' min';
    };

    $uptimeHuman = $formatUptimeHuman($uptimeSecondsFromRaw((string) $uptimeText));

    $loadText = (is_numeric($load1) && is_numeric($cores))
        ? number_format((float) $load1, 2, '.', '') . ' / ' . (int) $cores
        : '—';

    $state = (string) ($systemState ?? 'ok');
    $stateBadge = match ($state) {
        'critical' => ['red', 'CRITICAL'],
        'warning'  => ['yellow', 'WARNING'],
        default    => ['green', 'OK'],
    };

    $cards = [
        ['label' => 'Total Channels', 'value' => (int) $totalChannels, 'variant' => 'blue'],
        ['label' => 'Enabled', 'value' => (int) $enabledChannels, 'variant' => 'green'],
        ['label' => 'Running', 'value' => (int) $runningChannels, 'variant' => 'green'],
        ['label' => 'Error', 'value' => (int) $errorChannels, 'variant' => 'red'],
        ['label' => 'Encoding Running', 'value' => (int) ($jobsStats['running'] ?? 0), 'variant' => 'yellow'],
        ['label' => 'Encoding Queued', 'value' => (int) ($jobsStats['queued'] ?? 0), 'variant' => 'blue'],
        ['label' => 'Encoding Failed', 'value' => (int) ($jobsStats['failed'] ?? 0), 'variant' => 'red'],
        ['label' => 'Disk Free', 'value' => $diskFreePctText, 'variant' => 'yellow'],
        ['label' => 'CPU', 'value' => $cpuText, 'variant' => 'blue', 'subtitle' => $loadText !== '—' ? ('Load/Cores: ' . $loadText) : null],
        ['label' => 'RAM', 'value' => $ramText, 'variant' => 'purple'],
        ['label' => 'Uptime', 'value' => $uptimeHuman, 'variant' => 'blue'],
    ];

    $healthRowsSafe = $healthRows ?? [];

    $statusBadge = function ($status) {
        $status = (string) ($status ?? 'unknown');
        return match ($status) {
            'running', 'live' => ['green', 'RUNNING'],
            'error'            => ['red', 'ERROR'],
            'idle', 'stopped'  => ['yellow', 'IDLE'],
            default   => ['blue', strtoupper($status)],
        };
    };

    $streamBase = rtrim((string) config('app.streaming_domain', ''), '/');
    if ($streamBase === '' || str_contains($streamBase, 'localhost')) {
        $streamBase = rtrim((string) request()->getSchemeAndHttpHost(), '/');
    }

    $masterM3uUrl = url('/streams/all.m3u8');

    $formatHHMM = function (?int $seconds): string {
        if (!is_int($seconds) || $seconds < 0) return '—';
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    };

    $pageNowTs = now()->timestamp;
@endphp

<style>
    .dash-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px; }
    .dash-title { margin:0; font-size:24px; font-weight:900; color:var(--text-primary); }
    .dash-sub { margin-top:6px; font-size:12px; color:var(--text-muted); }
    .dash-links { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .dash-link { display:inline-flex; align-items:center; justify-content:center; padding:10px 12px; border-radius:6px; border:1px solid var(--border-color); background:var(--card-bg); color:var(--text-primary); font-size:12px; font-weight:800; text-decoration:none; }
    .dash-link.primary { background:var(--fox-blue); border-color:rgba(37,99,235,.35); color:#fff; }
    .dash-link:hover { filter: brightness(0.98); }
    .dash-section-title { font-size:12px; font-weight:900; color:#666; letter-spacing:.04em; text-transform:uppercase; }
</style>

<div class="dash-header">
    <div>
        <h1 class="dash-title">Dashboard</h1>
        <div class="dash-sub">System status: {{ $systemSummaryText ?? '' }}</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        @php
            [$stateColor, $stateText] = $stateBadge;
        @endphp
        <span class="fox-badge {{ $stateColor }}">{{ $stateText }}</span>
        <div class="dash-links">
            @foreach(($quickLinks ?? []) as $l)
                <a class="dash-link" href="{{ $l['url'] }}">{{ $l['label'] }}</a>
            @endforeach
            <a class="dash-link primary" href="{{ $masterM3uUrl }}">Download Master M3U</a>
        </div>
    </div>
</div>

{{-- Health Summary --}}
<div class="fox-table-container" style="margin-bottom:16px;">
    <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div class="dash-section-title">Health Summary</div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="fox-badge red">Critical: {{ count($alertSummary['critical'] ?? []) }}</span>
            <span class="fox-badge yellow">Warning: {{ count($alertSummary['warning'] ?? []) }}</span>
            <span class="fox-badge green">OK: {{ (int)($alertSummary['ok'] ?? 0) }}</span>
        </div>
    </div>

    <div style="overflow:auto;">
        <table class="fox-table">
            <thead>
            <tr>
                <th style="width:120px;">Severity</th>
                <th>Issue</th>
                <th>Impact</th>
                <th style="width:180px;">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($healthRowsSafe as $row)
                @php
                    $sev = (string) ($row['severity'] ?? 'warning');
                    $sevBadge = match ($sev) {
                        'critical' => ['red', 'CRITICAL'],
                        'warning'  => ['yellow', 'WARNING'],
                        default    => ['blue', strtoupper($sev)],
                    };
                @endphp
                <tr>
                    <td><span class="fox-badge {{ $sevBadge[0] }}">{{ $sevBadge[1] }}</span></td>
                    <td style="font-weight:700;">{{ $row['issue'] ?? '' }}</td>
                    <td style="color:#555;">{{ $row['impact'] ?? '' }}</td>
                    <td>
                        @if(!empty($row['action_url']))
                            <a class="dash-link primary" style="padding:8px 10px;" href="{{ $row['action_url'] }}">{{ $row['action_label'] ?? 'Open' }}</a>
                        @else
                            <span style="color:#999;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="padding:18px;color:#999;text-align:center;">All systems OK.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- KPIs --}}
<div class="fox-cards-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));align-items:stretch;">
    @foreach($cards as $card)
        <div class="fox-card {{ $card['variant'] }}" style="min-height:140px;">
            <div class="fox-card-label">{{ $card['label'] }}</div>
            <div class="fox-card-value">{{ $card['value'] }}</div>
            @if(!empty($card['subtitle']))
                <div class="fox-card-subtitle">{{ $card['subtitle'] }}</div>
            @endif
        </div>
    @endforeach
</div>

{{-- Action Center + Integrations --}}
@php
    $actionChannels = collect($recentChannels ?? [])->take(3);
@endphp

<div class="fox-table-container">
    <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div class="dash-section-title">Action Center</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
            <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.index') }}">View all channels</a>
        </div>
    </div>

    <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
        @forelse($actionChannels as $ch)
            @php
                [$badgeColor, $badgeText] = $statusBadge($ch->status);
                $streamUrl = $streamBase . "/streams/{$ch->id}/hls/stream.m3u8";
            @endphp
            <div style="border:1px solid var(--border-color);background:var(--card-bg);border-radius:6px;padding:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="min-width:0;flex:1;display:flex;align-items:center;gap:10px;">
                    <div style="font-weight:900;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ch->name }}</div>
                    <span class="fox-badge {{ $badgeColor }}">{{ $badgeText }}</span>
                    <span class="fox-badge blue">ID: {{ $ch->id }}</span>
                    <div style="min-width:0;flex:1;font-size:12px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $streamUrl }}</div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;flex-shrink:0;">
                    <a class="dash-link" style="padding:8px 10px;" href="{{ $streamUrl }}" target="_blank" rel="noopener">Open Stream</a>
                    <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.playlist', $ch) }}">Playlist</a>
                    <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.settings', $ch) }}">Settings</a>
                </div>
            </div>
        @empty
            <div style="color:#999;">No channels found.</div>
        @endforelse
    </div>
</div>

{{-- Operational Tables --}}
<div style="display:grid;grid-template-columns:1fr;gap:16px;margin-top:16px;">
    <div class="fox-table-container">
        <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div class="dash-section-title">Channels Needing Attention</div>
            <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.index', ['filter' => 'attention']) }}">Open list</a>
        </div>
        <div style="overflow:auto;">
            <table class="fox-table">
                <thead>
                <tr>
                    <th>Channel</th>
                    <th style="width:140px;">Status</th>
                    <th>Issues</th>
                    <th style="width:160px;">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($channelsNeedingAttention ?? []) as $ch)
                    @php
                        [$badgeColor, $badgeText] = $statusBadge($ch->status);
                        $issues = [];
                        if ((int) ($ch->enabled ?? 0) !== 1) $issues[] = 'DISABLED';
                        $hasOutputs = !empty($ch->encoded_output_path) && !empty($ch->hls_output_path);
                        if (!$hasOutputs) $issues[] = 'MISSING OUTPUTS';
                        if (empty($ch->logo_path)) $issues[] = 'MISSING LOGO';
                    @endphp
                    <tr>
                        <td style="font-weight:800;">{{ $ch->name }}</td>
                        <td><span class="fox-badge {{ $badgeColor }}">{{ $badgeText }}</span></td>
                        <td>
                            @if(empty($issues))
                                <span style="color:#999;">—</span>
                            @else
                                @foreach($issues as $i)
                                    <span class="fox-badge yellow">{{ $i }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.settings', $ch) }}">Open Settings</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:18px;color:#999;text-align:center;">No channels need attention.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fox-table-container">
        <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div class="dash-section-title">Now Encoding</div>
            <a class="dash-link" style="padding:8px 10px;" href="{{ route('encoding-jobs.index', ['status' => 'running,processing']) }}">Open queue</a>
        </div>
        <div style="overflow:auto;">
            <table class="fox-table">
                <thead>
                <tr>
                    <th style="width:80px;">Job</th>
                    <th>Channel</th>
                    <th>Video</th>
                    <th style="width:120px;">Status</th>
                    <th style="width:110px;">Progress</th>
                    <th style="width:200px;">Updated</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($nowEncodingJobs ?? []) as $j)
                    <tr>
                        <td style="font-variant-numeric:tabular-nums;">#{{ $j->id }}</td>
                        <td>
                            @if($j->channel)
                                <a href="{{ route('vod-channels.encoding-now', $j->channel) }}" style="color:var(--fox-blue);text-decoration:none;font-weight:700;">{{ $j->channel->name }}</a>
                            @else
                                <span style="color:#999;">—</span>
                            @endif
                        </td>
                        <td>{{ $j->video?->title ?? '—' }}</td>
                        <td><span class="fox-badge yellow">{{ strtoupper((string) $j->status) }}</span></td>
                        <td style="font-variant-numeric:tabular-nums;">{{ (int) ($j->progress ?? 0) }}%</td>
                        <td style="font-variant-numeric:tabular-nums;">{{ $j->updated_at ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:18px;color:#999;text-align:center;">No encoding is currently running.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fox-table-container">
        <div style="padding:14px 16px;border-bottom:1px solid #f0e0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div class="dash-section-title">Recently Changed</div>
            <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.index') }}">Open channels</a>
        </div>
        <div style="overflow:auto;">
            <table class="fox-table">
                <thead>
                <tr>
                    <th>Channel</th>
                    <th style="width:140px;">Status</th>
                    <th style="width:220px;">Updated</th>
                    <th style="width:220px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($recentChannels ?? []) as $ch)
                    @php
                        [$badgeColor, $badgeText] = $statusBadge($ch->status);
                    @endphp
                    <tr>
                        <td style="font-weight:800;">{{ $ch->name }}</td>
                        <td><span class="fox-badge {{ $badgeColor }}">{{ $badgeText }}</span></td>
                        <td style="font-variant-numeric:tabular-nums;">{{ $ch->updated_at }}</td>
                        <td>
                            <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.playlist', $ch) }}">Playlist</a>
                            <a class="dash-link" style="padding:8px 10px;" href="{{ route('vod-channels.settings', $ch) }}">Settings</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:18px;color:#999;text-align:center;">No channels found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    async function copyToClipboard(text) {
        if (!text) return false;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (e) {
            // fall back
        }

        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '-1000px';
            ta.style.left = '-1000px';
            document.body.appendChild(ta);
            ta.select();
            ta.setSelectionRange(0, ta.value.length);
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (e) {
            return false;
        }
    }

    async function copyTextFromButton(btn) {
        const text = btn?.getAttribute('data-copy-text') || '';
        const original = btn.textContent;
        const ok = await copyToClipboard(text);
        btn.textContent = ok ? 'Copied' : 'Copy failed';
        setTimeout(() => { btn.textContent = original; }, 1200);
    }

    function pad2(n) {
        n = Math.floor(Math.max(0, n));
        return (n < 10 ? '0' : '') + String(n);
    }

    function formatHHMMFromSeconds(totalSeconds) {
        totalSeconds = Math.max(0, Math.floor(totalSeconds));
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        return String(hours).padStart(2, '0') + ':' + pad2(minutes);
    }

    function updateOnlineTimers() {
        const nowTs = Math.floor(Date.now() / 1000);
        document.querySelectorAll('.js-online-hhmm').forEach((el) => {
            const startedAt = parseInt(el.getAttribute('data-started-at') || '', 10);
            if (!Number.isFinite(startedAt) || startedAt <= 0) return;
            el.textContent = formatHHMMFromSeconds(nowTs - startedAt);
        });
    }

    updateOnlineTimers();
    setInterval(updateOnlineTimers, 15000);
</script>
@endsection

