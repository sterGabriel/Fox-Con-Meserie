@extends('layouts.panel')

@section('content')
<div style="width: 100%; max-width: none; margin: 0;">
@php
    $diskUsedText = is_numeric($diskUsedPct) ? ($diskUsedPct . '%') : '—';
    $cpuText = $cpuUsage !== null ? (round($cpuUsage, 1) . '%') : '—';

    $ramText = '—';
    if (is_array($ramUsage) && isset($ramUsage['percent'])) {
        $ramText = round((float) $ramUsage['percent'], 1) . '%';
    } elseif (is_numeric($ramUsage)) {
        $ramText = round((float) $ramUsage, 1) . '%';
    }

    $uptimeText = is_string($uptime) ? $uptime : '—';

    $formatUptimeDDHHMM = function (string $raw): string {
        $raw = trim($raw);
        if ($raw === '' || $raw === 'N/A') return '—';

        $seconds = null;

        // If /proc/uptime style: "12345.67 890.12"
        if (preg_match('/^\d+(?:\.\d+)?\s+\d+(?:\.\d+)?$/', $raw)) {
            $seconds = (int) floor((float) explode(' ', $raw)[0]);
        }

        // uptime -p style: "up 3 weeks, 2 days, 19 hours, 39 minutes"
        if ($seconds === null) {
            $raw2 = preg_replace('/^up\s+/i', '', $raw);
            $chunks = preg_split('/,\s*/', $raw2);
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
                if (preg_match('/(\d+)\s+([a-zA-Z]+)/', $chunk, $m)) {
                    $n = (int) $m[1];
                    $u = strtolower($m[2]);
                    if (isset($unitSeconds[$u])) {
                        $acc += $n * $unitSeconds[$u];
                        $matched = true;
                    }
                }
            }
            if ($matched) {
                $seconds = $acc;
            }
        }

        if (!is_int($seconds)) {
            return '—';
        }

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        return sprintf('%02d:%02d:%02d', $days, $hours, $minutes);
    };

    $uptimeDDHHMM = is_string($uptimeText) ? $formatUptimeDDHHMM($uptimeText) : '—';

    $channelCards = [
        ['icon' => '📺', 'label' => 'Total Channels',   'value' => $totalChannels,   'variant' => 'blue'],
        ['icon' => '✅', 'label' => 'Enabled',          'value' => $enabledChannels, 'variant' => 'green'],
        ['icon' => '▶',  'label' => 'Running',          'value' => $runningChannels, 'variant' => 'green'],
        ['icon' => '⚠️', 'label' => 'Errors',           'value' => $errorChannels,   'variant' => 'red'],
        ['icon' => '⏸',  'label' => 'Idle',             'value' => $idleChannels,    'variant' => 'yellow'],
    ];

    $hardwareCards = [
        ['icon' => '💽', 'label' => 'Disk Used',         'value' => $diskUsedText,    'variant' => 'yellow'],
        ['icon' => '🧠', 'label' => 'CPU',              'value' => $cpuText,         'variant' => 'blue'],
        ['icon' => '🧬', 'label' => 'RAM',              'value' => $ramText,         'variant' => 'purple'],
    ];

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

    $formatHHMM = function (?int $seconds): string {
        if (!is_int($seconds) || $seconds < 0) return '—';
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    };

    $pageNowTs = now()->timestamp;
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;">
    <h1 style="margin:0;font-size:28px;font-weight:800;color:#111827;">Dashboard</h1>
</div>

{{-- Metrics Grid: 2 Cards Side by Side --}}
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
    
    {{-- Channel Metrics --}}
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">📊</span> Channel Status
        </h3>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0; text-align: center;">
            <div style="padding: 16px; border-right: 1px solid #f3f4f6;">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">📺 Total</div>
                <div style="font-size: 32px; font-weight: 900; color: #667eea;">{{ $totalChannels }}</div>
            </div>
            <div style="padding: 16px; border-right: 1px solid #f3f4f6;">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">✅ Enabled</div>
                <div style="font-size: 32px; font-weight: 900; color: #11998e;">{{ $enabledChannels }}</div>
            </div>
            <div style="padding: 16px; border-right: 1px solid #f3f4f6;">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">▶ Running</div>
                <div style="font-size: 32px; font-weight: 900; color: #10b981;">{{ $runningChannels }}</div>
            </div>
            <div style="padding: 16px; border-right: 1px solid #f3f4f6;">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">⚠️ Errors</div>
                <div style="font-size: 32px; font-weight: 900; color: #ef4444;">{{ $errorChannels }}</div>
            </div>
            <div style="padding: 16px;">
                <div style="font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">⏸ Idle</div>
                <div style="font-size: 32px; font-weight: 900; color: #f59e0b;">{{ $idleChannels }}</div>
            </div>
        </div>
    </div>

    {{-- Hardware Metrics --}}
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">💻</span> Hardware Resources
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px;">
            @foreach($hardwareCards as $card)
            @php
                $gradients = [
                    'blue'   => ['from' => '#667eea', 'to' => '#764ba2', 'shadow' => 'rgba(102,126,234,0.3)'],
                    'green'  => ['from' => '#11998e', 'to' => '#38ef7d', 'shadow' => 'rgba(17,153,142,0.3)'],
                    'red'    => ['from' => '#fc4a1a', 'to' => '#f7b733', 'shadow' => 'rgba(252,74,26,0.3)'],
                    'yellow' => ['from' => '#f093fb', 'to' => '#f5576c', 'shadow' => 'rgba(240,147,251,0.3)'],
                    'purple' => ['from' => '#a8edea', 'to' => '#fed6e3', 'shadow' => 'rgba(168,237,234,0.3)'],
                ];
                $grad = $gradients[$card['variant']] ?? $gradients['blue'];
                $textColor = ($card['variant'] === 'purple') ? '#111827' : '#ffffff';
                $labelColor = ($card['variant'] === 'purple') ? 'rgba(31,41,55,0.8)' : 'rgba(255,255,255,0.9)';
                $descColor = ($card['variant'] === 'purple') ? 'rgba(55,65,81,0.8)' : 'rgba(255,255,255,0.8)';
            @endphp
            <div style="position: relative; background: linear-gradient(135deg, {{ $grad['from'] }} 0%, {{ $grad['to'] }} 100%); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px {{ $grad['shadow'] }}; overflow: hidden; min-height: 110px;">
                <div style="position: absolute; top: -15px; right: -15px; font-size: 60px; opacity: 0.15;">{{ $card['icon'] }}</div>
                <div style="font-size: 11px; color: {{ $labelColor }}; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">{{ $card['label'] }}</div>
                <div style="font-size: 36px; font-weight: 900; color: {{ $textColor }}; line-height: 1;">{{ $card['value'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Playlists --}}
@php
  $masterM3uUrl = url('/streams/all.m3u8');
@endphp

<div class="fox-table-container" style="padding:16px;margin-top:16px;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div style="font-size:12px;font-weight:800;color:#666;letter-spacing:.04em;text-transform:uppercase;">Playlists</div>
    <a href="{{ $masterM3uUrl }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;background:#2563eb;color:#fff;font-size:12px;font-weight:800;text-decoration:none;">⬇ Download M3U</a>
  </div>
  <div style="margin-top:10px;font-size:12px;color:#666;word-break:break-all;">
    URL: <span style="font-weight:700;color:#111;">{{ $masterM3uUrl }}</span>
  </div>
</div>

{{-- Recent channels --}}
<div class="fox-table-container" style="margin-top:16px;">
    <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;font-weight:800;color:#666;letter-spacing:.04em;text-transform:uppercase;">Recent Channels</div>
        <a href="{{ route('vod-channels.index') }}" style="font-size:12px;font-weight:700;color:#2563eb;text-decoration:none;">View all</a>
    </div>

    <div style="padding:16px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
            @forelse($recentChannels as $ch)
                @php
                    [$badgeColor, $badgeText] = $statusBadge($ch->status);
                    $isRunning = in_array((string) ($ch->status ?? ''), ['running', 'live'], true);

                    $startedAtTs = null;
                    $onlineSeconds = null;
                    if ($isRunning && !empty($ch->started_at)) {
                        try {
                            $startedAtTs = \Carbon\Carbon::parse($ch->started_at)->timestamp;
                            $onlineSeconds = max(0, $pageNowTs - $startedAtTs);
                        } catch (\Throwable $e) {
                            $startedAtTs = null;
                            $onlineSeconds = null;
                        }
                    }

                    $playlistCount = (int) ($ch->playlist_items_count ?? 0);
                    $streamUrl = $streamBase . "/streams/{$ch->id}/hls/stream.m3u8";
                    $hasOutputs = !empty($ch->encoded_output_path) && !empty($ch->hls_output_path);
                @endphp

                <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:14px;display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 auto;">
                            @if(!empty($ch->logo_path))
                                <img src="{{ route('vod-channels.logo.preview', $ch) }}?v={{ urlencode((string) optional($ch->updated_at)->timestamp) }}" alt="" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'" />
                            @else
                                <span style="font-size:18px;opacity:.55;">📺</span>
                            @endif
                        </div>

                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                <div style="font-weight:900;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ch->name }}</div>
                                <span class="fox-badge {{ $badgeColor }}" style="flex:0 0 auto;">{{ $badgeText }}</span>
                            </div>
                            <div style="margin-top:4px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <span class="fox-badge blue">ID: {{ $ch->id }}</span>
                                @if((int) $ch->enabled === 1)
                                    <span class="fox-badge green">ENABLED</span>
                                @else
                                    <span class="fox-badge red">DISABLED</span>
                                @endif
                                @if($hasOutputs)
                                    <span class="fox-badge green">OUTPUT OK</span>
                                @else
                                    <span class="fox-badge yellow">OUTPUT?</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div style="background:#f9fafb;border:1px solid #f0f0f0;border-radius:12px;padding:10px;">
                            <div style="font-size:11px;font-weight:900;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">Online For</div>
                            <div style="margin-top:4px;font-size:13px;font-weight:900;color:#111;">
                                @if($startedAtTs !== null)
                                    <span class="js-online-hhmm" data-started-at="{{ $startedAtTs }}">{{ $formatHHMM($onlineSeconds) }}</span>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div style="background:#f9fafb;border:1px solid #f0f0f0;border-radius:12px;padding:10px;">
                            <div style="font-size:11px;font-weight:900;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">Playlist Items</div>
                            <div style="margin-top:4px;font-size:13px;font-weight:900;color:#111;">{{ $playlistCount }}</div>
                        </div>
                    </div>

                    <div style="background:#f9fafb;border:1px solid #f0f0f0;border-radius:12px;padding:10px;">
                        <div style="font-size:11px;font-weight:900;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">Stream URL (HLS)</div>
                        <div style="margin-top:4px;font-size:12px;color:#111;word-break:break-all;">{{ $streamUrl }}</div>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                        <button type="button" data-copy-text="{{ $streamUrl }}" onclick="copyTextFromButton(this)" style="display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;background:#fff;color:#111;border:1px solid #e5e7eb;font-size:12px;font-weight:800;">📋 Copy Playlist</button>
                                                <a href="{{ $streamUrl }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;background:#2563eb;color:#fff;font-size:12px;font-weight:800;text-decoration:none;">▶ Open Stream</a>
                                                <a href="{{ route('vod-channels.playlist', $ch) }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;background:#111;color:#fff;font-size:12px;font-weight:800;text-decoration:none;">▶ Playlist</a>
                                                <a href="{{ route('vod-channels.settings', $ch) }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;background:#fff;color:#111;border:1px solid #e5e7eb;font-size:12px;font-weight:800;text-decoration:none;">⚙ Settings</a>
                    </div>
                </div>
            @empty
                <div style="color:#666;">No channels found.</div>
            @endforelse
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
        btn.textContent = ok ? '✓ Copied' : 'Copy failed';
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
</div>
@endsection

