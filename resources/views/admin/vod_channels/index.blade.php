@extends('layouts.panel')

@section('content')

<style>
html, body { overflow-x: hidden; }
* { box-sizing: border-box; }

.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
.page-title { font-size: 28px; font-weight: 800; margin: 0; color: #1a1a1a; }
.server-highlight { color: #f1c40f; font-weight: 900; }
.page-subtitle { font-size: 13px; color: #6b7280; margin: 6px 0 0; }

.btn-new { background: #3b82f6; color: #fff; border: 0; padding: 10px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-new:hover { background: #2563eb; }

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 720px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }

.kpi-card { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.06); position: relative; }
.kpi-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: #2563eb; border-radius: 12px 0 0 12px; }
.kpi-title { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.4px; margin-bottom: 8px; }
.kpi-value { font-size: 24px; font-weight: 800; color: #1a1a1a; }

/* Card */
.card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); padding: 18px; margin-bottom: 16px; }

/* Server dropdown */
.server-bar { background: #fbf8e9; }
.field-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: rgba(0,0,0,.55); margin-bottom: 8px; letter-spacing: 0.4px; }
.input-field { width: 100%; padding: 10px 12px; border: 1px solid rgba(0,0,0,.15); border-radius: 8px; font-size: 14px; }
.input-field:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }

/* Toolbar */
.toolbar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.btn-toolbar { border: 0; padding: 10px 14px; border-radius: 8px; font-weight: 700; color: #fff; cursor: pointer; font-size: 14px; }
.btn-stop { background: #dc2626; }
.btn-start { background: #16a34a; }
.btn-epg { background: #f59e0b; }
.btn-fast { background: #d32f2f; }
.btn-msg { background: #2563eb; }

/* Warning */
.warning { background: #3f4a58; color: #fff; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; position: relative; }
.warning::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: #f1c40f; border-radius: 8px 0 0 8px; }
.warning-hl { color: #f1c40f; font-weight: 900; }

/* Table controls */
.table-controls { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 16px; }
.left-controls { display: flex; align-items: center; gap: 10px; }
.right-controls { display: flex; justify-content: flex-end; }
.muted { color: rgba(0,0,0,.5); font-size: 13px; }
.input-search { width: 280px; }

/* Table */
.table-fox { width: 100%; border-collapse: collapse; }
.table-fox thead { background: #f3f4f6; }
.table-fox th { font-weight: 700; text-transform: uppercase; font-size: 12px; padding: 14px 12px; text-align: left; letter-spacing: 0.4px; color: #1a1a1a; border-bottom: 1px solid #e5e7eb; }
.table-fox td { padding: 14px 12px; border-bottom: 1px solid #f3f4f6; }
.table-fox tbody tr:hover { background: #f9fafb; }

.name-cell { font-weight: 700; }

.pill { display: inline-flex; align-items: center; justify-content: center; height: 22px; padding: 0 8px; border-radius: 999px; font-weight: 700; font-size: 12px; border: 1px solid; }
.pill-blue { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.pill-pink { background: #fce7f3; color: #be185d; border-color: #fbcfe8; }
.pill-yellow { background: #fef3c7; color: #7c2d12; border-color: #fcd34d; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pill-gray { background: #f3f4f6; color: #374151; border-color: #d1d5db; }

.pill-row { display: flex; gap: 6px; }

.status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.dot-active { background: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.2); }
.dot-inactive { background: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.2); }

.epg-badge { display: inline-flex; align-items: center; justify-content: center; height: 22px; padding: 0 10px; border-radius: 999px; font-weight: 700; font-size: 12px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

.mono { font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums; }

/* Actions */
.actions-row { display: flex; gap: 8px; justify-content: flex-end; }
.btn-icon { width: 34px; height: 34px; border: 0; border-radius: 8px; color: #fff; cursor: pointer; font-weight: 700; }
.btn-icon-stop { background: #dc2626; }
.btn-icon-edit { background: #f59e0b; }
.btn-icon-del { background: #dc2626; }

/* Dropdown */
.dropdown { position: relative; display: inline-block; }
.dropdown-btn { background: #fff; border: 1px solid rgba(0,0,0,.15); border-radius: 8px; padding: 8px 12px; cursor: pointer; font-weight: 600; }
.dropdown-menu { position: absolute; right: 0; top: calc(100% + 6px); min-width: 200px; background: #fff; border: 1px solid rgba(0,0,0,.1); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,.15); display: none; z-index: 50; }
.dropdown-menu.active { display: block; }
.dropdown-menu button { width: 100%; text-align: left; padding: 10px 12px; border: 0; background: #fff; cursor: pointer; font-weight: 500; font-size: 14px; }
.dropdown-menu button:hover { background: #f3f4f6; }
</style>

<!-- PAGE HEADER -->
<div class="page-header">
  <div>
    <h1 class="page-title">
      Vod Channels <span class="server-highlight">[Server 1]</span>
    </h1>
    <p class="page-subtitle">Manage all your streaming channels</p>
  </div>
  <button class="btn-new" onclick="location.href='{{ route('vod-channels.create-new') }}'">+ New Channel</button>
</div>

<!-- KPI STATS (6 cards) -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-title">Total Channels</div>
    <div class="kpi-value">{{ $totalChannels ?? 0 }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Active Channels</div>
    <div class="kpi-value">{{ $enabledChannels ?? 0 }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Passive Channels</div>
    <div class="kpi-value">{{ ($totalChannels ?? 0) - ($enabledChannels ?? 0) }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Total Video</div>
    <div class="kpi-value">{{ $totalVideos ?? 0 }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Total Space</div>
    <div class="kpi-value">{{ $diskStats['total_gb'] ?? 0 }} TB</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Free Space</div>
    <div class="kpi-value">{{ $diskStats['free_gb'] ?? 0 }} TB</div>
  </div>
</div>

<!-- SERVER DROPDOWN -->
<div class="card server-bar">
  <label class="field-label" for="serverSelect">Server</label>
  <select id="serverSelect" class="input-field">
    <option value="1">Server 1</option>
  </select>
</div>

<!-- MAIN CARD -->
<div class="card">

  <!-- TOOLBAR -->
  <div class="toolbar">
    <button class="btn-toolbar btn-stop" onclick="handleAction('stop-all')">Stop</button>
    <button class="btn-toolbar btn-start" onclick="handleAction('start-all')">Start</button>
    <button class="btn-toolbar btn-epg" onclick="handleAction('channels-epg')">Channels Epg</button>
    <button class="btn-toolbar btn-fast" onclick="handleAction('fast-channel')">Fast Channel</button>
    <button class="btn-toolbar btn-msg" onclick="handleAction('send-message')">Send Message</button>
  </div>

  <!-- WARNING -->
  <div class="warning">
    <span class="warning-hl">Important warning !!!</span>
    You would create more than <span class="warning-hl">[50]</span> channels.
    There may be slowdowns in the panel, your server's hard drive may crash, please consider these.
    There is no channel creation limit.
  </div>

  <!-- TABLE CONTROLS -->
  <div class="table-controls">
    <div class="left-controls">
      <select id="pageSize" class="input-field" style="width: 80px;">
        <option>60</option>
        <option>30</option>
        <option>100</option>
      </select>
      <span class="muted">records per page</span>
    </div>
    <div class="right-controls">
      <input id="searchInput" class="input-field input-search" type="text" placeholder="Search..." />
    </div>
  </div>

  <!-- TABLE -->
  <div style="overflow-x: auto;">
    <table class="table-fox">
      <thead>
        <tr>
          <th>Name</th>
          <th>Transcoding</th>
          <th>Playing</th>
          <th>Bitrate</th>
          <th>Uptime</th>
          <th>Status</th>
          <th>Epg</th>
          <th>Size</th>
          <th>Total Time</th>
          <th>Events</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($channels ?? [] as $channel)
          @php
            $videos = $channel->playlistItems->map(fn($pi) => $pi->video)->filter();
            $totalDuration = $videos->sum(fn($v) => $v->duration_seconds ?? 0);
            $totalSize = $videos->sum(fn($v) => $v->size_bytes ?? 0);
            $avgBitrate = $videos->isNotEmpty() ? round($videos->avg('bitrate_kbps') ?? 0) : 0;

            $hours = intdiv($totalDuration, 3600);
            $minutes = intdiv($totalDuration % 3600, 60);
            $seconds = $totalDuration % 60;

            $daysActive = max(1, now()->diffInDays($channel->updated_at));

            if ($totalSize < 1024 * 1024) {
              $sizeStr = round($totalSize / 1024) . 'K';
            } elseif ($totalSize < 1024 * 1024 * 1024) {
              $sizeStr = round($totalSize / (1024 * 1024)) . 'M';
            } else {
              $sizeStr = round($totalSize / (1024 * 1024 * 1024), 1) . 'G';
            }
          @endphp

          <tr>
            <td class="name-cell">{{ $channel->name }}</td>
            <td>
              <div class="pill-row">
                <span class="pill pill-blue">{{ $videos->count() }}</span>
                <span class="pill pill-blue">{{ $videos->where('format', 'mp4')->count() }}</span>
                <span class="pill pill-pink">{{ $videos->where('format', 'mkv')->count() }}</span>
              </div>
            </td>
            <td><span class="pill pill-yellow" title="{{ $videos->first()?->title }}">{{ substr($videos->first()?->title ?? '-', 0, 20) }}</span></td>
            <td><span class="pill pill-gray">{{ $avgBitrate }}k</span></td>
            <td><span class="pill pill-gray">{{ $daysActive }}d {{ $hours }}h {{ $minutes }}m</span></td>
            <td><span class="status-dot {{ $channel->enabled ? 'dot-active' : 'dot-inactive' }}"></span></td>
            <td><span class="epg-badge">OPEN</span></td>
            <td class="mono">{{ $sizeStr }}</td>
            <td class="mono">{{ $hours }}h {{ $minutes }}m {{ $seconds }}s</td>
            <td>
              <div class="dropdown">
                <button class="dropdown-btn" onclick="toggleDropdown(this)">Actions ▼</button>
                <div class="dropdown-menu">
                  <button onclick="handleRowAction('create-video', {{ $channel->id }})">Create Video</button>
                  <button onclick="handleRowAction('edit-playlist', {{ $channel->id }})">Edit Playlist ({{ $videos->count() }})</button>
                  <button onclick="handleRowAction('edit-video-epg', {{ $channel->id }})">Edit Video Epg</button>
                  <button onclick="handleRowAction('epg-link', {{ $channel->id }})">Channel Epg Link</button>
                  <button onclick="handleRowAction('converted-videos', {{ $channel->id }})">Converted Videos</button>
                  <button onclick="handleRowAction('send-message', {{ $channel->id }})">Send Message</button>
                  <button onclick="handleRowAction('error-videos', {{ $channel->id }})">Error Videos</button>
                </div>
              </div>
            </td>
            <td>
              <div class="actions-row">
                @if($channel->enabled)
                  <button class="btn-icon btn-icon-stop" onclick="handleRowAction('stop', {{ $channel->id }})" title="Stop">⏹</button>
                @endif
                <button class="btn-icon btn-icon-edit" onclick="handleRowAction('edit', {{ $channel->id }})" title="Edit">✎</button>
                <button class="btn-icon btn-icon-del" onclick="handleRowAction('delete', {{ $channel->id }})" title="Delete">✕</button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="11" style="text-align: center; padding: 32px; color: #6b7280;">
              No channels found
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>

<script>
function toggleDropdown(btn) {
  const menu = btn.nextElementSibling;
  document.querySelectorAll('.dropdown-menu').forEach(m => {
    if (m !== menu) m.classList.remove('active');
  });
  menu.classList.toggle('active');
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.dropdown')) {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
  }
});

function handleAction(action) {
  switch(action) {
    case 'stop-all':
      // TODO: POST /vod-channels/{server}/stop
      console.log('Stop all');
      break;
    case 'start-all':
      // TODO: POST /vod-channels/{server}/start
      console.log('Start all');
      break;
    case 'channels-epg':
      // TODO: GET /vod-channels/{server}/epg
      break;
    case 'fast-channel':
      // TODO: GET /vod-channels/{server}/fast
      break;
    case 'send-message':
      // TODO: POST /vod-channels/{server}/message
      break;
  }
}

function handleRowAction(action, id) {
  switch(action) {
    case 'stop':
      // TODO: POST /vod-channels/{id}/stop
      break;
    case 'edit':
      // TODO: GET /vod-channels/{id}/edit
      break;
    case 'delete':
      if (confirm('Delete this channel?')) {
        // TODO: DELETE /vod-channels/{id}
      }
      break;
    case 'create-video':
      // TODO: GET /create-video/{id}
      break;
    case 'edit-playlist':
      // TODO: GET /vod-channels/{id}/playlist
      break;
    case 'edit-video-epg':
      // TODO: GET /vod-channels/{id}/video-epg
      break;
    case 'epg-link':
      // TODO: GET /api/vod-channels/{id}/epg-link
      break;
    case 'converted-videos':
      // TODO: GET /vod-channels/{id}/converted
      break;
    case 'send-message':
      // TODO: POST /vod-channels/{id}/message
      break;
    case 'error-videos':
      // TODO: GET /vod-channels/{id}/errors
      break;
  }
}
</script>

@endsection
