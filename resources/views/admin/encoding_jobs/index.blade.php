@extends('layouts.panel')

@section('content')
@php
    $statusFilter = (string) ($statusFilter ?? '');

    $tabs = [
        ['label' => 'All', 'status' => ''],
        ['label' => 'Running', 'status' => 'running,processing'],
        ['label' => 'Queued', 'status' => 'queued,pending'],
        ['label' => 'Failed', 'status' => 'failed'],
    ];

    $badgeForStatus = function ($status) {
        $st = strtolower((string) $status);
        return match ($st) {
            'running', 'processing' => ['yellow', strtoupper($st)],
            'queued', 'pending'     => ['blue', strtoupper($st)],
            'failed'                => ['red', strtoupper($st)],
            'done', 'completed'     => ['green', strtoupper($st)],
            default                 => ['blue', strtoupper($st ?: 'UNKNOWN')],
        };
    };
@endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:22px;font-weight:900;color:var(--text-primary);">Encoding Jobs</h1>
        <div style="margin-top:6px;font-size:12px;color:var(--text-muted);">Queue and status overview for encoding tasks.</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        @foreach($tabs as $t)
            @php
                $isActive = ($t['status'] === '' && $statusFilter === '') || ($t['status'] !== '' && $t['status'] === $statusFilter);
                $url = $t['status'] === ''
                    ? route('encoding-jobs.index')
                    : route('encoding-jobs.index', ['status' => $t['status']]);
            @endphp
            <a href="{{ $url }}" class="fox-badge {{ $isActive ? 'green' : 'blue' }}" style="text-decoration:none;">{{ $t['label'] }}</a>
        @endforeach
    </div>
</div>

@if (session('success'))
    <div class="fox-table-container" style="padding:12px 14px;margin-bottom:12px;border-left:4px solid var(--fox-green);">
        <div style="font-weight:700;color:var(--text-primary);">{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="fox-table-container" style="padding:12px 14px;margin-bottom:12px;border-left:4px solid var(--fox-red);">
        <div style="font-weight:700;color:var(--text-primary);">{{ session('error') }}</div>
    </div>
@endif

<div class="fox-table-container">
    <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;font-weight:900;color:#666;letter-spacing:.04em;text-transform:uppercase;">Jobs</div>
        <div style="font-size:12px;color:#666;">{{ method_exists($jobs, 'total') ? $jobs->total() : count($jobs) }} total</div>
    </div>

    <div style="overflow:auto;">
        <table class="fox-table">
            <thead>
            <tr>
                <th style="width:70px;">ID</th>
                <th>Channel</th>
                <th>Video</th>
                <th style="width:140px;">Status</th>
                <th style="width:90px;">Progress</th>
                <th style="width:190px;">Created</th>
                <th style="width:190px;">Started</th>
                <th style="width:190px;">Finished</th>
                <th>Error</th>
            </tr>
            </thead>
            <tbody>
            @forelse($jobs as $job)
                @php
                    [$badgeColor, $badgeText] = $badgeForStatus($job->status);
                @endphp
                <tr>
                    <td style="font-variant-numeric:tabular-nums;">{{ $job->id }}</td>
                    <td>
                        @if ($job->channel)
                            <a href="{{ route('vod-channels.settings', $job->channel) }}" style="color:var(--fox-blue);text-decoration:none;font-weight:700;">[#{{ $job->channel->id }}] {{ $job->channel->name }}</a>
                        @else
                            <span style="color:#999;">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($job->video)
                            <span style="font-weight:600;">[#{{ $job->video->id }}] {{ $job->video->title }}</span>
                        @else
                            <span style="color:#999;">—</span>
                        @endif
                    </td>
                    <td><span class="fox-badge {{ $badgeColor }}">{{ $badgeText }}</span></td>
                    <td style="font-variant-numeric:tabular-nums;">{{ (int) ($job->progress ?? 0) }}%</td>
                    <td style="font-variant-numeric:tabular-nums;">{{ $job->created_at }}</td>
                    <td style="font-variant-numeric:tabular-nums;">{{ $job->started_at ?? '—' }}</td>
                    <td style="font-variant-numeric:tabular-nums;">{{ $job->finished_at ?? ($job->completed_at ?? '—') }}</td>
                    <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#b91c1c;">{{ $job->error_message ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="padding:18px;color:#999;text-align:center;">No encoding jobs yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($jobs, 'links'))
        <div style="padding:12px 16px;border-top:1px solid #f0f0f0;">
            {{ $jobs->links() }}
        </div>
    @endif
</div>
@endsection
