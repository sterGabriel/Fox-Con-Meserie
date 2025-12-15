@extends('layouts.panel')

@section('content')
<script>
    // Density toggle persistence
    document.addEventListener('DOMContentLoaded', function() {
        const saved = localStorage.getItem('dashboard-density') || 'comfy';
        document.documentElement.classList.add('density-' + saved);
        document.getElementById('density-toggle-' + saved).checked = true;
    });

    function toggleDensity(mode) {
        localStorage.setItem('dashboard-density', mode);
        document.documentElement.classList.remove('density-compact', 'density-comfy');
        document.documentElement.classList.add('density-' + mode);
    }
</script>

<style>
    :root.density-compact { --tw-padding-multiplier: 0.75; }
    :root.density-comfy { --tw-padding-multiplier: 1; }
</style>

<div class="flex items-center justify-between">
    <div>
        <div class="text-xs uppercase tracking-wide text-slate-300/70">System Overview</div>
        <h1 class="mt-1 text-2xl font-semibold text-slate-100">Dashboard</h1>
    </div>

    <div class="flex items-center gap-4">
        <div class="rounded-xl border border-slate-500/20 bg-slate-900/40 backdrop-blur-sm shadow-[0_0_0_1px_rgba(255,255,255,0.02)] px-4 py-2 text-sm text-slate-300/80">
            <span class="mr-2">Last updated:</span>
            <span class="font-semibold text-slate-100">{{ now()->format('H:i:s') }}</span>
        </div>

        <div class="flex items-center gap-2 rounded-xl border border-slate-500/20 bg-slate-900/40 px-3 py-2">
            <span class="text-xs text-slate-300/70 mr-2">Density:</span>
            <label class="text-xs text-slate-200/90 cursor-pointer">
                <input type="radio" id="density-toggle-compact" name="density" value="compact" onchange="toggleDensity('compact')" class="mr-1">
                Compact
            </label>
            <label class="text-xs text-slate-200/90 cursor-pointer ml-3">
                <input type="radio" id="density-toggle-comfy" name="density" value="comfy" onchange="toggleDensity('comfy')" class="mr-1">
                Comfy
            </label>
        </div>
    </div>
</div>

{{-- ALERT SUMMARY BLOCK --}}
<div class="mt-6 rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5">
    <div class="text-sm font-semibold text-slate-100 mb-4">Alert Summary</div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 p-4">
            <div class="text-xs text-red-200/70 uppercase tracking-wide">Critical</div>
            <div class="mt-2 text-3xl font-bold text-red-300">{{ count($alertSummary['critical'] ?? []) }}</div>
            @if(count($alertSummary['critical'] ?? []) > 0)
                <div class="mt-3 space-y-1">
                    @foreach(array_slice($alertSummary['critical'] ?? [], 0, 3) as $alert)
                        <div class="text-xs text-red-200/80">• {{ $alert }}</div>
                    @endforeach
                    @if(count($alertSummary['critical'] ?? []) > 3)
                        <div class="text-xs text-red-200/60">+{{ count($alertSummary['critical']) - 3 }} more</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
            <div class="text-xs text-amber-200/70 uppercase tracking-wide">Warning</div>
            <div class="mt-2 text-3xl font-bold text-amber-300">{{ count($alertSummary['warning'] ?? []) }}</div>
            @if(count($alertSummary['warning'] ?? []) > 0)
                <div class="mt-3 space-y-1">
                    @foreach(array_slice($alertSummary['warning'] ?? [], 0, 3) as $alert)
                        <div class="text-xs text-amber-200/80">• {{ $alert }}</div>
                    @endforeach
                    @if(count($alertSummary['warning'] ?? []) > 3)
                        <div class="text-xs text-amber-200/60">+{{ count($alertSummary['warning']) - 3 }} more</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4">
            <div class="text-xs text-emerald-200/70 uppercase tracking-wide">Healthy</div>
            <div class="mt-2 text-3xl font-bold text-emerald-300">✓</div>
            <div class="mt-3 text-xs text-emerald-200/80">{{ count($alertSummary['critical'] ?? []) === 0 && count($alertSummary['warning'] ?? []) === 0 ? 'All systems nominal' : 'Address alerts above' }}</div>
        </div>
    </div>
</div>

{{-- KPI ROW (compact + horizontal) --}}
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-4">
        <div class="text-[11px] uppercase tracking-wide text-slate-400">Total Channels</div>
        <div class="mt-1 text-2xl lg:text-3xl font-semibold leading-none text-slate-100">{{ $totalChannels }}</div>
    </div>

    <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-4">
        <div class="text-[11px] uppercase tracking-wide text-slate-400">Enabled</div>
        <div class="mt-1 text-2xl lg:text-3xl font-semibold leading-none text-slate-100">{{ $enabledChannels }}</div>
    </div>

    <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-4">
        <div class="text-[11px] uppercase tracking-wide text-slate-400">Running</div>
        <div class="mt-1 flex items-end justify-between">
            <div class="text-2xl lg:text-3xl font-semibold leading-none text-slate-100">{{ $runningChannels }}</div>
            <span class="text-xs rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-emerald-300">LIVE</span>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-4">
        <div class="text-[11px] uppercase tracking-wide text-slate-400">Errors</div>
        <div class="mt-1 flex items-end justify-between">
            <div class="text-2xl lg:text-3xl font-semibold leading-none text-slate-100">{{ $errorChannels }}</div>
            <span class="text-xs rounded-full border border-red-500/30 bg-red-500/10 px-2 py-1 text-red-300">ALERT</span>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-4">
        <div class="text-[11px] uppercase tracking-wide text-slate-400">Storage Used</div>
        <div class="mt-1 flex items-end justify-between">
            <div class="text-2xl lg:text-3xl font-semibold leading-none text-slate-100">
                {{ $diskUsedPct === null ? 'N/A' : $diskUsedPct . '%' }}
            </div>

            @php
                $storageBadge = 'border-slate-400/20 bg-slate-400/10 text-slate-200/80';
                if (is_numeric($diskUsedPct) && $diskUsedPct >= 85) $storageBadge = 'border-red-500/30 bg-red-500/10 text-red-300';
                elseif (is_numeric($diskUsedPct) && $diskUsedPct >= 70) $storageBadge = 'border-amber-500/30 bg-amber-500/10 text-amber-300';
                elseif (is_numeric($diskUsedPct)) $storageBadge = 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300';
            @endphp

            <span class="text-xs rounded-full border px-2 py-1 {{ $storageBadge }}">HEALTH</span>
        </div>

        <div class="mt-2 text-xs text-slate-300/70">
            {{ $diskTotal ? round($diskTotal/1024/1024/1024, 1) : 0 }} GB total •
            {{ $diskFree ? round($diskFree/1024/1024/1024, 1) : 0 }} GB free
        </div>
    </div>
</div>

{{-- ALERTS + RECENT CHANNELS (12-col grid: 4/8) --}}
<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-12">
    {{-- Alerts (narrow) --}}
    <div class="lg:col-span-4 rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5">
        <div class="text-sm font-semibold text-slate-100">Alerts</div>

        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between rounded-xl border border-slate-500/15 bg-slate-950/30 px-3 py-2">
                <div class="text-sm text-slate-200/90">Missing logo</div>
                <span class="text-[11px] font-medium rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-amber-300">
                    {{ $channelsMissingLogo }}
                </span>
            </div>

            <div class="flex items-center justify-between rounded-xl border border-slate-500/15 bg-slate-950/30 px-3 py-2">
                <div class="text-sm text-slate-200/90">Missing outputs</div>
                <span class="text-[11px] font-medium rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-amber-300">
                    {{ $channelsMissingOutput }}
                </span>
            </div>

            <div class="text-xs text-slate-300/60">
                Alerts are computed from current DB state (MVP).
            </div>
        </div>
    </div>

    {{-- Recent Channels (dominant) --}}
    <div class="lg:col-span-8 rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5">
        <div class="flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-100">Recent Channels</div>
            <a href="{{ route('vod-channels.index') }}"
               class="text-sm text-blue-200/90 hover:text-blue-200 underline underline-offset-4">
                View all
            </a>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-500/15 shadow-[0_0_0_1px_rgba(255,255,255,0.02)]">
            <table class="w-full text-sm">
                <thead class="bg-slate-950/40 text-slate-300/80">
                    <tr>
                        <th class="px-3 py-2 text-left">Channel</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Profile</th>
                        <th class="px-3 py-2 text-left">Updated</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Sort channels by severity
                        $severityMap = [];
                        foreach($recentChannels as $ch) {
                            $sev = 2; // OK default
                            if ($ch->status === 'error' || empty($ch->encoded_output_path) || empty($ch->hls_output_path)) {
                                $sev = 0; // CRITICAL
                            } elseif (empty($ch->logo_path) || $ch->status === 'idle') {
                                $sev = 1; // WARNING
                            }
                            $severityMap[$ch->id] = $sev;
                        }
                        $sortedChannels = $recentChannels->sortBy(function($ch) use ($severityMap) {
                            return $severityMap[$ch->id];
                        });
                    @endphp

                    @forelse($sortedChannels as $ch)
                        @php
                            $status = $ch->status ?? 'unknown';
                            $severity = $severityMap[$ch->id];
                            $badge = match($status) {
                                'running' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                                'idle'    => 'border-slate-400/20 bg-slate-400/10 text-slate-200/80',
                                'error'   => 'border-red-500/30 bg-red-500/10 text-red-300',
                                default   => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                            };
                            $severityBorder = match($severity) {
                                0 => 'border-l-4 border-l-red-500',
                                1 => 'border-l-4 border-l-amber-500',
                                default => 'border-l-4 border-l-slate-500',
                            };
                        @endphp
                        <tr class="border-t border-slate-500/10 hover:bg-slate-800/40 transition {{ $severityBorder }}">
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-lg border border-slate-500/20 bg-slate-950/30 overflow-hidden flex items-center justify-center">
                                        @if($ch->logo_path)
                                            <img class="h-8 w-8 object-contain"
                                                 src="{{ route('vod-channels.logo.preview', $ch) }}"
                                                 alt="logo">
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-100">{{ $ch->name }}</div>
                                        <div class="text-xs text-slate-300/60">ID: {{ $ch->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="text-[11px] font-medium tracking-wide leading-none rounded-full border px-2 py-1 {{ $badge }}">{{ strtoupper($status) }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-slate-200/90">
                                {{ $ch->resolution ?? '—' }} • {{ $ch->video_bitrate ?? '—' }} kbps • {{ $ch->fps ?? '—' }} fps
                            </td>
                            <td class="px-3 py-2.5 text-slate-300/70">
                                {{ optional($ch->updated_at)->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('vod-channels.settings', $ch) }}"
                                       class="inline-flex items-center rounded-lg bg-blue-500/15 px-3 py-1.5 text-xs font-medium text-blue-200 ring-1 ring-inset ring-blue-400/25 hover:bg-blue-500/20 transition">
                                        Settings
                                    </a>

                                    <button type="button" disabled
                                       class="inline-flex items-center rounded-lg bg-amber-500/10 px-3 py-1.5 text-xs font-medium text-amber-200/60 ring-1 ring-inset ring-amber-400/15 opacity-60 cursor-not-allowed">
                                        Restart
                                    </button>

                                    <button type="button" disabled
                                       class="inline-flex items-center rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-200/60 ring-1 ring-inset ring-emerald-400/15 opacity-60 cursor-not-allowed">
                                        Toggle
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-slate-300/70">No channels found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Encoding Jobs (separate, full-width) --}}
<div class="mt-6 rounded-2xl border border-slate-500/20 bg-slate-900/40 p-5">
    <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-100">Encoding Jobs</div>
        <div class="text-xs text-slate-300/60">
            Queued: {{ $jobsStats['queued'] ?? 'N/A' }} • Running: {{ $jobsStats['running'] ?? 'N/A' }} • Failed: {{ $jobsStats['failed'] ?? 'N/A' }}
        </div>
    </div>

    <div class="mt-3 overflow-hidden rounded-xl border border-slate-500/15 shadow-[0_0_0_1px_rgba(255,255,255,0.02)]">
        <table class="w-full text-sm">
            <thead class="bg-slate-950/40 text-slate-300/80">
                <tr>
                    <th class="px-3 py-2 text-left">Job</th>
                    <th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Progress</th>
                    <th class="px-3 py-2 text-left">Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    @php
                        $st = $job->status ?? 'unknown';
                        $jb = match($st) {
                            'running' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                            'queued'  => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                            'failed'  => 'border-red-500/30 bg-red-500/10 text-red-300',
                            default   => 'border-slate-400/20 bg-slate-400/10 text-slate-200/80',
                        };
                    @endphp
                    <tr class="border-t border-slate-500/10 hover:bg-slate-800/40 transition">
                        <td class="px-3 py-2.5 text-slate-100">#{{ $job->id }}</td>
                        <td class="px-3 py-2.5">
                            <span class="text-[11px] font-medium tracking-wide leading-none rounded-full border px-2 py-1 {{ $jb }}">{{ strtoupper($st) }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-slate-200/90">
                            {{ is_null($job->progress) ? '—' : ($job->progress . '%') }}
                        </td>
                        <td class="px-3 py-2.5 text-slate-300/70">
                            {{ \Carbon\Carbon::parse($job->updated_at)->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-slate-300/70">
                            No encoding jobs table detected (MVP).
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

