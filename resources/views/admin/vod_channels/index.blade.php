@extends('layouts.panel')

@section('full_width', true)

@section('content')

<style>
/* Clean modern styles */
* { box-sizing: border-box; }

.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.page-title { font-size: 28px; font-weight: 800; margin: 0; color: #111827; }
.server-highlight { color: #dc2626; font-weight: 900; }

.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: 0; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(102,126,234,0.3); transition: transform 0.2s; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102,126,234,0.4); }

/* KPI Grid - 2 rows x 3 cols */
.kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
.kpi-card { background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%); border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.04); border: 1px solid #e5e7eb; position: relative; overflow: hidden; }
.kpi-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); }
.kpi-title { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.8px; margin-bottom: 10px; padding-left: 12px; }
.kpi-value { font-size: 32px; font-weight: 900; color: #111827; padding-left: 12px; }

/* Table card */
.table-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); padding: 24px; overflow: hidden; }

/* Table controls */
.table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
.input-field { padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; outline: none; transition: all 0.2s; }
.input-field:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.1); }
.input-search { width: 300px; }

/* Table */
.table-wrapper { overflow-x: auto; border-radius: 8px; border: 1px solid #e5e7eb; }
.table-fox { width: 100%; border-collapse: collapse; min-width: 1200px; }
.table-fox thead { background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%); }
.table-fox th { font-weight: 700; text-transform: uppercase; font-size: 11px; padding: 16px 12px; text-align: left; letter-spacing: 0.6px; color: #374151; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
.table-fox td { padding: 14px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.table-fox tbody tr { transition: background 0.15s; }
.table-fox tbody tr:hover { background: #f9fafb; }

.channel-name-wrap { display: flex; align-items: center; gap: 12px; min-width: 200px; }
.channel-logo-box { width: 64px; height: 36px; border-radius: 6px; border: 1px solid #e5e7eb; background: #f9fafb; flex: 0 0 auto; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.channel-logo-box img { width: 100%; height: 100%; object-fit: contain; }
.channel-name { font-weight: 600; color: #111827; font-size: 14px; }

.pill { display: inline-flex; align-items: center; justify-content: center; height: 24px; padding: 0 10px; border-radius: 6px; font-weight: 600; font-size: 12px; white-space: nowrap; }
.pill-blue { background: #dbeafe; color: #1e40af; }
.pill-pink { background: #fce7f3; color: #be185d; }
.pill-yellow { background: #fef3c7; color: #92400e; max-width: 140px; overflow: hidden; text-overflow: ellipsis; }
.pill-gray { background: #f3f4f6; color: #374151; }
.pill-green { background: #d1fae5; color: #065f46; }

.pill-row { display: flex; gap: 6px; flex-wrap: wrap; }

.status-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
.dot-active { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
.dot-inactive { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.2); }

.mono { font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums; color: #6b7280; font-size: 13px; }

/* Action buttons */
.actions-row { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
.action-btn { width: 34px; height: 34px; border: 0; border-radius: 8px; cursor: pointer; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; font-weight: 600; }
.action-btn:hover { transform: translateY(-2px); }
.btn-start { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: #fff; box-shadow: 0 2px 8px rgba(17,153,142,0.3); }
.btn-start:hover { box-shadow: 0 4px 12px rgba(17,153,142,0.4); }
.btn-stop { background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%); color: #fff; box-shadow: 0 2px 8px rgba(252,74,26,0.3); }
.btn-stop:hover { box-shadow: 0 4px 12px rgba(252,74,26,0.4); }
.btn-edit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; box-shadow: 0 2px 8px rgba(102,126,234,0.3); }
.btn-edit:hover { box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
.btn-settings { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff; box-shadow: 0 2px 8px rgba(240,147,251,0.3); }
.btn-settings:hover { box-shadow: 0 4px 12px rgba(240,147,251,0.4); }
.btn-delete { background: linear-gradient(135deg, #434343 0%, #000000 100%); color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
.btn-delete:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.4); }

/* Dropdown */
.fox-dropdown { position: relative; display: inline-block; }
.fox-dropdown-btn { background: #f3f4f6; border: 1px solid #e5e7eb; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; color: #374151; transition: all 0.2s; }
.fox-dropdown-btn:hover { background: #e5e7eb; }
.fox-dropdown-menu { position: absolute; top: 100%; right: 0; margin-top: 6px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.12); min-width: 200px; z-index: 100; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s; }
.fox-dropdown-menu.is-open { opacity: 1; visibility: visible; transform: translateY(0); }
.dropdown-item-btn { width: 100%; text-align: left; padding: 12px 16px; border: 0; background: #fff; cursor: pointer; font-weight: 500; font-size: 13px; color: #374151; border-bottom: 1px solid #f3f4f6; transition: all 0.15s; }
.dropdown-item-btn:first-child { border-radius: 10px 10px 0 0; }
.dropdown-item-btn:last-child { border-bottom: 0; border-radius: 0 0 10px 10px; }
.dropdown-item-btn:hover { background: #f9fafb; color: #667eea; padding-left: 20px; }
</style>

<div style="padding: 28px; background: #f9fafb; min-height: 100vh;">

<!-- PAGE HEADER -->
<div class="page-header">
  <div>
    <h1 class="page-title">VOD Channels <span class="server-highlight">[Server 1]</span></h1>
    <p style="font-size: 14px; color: #6b7280; margin: 6px 0 0;">Manage all your streaming channels</p>
  </div>
  <button class="btn-primary" onclick="location.href='{{ route('vod-channels.create-new') }}'">+ New Channel</button>
</div>

<!-- KPI STATS -->
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-title">📺 Total Channels</div>
    <div class="kpi-value">{{ $totalChannels ?? 0 }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">✅ Active Channels</div>
    <div class="kpi-value">{{ $enabledChannels ?? 0 }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">⏸ Passive Channels</div>
    <div class="kpi-value">{{ ($totalChannels ?? 0) - ($enabledChannels ?? 0) }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">🎬 Total Video</div>
    <div class="kpi-value">{{ $totalVideos ?? 0 }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">💾 Total Space</div>
    <div class="kpi-value">{{ $diskStats['total_gb'] ?? 0 }} TB</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">📊 Free Space</div>
    <div class="kpi-value">{{ $diskStats['free_gb'] ?? 0 }} TB</div>
  </div>
</div>

<!-- MAIN TABLE CARD -->
<div class="table-card">

  <!-- TABLE CONTROLS -->
  <div class="table-controls">
    <div style="display: flex; align-items: center; gap: 10px;">
      <select id="pageSize" class="input-field" style="width: 100px;">
        <option>60</option>
        <option>30</option>
        <option>100</option>
      </select>
      <span style="color: #6b7280; font-size: 13px;">records per page</span>
    </div>
    <div>
      <input id="searchInput" class="input-field input-search" type="text" placeholder="🔍 Search channels..." />
    </div>
  </div>

  <!-- TABLE -->
  <div class="table-wrapper">
    <table class="table-fox">
      <thead>
        <tr>
          <th>Name</th>
          <th>Transcoding</th>
          <th>Playing</th>
          <th>Bitrate</th>
          <th>Speed</th>
          <th>Redis</th>
          <th>Uptime</th>
          <th>Status</th>
          <th>Epg</th>
          <th>Size</th>
          <th>Total Time</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($channels ?? [] as $channel)
          @php
            $videos = $channel->playlistItems->map(fn($pi) => $pi->video)->filter();
            $totalDuration = $videos->sum(fn($v) => $v->duration_seconds ?? 0);
            $totalSize = $videos->sum(fn($v) => $v->size_bytes ?? 0);
            $targetBitrateK = (int) (
              $channel->encodeProfile?->video_bitrate_k
              ?? $channel->video_bitrate
              ?? 0
            );

            $hours = intdiv($totalDuration, 3600);
            $minutes = intdiv($totalDuration % 3600, 60);
            $seconds = $totalDuration % 60;

            $pid = (int) ($channel->encoder_pid ?? 0);
            $isRunning = $pid > 0 && is_dir('/proc/' . $pid);
            $uptimeStr = '—';
            if ($isRunning && !empty($channel->started_at)) {
              $elapsed = max(0, \Carbon\Carbon::parse($channel->started_at)->diffInSeconds(now()));
              $d = intdiv($elapsed, 86400);
              $h = intdiv($elapsed % 86400, 3600);
              $m = intdiv($elapsed % 3600, 60);
              $s = $elapsed % 60;
              $uptimeStr = ($d > 0 ? ($d . 'd ') : '') . ($h > 0 ? ($h . 'h ') : '') . $m . 'm ' . $s . 's';
            }

            if ($totalSize < 1024 * 1024) {
              $sizeStr = round($totalSize / 1024) . 'K';
            } elseif ($totalSize < 1024 * 1024 * 1024) {
              $sizeStr = round($totalSize / (1024 * 1024)) . 'M';
            } else {
              $sizeStr = round($totalSize / (1024 * 1024 * 1024), 1) . 'G';
            }
          @endphp

          <tr>
            <td>
              <div class="channel-name-wrap">
                <span class="channel-logo-box">
                  @if(!empty($channel->logo_path))
                    <img src="{{ route('vod-channels.logo.preview', $channel) }}?v={{ urlencode((string) optional($channel->updated_at)->timestamp) }}" alt="" loading="lazy" onerror="this.style.visibility='hidden'" />
                  @endif
                </span>
                <span class="channel-name">{{ $channel->name }}</span>
              </div>
            </td>
            <td>
              <div class="pill-row">
                <span class="pill pill-blue" title="Total videos">{{ $videos->count() }}</span>
                <span class="pill pill-blue" title="MP4 videos">{{ $videos->where('format', 'mp4')->count() }}</span>
                <span class="pill pill-pink" title="MKV videos">{{ $videos->where('format', 'mkv')->count() }}</span>
              </div>
            </td>
            <td><span class="pill pill-yellow" title="{{ $videos->first()?->title ?? 'No video' }}">{{ substr($videos->first()?->title ?? '-', 0, 20) }}</span></td>
            <td><span class="pill pill-gray">{{ $targetBitrateK }}k</span></td>
            <td><span class="pill pill-gray">{{ $channel->runtime_speed ?? '—' }}</span></td>
            <td><span class="pill pill-gray">{{ $channel->runtime_redis ?? '—' }}</span></td>
            <td class="mono">{{ $uptimeStr }}</td>
            <td style="text-align: center;"><span class="status-dot {{ $isRunning ? 'dot-active' : 'dot-inactive' }}"></span></td>
            <td><span class="pill pill-green">EPG</span></td>
            <td class="mono">{{ $sizeStr }}</td>
            <td class="mono">{{ $hours }}h {{ $minutes }}m</td>
            <td>
              <div class="actions-row">
                <button type="button" class="action-btn btn-start" onclick="handleRowAction('start', {{ $channel->id }})" title="Start channel">▶</button>
                <button type="button" class="action-btn btn-stop" onclick="handleRowAction('stop', {{ $channel->id }})" title="Stop channel">■</button>
                <button type="button" class="action-btn btn-edit" onclick="handleRowAction('edit-playlist', {{ $channel->id }})" title="Playlist">📋</button>
                <button type="button" class="action-btn btn-settings" onclick="handleRowAction('encoding', {{ $channel->id }})" title="Encoding">⚙</button>
                <button type="button" class="action-btn btn-edit" onclick="handleRowAction('edit', {{ $channel->id }})" title="Settings">⚡</button>
                <button type="button" class="action-btn btn-delete" onclick="handleRowAction('delete', {{ $channel->id }})" title="Delete channel">✕</button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="12" style="text-align: center; padding: 32px; color: #6b7280;">
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
  document.querySelectorAll('.fox-dropdown-menu').forEach(m => {
    if (m !== menu) m.classList.remove('is-open');
  });
  menu.classList.toggle('is-open');
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.fox-dropdown')) {
    document.querySelectorAll('.fox-dropdown-menu').forEach(m => m.classList.remove('is-open'));
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
    case 'start':
      if (!confirm('Start this channel?')) return;
      (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(@json(url('/vod-channels')) + '/' + id + '/engine/start', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
        })
          .then(async (r) => {
            if (r.ok) return { ok: true };
            let payload = null;
            try { payload = await r.json(); } catch (e) {}
            return { ok: false, status: r.status, message: payload?.message };
          })
          .then((res) => {
            if (res.ok) {
              window.location.reload();
              return;
            }
            alert('❌ Start failed' + (res.message ? (': ' + res.message) : ''));
          })
          .catch((e) => {
            alert('❌ Network error: ' + (e?.message || e));
          });
      })();
      break;
    case 'stop':
      if (!confirm('Stop this channel?')) return;
      (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(@json(url('/vod-channels')) + '/' + id + '/engine/stop', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
        })
          .then(async (r) => {
            if (r.ok) return { ok: true };
            let payload = null;
            try { payload = await r.json(); } catch (e) {}
            return { ok: false, status: r.status, message: payload?.message };
          })
          .then((res) => {
            if (res.ok) {
              window.location.reload();
              return;
            }
            alert('❌ Stop failed' + (res.message ? (': ' + res.message) : ''));
          })
          .catch((e) => {
            alert('❌ Network error: ' + (e?.message || e));
          });
      })();
      break;
    case 'edit':
      window.location = @json(url('/vod-channels')) + '/' + id + '/settings';
      break;
    case 'encoding':
      window.location = @json(url('/vod-channels')) + '/' + id + '/encoding';
      break;
    case 'delete':
      if (confirm('Delete this channel?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(@json(url('/vod-channels')) + '/' + id, {
          method: 'DELETE',
          credentials: 'include',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
        })
          .then(async (r) => {
            if (r.ok) return { ok: true };
            let payload = null;
            try { payload = await r.json(); } catch (e) {}
            return { ok: false, status: r.status, message: payload?.message };
          })
          .then((res) => {
            if (res.ok) {
              window.location.reload();
              return;
            }
            alert('❌ Delete failed' + (res.message ? (': ' + res.message) : ''));
          })
          .catch((e) => {
            alert('❌ Network error: ' + (e?.message || e));
          });
      }
      break;
    case 'create-video':
      // TODO: GET /create-video/{id}
      break;
    case 'edit-playlist':
      window.location = @json(url('/vod-channels')) + '/' + id + '/playlist';
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

</div>

@endsection
