@extends('layouts.panel')

@section('full_width', true)

@section('content')
@php
    $fmtPct = function ($value): string {
        if (!is_numeric($value)) return '—';
        return number_format((float) $value, 1, '.', '') . '%';
    };

    $cpuText = $cpuUsage !== null ? $fmtPct($cpuUsage) : '—';
    $ramText = is_numeric($ramUsage) ? $fmtPct($ramUsage) : '—';

    $diskFreePctText = '—';
    if (is_array($diskStats) && isset($diskStats['free_pct'])) {
        $diskFreePctText = $fmtPct($diskStats['free_pct']);
    } elseif (is_numeric($diskUsedPct)) {
        $diskFreePctText = $fmtPct(max(0, 100 - (float) $diskUsedPct));
    }

    $loadText = (is_numeric($load1) && is_numeric($cores))
        ? number_format((float) $load1, 2, '.', '') . ' / ' . (int) $cores
        : '—';

    $uptimeText = is_string($uptime) ? $uptime : '—';

    $nowTs = now();

    $state = (string) ($systemState ?? 'ok');
    $stateBadge = match ($state) {
        'critical' => ['danger', 'CRITICAL'],
        'warning'  => ['warning', 'WARNING'],
        default    => ['success', 'OK'],
    };

    $statusBadge = function ($status) {
        $status = (string) ($status ?? 'unknown');
        return match ($status) {
            'running', 'live' => ['success', 'RUNNING'],
            'error'            => ['danger', 'ERROR'],
            'idle', 'stopped'  => ['warning', 'IDLE'],
            default            => ['primary', strtoupper($status)],
        };
    };
@endphp

<div style="padding: 28px; background: #f4f5f7; min-height: 100vh;">
    
    <!-- HEADER -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 32px; font-weight: 800; color: #1f2937; margin: 0 0 8px 0;">
                IPTV Panel Dashboard
            </h1>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Live operational overview · Last refresh: {{ $nowTs->format('Y-m-d H:i:s') }}
            </p>
        </div>
        <div style="display: flex; gap: 16px;">
            @php
                [$stateClass, $stateText] = $stateBadge;
                $badgeColor = $stateClass === 'success' ? '#10b981' : ($stateClass === 'danger' ? '#ef4444' : '#f59e0b');
                $badgeBg = $stateClass === 'success' ? 'rgba(16,185,129,0.1)' : ($stateClass === 'danger' ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.1)');
            @endphp
            <span style="display: inline-flex; align-items: center; font-size: 13px; padding: 8px 16px; background: {{ $badgeBg }}; color: {{ $badgeColor }}; border-radius: 8px; font-weight: 700;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $badgeColor }}; margin-right: 8px; display: inline-block;"></span>
                {{ $stateText }}
            </span>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="{{ route('dashboard') }}" style="padding: 8px 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; color: #374151; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s;">Dashboard</a>
        <a href="{{ route('vod-channels.index') }}" style="padding: 8px 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; color: #374151; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s;">VOD Channels</a>
        <a href="{{ route('encoding-jobs.index') }}" style="padding: 8px 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; color: #374151; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s;">Encoding Jobs</a>
        <a href="{{ url('/streams/all.m3u8') }}" style="padding: 8px 16px; background: #2563eb; border: 1px solid #2563eb; border-radius: 8px; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s;">📥 Download Master M3U</a>
    </div>

    <!-- SYSTEM STATUS CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
        
        <!-- Total Channels Card -->
        <div style="position: relative; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(102,126,234,0.3); overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">📺</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Total Channels</div>
            <div style="font-size: 42px; font-weight: 900; color: #ffffff; line-height: 1; margin-bottom: 8px;">{{ (int) ($totalChannels ?? 0) }}</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 8px;">✅ {{ (int) ($enabledChannels ?? 0) }} enabled · ⏸ {{ (int) ($idleChannels ?? 0) }} idle</div>
        </div>

        <!-- Running Card -->
        <div style="position: relative; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(17,153,142,0.3); overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">▶️</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Running</div>
            <div style="font-size: 42px; font-weight: 900; color: #ffffff; line-height: 1; margin-bottom: 8px;">{{ (int) ($runningChannels ?? 0) }}</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 8px;">Active streaming</div>
            @if(($runningChannels ?? 0) > 0)
                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #ffffff; background: rgba(255,255,255,0.25); padding: 6px 12px; border-radius: 20px; margin-top: 8px;">● LIVE</span>
            @endif
        </div>

        <!-- Errors Card -->
        <div style="position: relative; background: linear-gradient(135deg, {{ ($errorChannels ?? 0) > 0 ? '#fc4a1a 0%, #f7b733' : '#11998e 0%, #38ef7d' }} 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px {{ ($errorChannels ?? 0) > 0 ? 'rgba(252,74,26,0.3)' : 'rgba(17,153,142,0.3)' }}; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">{{ ($errorChannels ?? 0) > 0 ? '⚠️' : '✅' }}</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Errors</div>
            <div style="font-size: 42px; font-weight: 900; color: #ffffff; line-height: 1; margin-bottom: 8px;">{{ (int) ($errorChannels ?? 0) }}</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 8px;">{{ ($errorChannels ?? 0) > 0 ? 'Need attention' : 'All good' }}</div>
        </div>

        <!-- Disk Used Card -->
        <div style="position: relative; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(240,147,251,0.3); overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">💽</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Disk Used</div>
            <div style="font-size: 42px; font-weight: 900; color: #ffffff; line-height: 1; margin-bottom: 8px;">{{ $diskFreePctText }}</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 8px;">Storage capacity</div>
        </div>

        <!-- CPU Card -->
        <div style="position: relative; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(79,172,254,0.3); overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">🧠</div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">CPU</div>
            <div style="font-size: 42px; font-weight: 900; color: #ffffff; line-height: 1; margin-bottom: 8px;">{{ $cpuText }}</div>
            <div style="font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 8px;">Load: {{ $loadText }}</div>
        </div>

        <!-- RAM Card -->
        <div style="position: relative; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(168,237,234,0.3); overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">🧬</div>
            <div style="font-size: 12px; color: #1f2937; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">RAM</div>
            <div style="font-size: 42px; font-weight: 900; color: #111827; line-height: 1; margin-bottom: 8px;">{{ $ramText }}</div>
            <div style="font-size: 13px; color: #374151; margin-top: 8px;">Memory usage</div>
        </div>
        <!-- Uptime Card -->
        <div style="position: relative; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(250,112,154,0.3); overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.15;">⏱</div>
            <div style="font-size: 12px; color: #1f2937; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Uptime</div>
            <div style="font-size: 24px; font-weight: 900; color: #111827; line-height: 1; margin-bottom: 8px;">{{ $uptimeText }}</div>
            <div style="font-size: 13px; color: #374151; margin-top: 8px;">System online</div>
        </div>
    </div>

    <!-- ALERTS SUMMARY -->
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 0; margin-bottom: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">⚠️ System Alerts</h3>
                <p style="font-size: 14px; color: #6b7280; margin: 0;">Critical issues and warnings requiring attention</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <span style="padding: 6px 12px; background: rgba(239,68,68,0.1); color: #dc2626; border-radius: 6px; font-size: 13px; font-weight: 600;">Critical: {{ count($alertSummary['critical'] ?? []) }}</span>
                <span style="padding: 6px 12px; background: rgba(245,158,11,0.1); color: #d97706; border-radius: 6px; font-size: 13px; font-weight: 600;">Warning: {{ count($alertSummary['warning'] ?? []) }}</span>
                <span style="padding: 6px 12px; background: rgba(16,185,129,0.1); color: #059669; border-radius: 6px; font-size: 13px; font-weight: 600;">OK: {{ (int)($alertSummary['ok'] ?? 0) }}</span>
            </div>
        </div>
        <div style="padding: 0;">
            <div style="border: none; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="width: 120px; padding: 16px; text-align: left; font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Severity</th>
                            <th style="padding: 16px; text-align: left; font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Issue</th>
                            <th style="padding: 16px; text-align: left; font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Impact</th>
                            <th style="width: 180px; padding: 16px; text-align: right; font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($healthRows ?? []) as $row)
                            @php
                                $sev = (string) ($row['severity'] ?? 'warning');
                                $sevColor = match ($sev) {
                                    'critical' => ['#dc2626', 'rgba(239,68,68,0.1)'],
                                    'warning'  => ['#d97706', 'rgba(245,158,11,0.1)'],
                                    default    => ['#2563eb', 'rgba(37,99,235,0.1)'],
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 16px;"><span style="padding: 6px 12px; background: {{ $sevColor[1] }}; color: {{ $sevColor[0] }}; border-radius: 6px; font-size: 12px; font-weight: 700;">{{ strtoupper($sev) }}</span></td>
                                <td style="padding: 16px; font-weight: 600; color: #111827;">{{ $row['issue'] ?? '' }}</td>
                                <td style="padding: 16px; color: #6b7280;">{{ $row['impact'] ?? '' }}</td>
                                <td style="padding: 16px; text-align: right;">
                                    @if(!empty($row['action_url']))
                                        <a style="padding: 8px 16px; background: #2563eb; color: #ffffff; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;" href="{{ $row['action_url'] }}">
                                            {{ $row['action_label'] ?? 'View' }}
                                        </a>
                                    @else
                                        <span style="color: #d1d5db;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding: 60px 40px; text-align: center;">
                                    <div style="font-size: 48px; margin-bottom: 16px;">✓</div>
                                    <div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">All Systems Operational</div>
                                    <div style="font-size: 14px; color: #6b7280;">No issues detected</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="g-grid g-grid-2 g-mb-lg">
        <!-- SYSTEM RESOURCES -->
        <div class="g-panel">
            <div class="g-panel-header">
                <div>
                    <h3 class="g-panel-title">💻 System Resources</h3>
                    <p class="g-panel-subtitle">Real-time server performance metrics</p>
                </div>
                <a href="{{ route('file-browser.index') }}" class="g-btn g-btn-ghost g-btn-sm">Storage →</a>
            </div>
            <div class="g-panel-body">
                <div style="display: grid; gap: 20px;">
                    <!-- CPU -->
                    <div>
                        <div class="g-flex g-justify-between g-mb-sm">
                            <span class="g-label">CPU Usage</span>
                            <span style="font-size: 18px; font-weight: 700; color: var(--g-text-primary);">{{ $cpuText }}</span>
                        </div>
                        <div class="g-progress">
                            <div class="g-progress-bar {{ (float)($cpuUsage ?? 0) > 80 ? 'danger' : ((float)($cpuUsage ?? 0) > 60 ? 'warning' : 'success') }}" 
                                 style="width: {{ min(100, (float)($cpuUsage ?? 0)) }}%;">
                            </div>
                        </div>
                        <div style="font-size: 12px; color: var(--g-text-muted); margin-top: 4px;">
                            Load/Cores: {{ $loadText }}
                        </div>
                    </div>

                    <!-- RAM -->
                    <div>
                        <div class="g-flex g-justify-between g-mb-sm">
                            <span class="g-label">Memory Usage</span>
                            <span style="font-size: 18px; font-weight: 700; color: var(--g-text-primary);">{{ $ramText }}</span>
                        </div>
                        <div class="g-progress">
                            <div class="g-progress-bar {{ (float)($ramUsage ?? 0) > 80 ? 'danger' : ((float)($ramUsage ?? 0) > 60 ? 'warning' : 'success') }}" 
                                 style="width: {{ min(100, (float)($ramUsage ?? 0)) }}%;">
                            </div>
                        </div>
                        <div style="font-size: 12px; color: var(--g-text-muted); margin-top: 4px;">
                            System memory usage
                        </div>
                    </div>

                    <!-- DISK -->
                    <div>
                        <div class="g-flex g-justify-between g-mb-sm">
                            <span class="g-label">Disk Free</span>
                            <span style="font-size: 18px; font-weight: 700; color: var(--g-text-primary);">{{ $diskFreePctText }}</span>
                        </div>
                        <div class="g-progress">
                            <div class="g-progress-bar success" style="width: {{ min(100, (float)($diskStats['free_pct'] ?? 50)) }}%;"></div>
                        </div>
                        <div style="font-size: 12px; color: var(--g-text-muted); margin-top: 4px;">
                            {{ $diskStats['free_gb'] ?? 0 }} GB free of {{ $diskStats['total_gb'] ?? 0 }} GB
                        </div>
                    </div>

                    <!-- NETWORK -->
                    <div>
                        <div class="g-flex g-justify-between g-mb-sm">
                            <span class="g-label">Network Activity</span>
                            <span style="font-size: 18px; font-weight: 700; color: var(--g-text-primary);">{{ (int) ($networkStats['total_mbps'] ?? 0) }} MB/s</span>
                        </div>
                        <div style="font-size: 12px; color: var(--g-text-muted);">
                            ↓ In: {{ (int) ($networkStats['input_mbps'] ?? 0) }} MB/s · ↑ Out: {{ (int) ($networkStats['output_mbps'] ?? 0) }} MB/s
                        </div>
                    </div>

                    <!-- UPTIME -->
                    <div>
                        <div class="g-label g-mb-sm">System Uptime</div>
                        <div style="font-size: 20px; font-weight: 700; color: var(--g-brand-success);">{{ $uptimeText }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT CHANNELS -->
        <div class="g-panel">
            <div class="g-panel-header">
                <div>
                    <h3 class="g-panel-title">📺 Recent Channel Activity</h3>
                    <p class="g-panel-subtitle">Latest channel updates and modifications</p>
                </div>
                <a href="{{ route('vod-channels.index') }}" class="g-btn g-btn-primary g-btn-sm">View All →</a>
            </div>
            <div class="g-panel-body" style="padding: 0;">
                <div class="g-table-container" style="border: none;">
                    <table class="g-table">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 100px;">Playlist</th>
                                <th style="width: 160px;">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($recentChannels ?? []) as $ch)
                                @php
                                    [$badgeClass, $badgeText] = $statusBadge($ch->status);
                                @endphp
                                <tr>
                                    <td style="font-weight: 600; color: var(--g-text-primary);">{{ $ch->name }}</td>
                                    <td><span class="g-badge g-badge-{{ $badgeClass }}">{{ $badgeText }}</span></td>
                                    <td style="font-variant-numeric: tabular-nums; text-align: center;">
                                        <span class="g-badge g-badge-neutral">{{ (int) ($ch->playlist_items_count ?? 0) }}</span>
                                    </td>
                                    <td style="font-size: 12px; color: var(--g-text-muted);">{{ $ch->updated_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="g-empty-state">
                                        <div class="g-empty-description">No recent channel activity</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
