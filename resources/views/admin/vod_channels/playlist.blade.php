@extends('layouts.panel')

@section('content')
<style>
  /* Alias variables used by this view to the enterprise theme tokens.
     Fixes invisible buttons when custom vars are undefined. */
  :root {
    --card-bg: var(--bg-secondary);
    --border-color: var(--border-default);
    --text-muted: var(--text-secondary);

    --fox-blue: var(--color-info);
    --fox-green: var(--color-success);
    --fox-red: var(--color-error);
    --btn-start: var(--color-success);
    --btn-stop: var(--color-error);
  }

  .page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px; }
  .page-title { font-size:22px; font-weight:900; color:var(--text-primary); margin:0; }
  .page-subtitle { font-size:12px; color:var(--text-muted); margin-top:6px; }

  .flash { border:1px solid var(--border-color); background:var(--card-bg); border-radius:6px; padding:12px 14px; box-shadow:var(--shadow-sm); margin:12px 0 16px; }
  .flash.success { border-left:4px solid var(--fox-green); }
  .flash.error { border-left:4px solid var(--fox-red); }

  .card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:6px; box-shadow:var(--shadow-sm); overflow:hidden; }
  .card-h { padding:12px 14px; border-bottom:1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap:12px; }
  .card-t { font-size:13px; font-weight:900; color:var(--text-primary); }
  .card-b { padding:14px; }

  .top-grid { display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin: 0 0 14px; }
  @media (max-width: 980px) { .top-grid { grid-template-columns: 1fr; } }

  .btn-row { display:flex; gap:10px; flex-wrap:wrap; }
  .btn { padding:10px 12px; border-radius:6px; color:#fff; font-weight:900; font-size:12px; border:0; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
  .btn-blue { background:var(--fox-blue); }
  .btn-green { background:var(--btn-start); }
  .btn-red { background:var(--btn-stop); }
  .btn-dark { background:var(--text-primary); }
  .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); }
  .btn:disabled { opacity:.5; cursor:not-allowed; }

  .btn-col { display:flex; flex-direction:row; flex-wrap:wrap; gap:8px; align-items:center; }
  .btn-sm { padding:8px 10px; font-size:11px; border-radius:6px; }
  .cell-actions { width: 320px; }

  .bottom-bar {
    position: sticky;
    bottom: 0;
    z-index: 10;
    padding: 12px 0 6px;
    background: linear-gradient(to top, rgba(0,0,0,.06), rgba(0,0,0,0));
  }
  .bottom-actions {
    display:flex;
    gap:10px;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
  }
  .bottom-actions .btn {
    flex: 1 1 220px;
    padding: 12px 16px;
    font-size: 12px;
    border-radius: 999px;
  }
  .total-pill {
    margin-top: 10px;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
    border-radius: 999px;
    padding: 14px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 12px;
    box-shadow: var(--shadow-sm);
  }
  .total-pill .label { font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .5px; font-weight: 900; }
  .total-pill .value { font-size: 22px; font-weight: 900; color: var(--text-primary); }

  .player { width:100%; max-height:70vh; border-radius:6px; border:1px solid var(--border-light); background:var(--card-bg); }
  .np { font-size:12px; color:var(--text-muted); font-weight:800; }

  .table-wrap { overflow:auto; }
  table { width:100%; border-collapse:separate; border-spacing:0; }
  thead th { position:sticky; top:0; z-index:2; background:var(--card-bg); font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.4px; text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-light); }
  tbody td { padding:10px 12px; border-bottom:1px solid var(--border-light); font-size:12px; color:var(--text-primary); vertical-align:middle; }
  tbody tr:hover { background:rgba(255,255,255,.03); }

  .muted { color:var(--text-muted); }
  .title-cell { font-weight:900; }
  .title-wrap { display:flex; align-items:center; gap:10px; }
  .poster { width:36px; height:54px; border-radius:6px; border:1px solid var(--border-light); object-fit:cover; flex:0 0 auto; }
  .status-ok { font-weight:900; color:var(--fox-green); }
</style>

<div class="page-header">
  <div>
    <h1 class="page-title">Playlist (Encoded Only) — {{ $channel->name }}</h1>
    <div class="page-subtitle">Shows TS-ready items for quick playback. If items are not encoded yet, they appear below.</div>

    <div class="btn-row" style="margin-top:10px; justify-content:flex-start;">
      <form method="POST" action="{{ route('vod-channels.engine.start-looping', $channel) }}" style="margin:0;">
        @csrf
        <button class="btn btn-green" type="submit" onclick="return confirm('Start channel (24/7 loop)?');">Start</button>
      </form>

      <form method="POST" action="{{ route('vod-channels.engine.stop', $channel) }}" style="margin:0;" onsubmit="return confirm('Stop channel?');">
        @csrf
        <button class="btn btn-red" type="submit">Stop</button>
      </form>

      <form method="POST" action="{{ route('vod-channels.engine.start-encoding', $channel) }}" style="margin:0;" onsubmit="return confirm('Queue TS encoding for all playlist videos?');">
        @csrf
        <button class="btn btn-blue" type="submit">Start Encoding TS</button>
      </form>
    </div>
  </div>
  <div class="btn-row">
    <a class="btn btn-blue" href="{{ route('vod-channels.settings', $channel) }}">Settings</a>
    <a class="btn btn-blue" href="{{ route('vod-channels.index') }}">Back</a>
  </div>
</div>

@if(session('success'))
  <div class="flash success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="flash error">{{ session('error') }}</div>
@endif

<div class="top-grid">
  <div class="card">
    <div class="card-h">
      <div class="card-t">Now Playing</div>
      <div class="muted" style="font-size:12px; font-weight:900;" id="jsNpStatus">—</div>
    </div>
    <div class="card-b" style="display:grid; gap:10px;">
      <div class="np" id="totalTime" style="margin: 0;">Total Time: —</div>
      <div class="muted"><span style="font-weight:900;">Now:</span> <span id="jsNpTitle">—</span> <span class="muted" id="jsNpIndex"></span></div>
      <div class="muted"><span style="font-weight:900;">Remaining:</span> <span id="jsNpRemain">—</span></div>
      <div class="muted"><span style="font-weight:900;">Next:</span> <span id="jsNpNext">—</span></div>
    </div>
  </div>

<?php
  $domain = rtrim((string) config('app.streaming_domain', ''), '/');
  $domain = ($domain === '' || str_contains($domain, 'localhost'))
    ? rtrim((string) request()->getSchemeAndHttpHost(), '/')
    : $domain;

  $hlsUrl = $domain . "/streams/{$channel->id}/hls/stream.m3u8";
  // Compatibility URL (redirects to HLS for TV-like playback)
  $tsUrlLive = $domain . "/streams/{$channel->id}/stream.ts";
  $hlsAliasUrl = $domain . "/streams/{$channel->id}/stream.m3u8";
  $masterUrl = $domain . "/streams/all.m3u8";
  $epgUrl = $domain . "/epg/all.xml";

  $vodDisplayTitle = function ($video): string {
    if (!$video) return 'Unknown';

    $title = trim((string)($video->title ?? ''));
    $filePath = trim((string)($video->file_path ?? ''));

    $isNumericTitle = ($title !== '' && preg_match('/^\d+$/', $title) === 1);

    $fromPath = '';
    if ($filePath !== '') {
      $base = (string) pathinfo($filePath, PATHINFO_FILENAME);
      $parent = (string) basename((string) dirname($filePath));
      $base = trim($base);
      $parent = trim($parent);

      if ($base !== '') {
        $fromPath = $base;
        if ($parent !== '' && $parent !== '.' && $parent !== '/' && $parent !== $base) {
          if (preg_match('/^\d+$/', $base) === 1 || preg_match('/^\d+$/', $title) === 1 || $title === '') {
            $fromPath = $parent . ' / ' . $base;
          }
        }
      }
    }

    if ($title !== '' && !$isNumericTitle) {
      return $title;
    }

    if ($fromPath !== '') {
      return $fromPath;
    }

    return $title !== '' ? $title : ('Video #' . (int)($video->id ?? 0));
  };
?>

  <div class="card">
    <div class="card-h">
      <div class="card-t">Stream URLs</div>
    </div>
    <div class="card-b" style="display:grid; gap:10px;">
      <div class="muted" style="font-weight:900;">HLS (M3U8)</div>
      <a class="muted" href="{{ $hlsUrl }}" target="_blank" rel="noopener">{{ $hlsUrl }}</a>

      <div class="muted" style="font-weight:900;">HLS Alias (M3U8)</div>
      <a class="muted" href="{{ $hlsAliasUrl }}" target="_blank" rel="noopener">{{ $hlsAliasUrl }}</a>

      <div class="muted" style="font-weight:900;">TS (MPEG-TS)</div>
      <a class="muted" href="{{ $tsUrlLive }}" target="_blank" rel="noopener">{{ $tsUrlLive }}</a>

      <div class="muted" style="font-weight:900;">Master Playlist (All Channels)</div>
      <a class="muted" href="{{ $masterUrl }}" target="_blank" rel="noopener">{{ $masterUrl }}</a>

      <div class="muted" style="font-weight:900;">EPG (XMLTV, 7 days)</div>
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <a class="muted" href="{{ $epgUrl }}" target="_blank" rel="noopener">{{ $epgUrl }}</a>
        <a class="btn btn-outline btn-sm" href="{{ $epgUrl }}" target="_blank" rel="noopener">Export EPG (All)</a>
      </div>

      <div class="muted">Note: Streams are available only when the channel is running. Pentru comportament "TV" (toți văd același lucru), folosește link-ul HLS.</div>
    </div>
  </div>
</div>

<?php
  $pendingItems = isset($pendingItems) ? $pendingItems : collect();
  $pendingCount = is_countable($pendingItems) ? count($pendingItems) : 0;
  $activeJobsCount = isset($jobCounts)
    ? ((int)($jobCounts['running'] ?? 0) + (int)($jobCounts['queued'] ?? 0))
    : 0;
?>

@if($pendingCount > 0)
  <div class="card" style="margin-bottom: 14px;">
    <div class="card-h">
      <div class="card-t">Encoding Queue (Not Encoded Yet)</div>
      <div class="muted" style="font-size:12px; font-weight:900;">
        {{ $pendingCount }} item(s)
        @if(isset($jobCounts))
          · Jobs: <span id="jsJobsRunning">{{ (int)($jobCounts['running'] ?? 0) }}</span> running, <span id="jsJobsQueued">{{ (int)($jobCounts['queued'] ?? 0) }}</span> queued
        @endif
      </div>
    </div>
    <div class="card-b table-wrap">
      <form method="POST" action="{{ route('vod-channels.engine.encoding-queue.remove-bulk', $channel) }}" id="encQueueBulkRemoveForm" style="margin:0;">
        @csrf
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:10px; margin: 0 0 10px 0;">
          <button class="btn btn-red btn-sm" type="submit" onclick="return confirm('Remove selected item(s) from encoding queue?');">Delete selected</button>
        </div>
      <table>
        <thead>
          <tr>
            <th style="width:44px; text-align:center;"><input type="checkbox" id="encQueueSelectAll" title="Select all"></th>
            <th style="width:70px;">#</th>
            <th>Title</th>
            <th style="width:140px;">Status</th>
            <th style="width:120px;">Progress</th>
            <th style="width:120px;">ETA</th>
            <th style="width:180px;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pendingItems as $index => $item)
            <?php
              $video = $item->video;
              $displayTitle = $vodDisplayTitle($video);
              $posterUrl = ($video && !empty($video->tmdb_poster_path)) ? ('https://image.tmdb.org/t/p/w92' . $video->tmdb_poster_path) : '';
              $job = null;
            ?>
            @if(isset($jobByPlaylistItemId))
              <?php $job = $jobByPlaylistItemId->get($item->id) ?? null; ?>
            @endif
            @if(!$job && $video && isset($jobByVideoId))
              <?php $job = $jobByVideoId->get($video->id) ?? null; ?>
            @endif
            <?php
              $rawJobStatus = $job ? strtoupper((string)($job->status ?? '')) : 'NOT STARTED';
              $jobStatus = ($job && $rawJobStatus === 'QUEUED' && isset($job->display_queue_position) && (int)$job->display_queue_position > 0)
                ? ('QUEUED #' . (int)$job->display_queue_position)
                : $rawJobStatus;
              $progress = $job ? (int)($job->display_progress ?? $job->progress ?? 0) : 0;
              $outTime = $job ? (string)($job->display_out_time ?? '') : '';
              $speed = $job ? (string)($job->display_speed ?? '') : '';
              $eta = $job ? (string)($job->display_eta ?? '') : '';
            ?>
            <tr class="js-enc-row" data-playlist-item-id="{{ (int) $item->id }}" data-video-id="{{ (int)($video?->id ?? 0) }}">
              <td style="text-align:center;"><input type="checkbox" class="js-enc-queue-pick" name="playlist_item_ids[]" value="{{ (int) $item->id }}"></td>
              <td class="muted">{{ $index + 1 }}</td>
              <td class="title-cell">
                <div class="title-wrap">
                  @if($posterUrl !== '')
                    <img class="poster" src="{{ $posterUrl }}" alt="">
                  @endif
                  <div>
                    <div>{{ $displayTitle }}</div>
                    @if($video && !empty($video->tmdb_genres))
                      <div class="muted" style="font-size:11px; font-weight:800; margin-top:2px;">{{ $video->tmdb_genres }}</div>
                    @endif
                  </div>
                </div>
              </td>
              <td class="muted js-enc-status">{{ $jobStatus }}</td>
              <td class="muted">
                @if(!$job)
                  —
                @elseif(str_starts_with($jobStatus, 'QUEUED') || $jobStatus === 'NOT STARTED')
                  —
                @elseif($rawJobStatus === 'RUNNING')
                  {{ $progress }}%
                  @if($outTime !== '')
                    <span class="muted">({{ $outTime }}@if($speed !== '') · {{ $speed }}@endif)</span>
                  @elseif($speed !== '')
                    <span class="muted">({{ $speed }})</span>
                  @endif
                @else
                  {{ $progress }}%
                @endif
              </td>
              <td class="muted js-enc-eta">
                @if(!$job)
                  —
                @elseif($rawJobStatus === 'RUNNING' && $eta !== '')
                  {{ $eta }}
                @else
                  —
                @endif
              </td>
              <td>
                <div style="display:flex; gap:8px; justify-content:flex-end;">
                  <a class="btn btn-blue btn-sm" href="{{ route('vod-channels.settings', $channel) }}">Open Settings</a>
                  @if($rawJobStatus !== 'DONE')
                    <form method="POST" action="{{ route('vod-channels.engine.encoding-queue.remove', [$channel, $item]) }}" style="margin:0;" onsubmit="return confirm('{{ $rawJobStatus === 'RUNNING' ? 'Stop this running encoding job and remove from queue?' : 'Remove this item from encoding queue?' }}');">
                      @csrf
                      <button class="btn btn-red btn-sm" type="submit">{{ $rawJobStatus === 'RUNNING' ? 'Stop' : 'Delete' }}</button>
                    </form>
                  @else
                    <button class="btn btn-red btn-sm" type="button" disabled>Delete</button>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      </form>
      <div class="muted" style="margin-top:10px; font-size:12px;">Tip: start encoding from Settings. This page shows only TS-ready items in the Encoded Items table.</div>
    </div>
  </div>
@endif

<script>
  (function () {
    const selectAll = document.getElementById('encQueueSelectAll');
    if (!selectAll) return;
    selectAll.addEventListener('change', function () {
      const checked = !!selectAll.checked;
      document.querySelectorAll('.js-enc-queue-pick').forEach(cb => { cb.checked = checked; });
    });
  })();
</script>

<script>
  (function () {
    const channelId = {{ (int) $channel->id }};
    const statusEls = () => Array.from(document.querySelectorAll('tr.js-enc-row'));

    const runningEl = document.getElementById('jsJobsRunning');
    const queuedEl = document.getElementById('jsJobsQueued');

    let lastSignature = null;
    let lastCompleted = null;

    function formatProgress(job) {
      if (!job) return '—';
      const st = String(job.status || '').toUpperCase();
      if (st === 'QUEUED' || st === 'NOT STARTED') return '—';
      const pct = (typeof job.progress === 'number') ? job.progress : Number(job.progress || 0);
      if (st === 'RUNNING') {
        const outTime = job.out_time ? String(job.out_time) : '';
        const speed = job.speed ? String(job.speed) : '';
        if (outTime) return `${pct}% (${outTime}${speed ? ' · ' + speed : ''})`;
        if (speed) return `${pct}% (${speed})`;
        return `${pct}%`;
      }
      return `${pct}%`;
    }

    function formatStatus(job) {
      if (!job) return 'NOT STARTED';
      const st = String(job.status || '').toUpperCase();
      if (st === 'QUEUED' && Number(job.queued_position || 0) > 0) {
        return `QUEUED #${Number(job.queued_position)}`;
      }
      return st || 'NOT STARTED';
    }

    function formatEta(job) {
      if (!job) return '—';
      const st = String(job.status || '').toUpperCase();
      if (st !== 'RUNNING') return '—';
      const eta = job.eta ? String(job.eta) : '';
      return eta || '—';
    }

    async function poll() {
      const res = await fetch(`/vod-channels/${channelId}/engine/encoding-jobs`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || json.status !== 'success') return;

      if (runningEl && typeof json.running_jobs === 'number') runningEl.textContent = String(json.running_jobs);
      // queued jobs count isn't provided; approximate from jobs list
      const jobs = Array.isArray(json.jobs) ? json.jobs : [];
      if (queuedEl) {
        const q = jobs.filter(j => String(j.status || '').toLowerCase() === 'queued').length;
        queuedEl.textContent = String(q);
      }

      const mapByPlaylistItemId = new Map();
      const mapByVideoId = new Map();
      for (const j of jobs) {
        const pid = Number(j.playlist_item_id || 0);
        const vid = Number(j.video_id || 0);
        if (pid > 0) mapByPlaylistItemId.set(pid, j);
        if (vid > 0) {
          const prev = mapByVideoId.get(vid);
          if (!prev || Number(j.id) > Number(prev.id)) mapByVideoId.set(vid, j);
        }
      }

      for (const row of statusEls()) {
        const pid = Number(row.getAttribute('data-playlist-item-id') || 0);
        const vid = Number(row.getAttribute('data-video-id') || 0);
        const job = (pid > 0 && mapByPlaylistItemId.get(pid)) || (vid > 0 && mapByVideoId.get(vid)) || null;

        const stCell = row.querySelector('.js-enc-status');
        if (stCell) stCell.textContent = formatStatus(job);

        const cells = row.querySelectorAll('td');
        const progressCell = cells.length >= 4 ? cells[3] : null;
        if (progressCell) progressCell.textContent = formatProgress(job);

        const etaCell = row.querySelector('.js-enc-eta');
        if (etaCell) etaCell.textContent = formatEta(job);
      }

      const signature = jobs.map(j => `${j.id}:${j.status}:${j.progress}`).join('|');
      const completed = typeof json.completed_jobs === 'number' ? json.completed_jobs : null;

      if (lastSignature === null) lastSignature = signature;
      if (lastCompleted === null) lastCompleted = completed;

      // If something completed, reload so items move from queue -> encoded table.
      if (completed !== null && lastCompleted !== null && completed > lastCompleted) {
        location.reload();
        return;
      }

      lastSignature = signature;
      lastCompleted = completed;
    }

    // Poll only if the queue section exists
    if (document.querySelector('tr.js-enc-row')) {
      setInterval(() => { poll().catch(() => {}); }, 2000);
      poll().catch(() => {});
    }
  })();
</script>

<script>
  (function () {
    const channelId = {{ (int) $channel->id }};

    const stEl = document.getElementById('jsNpStatus');
    const titleEl = document.getElementById('jsNpTitle');
    const idxEl = document.getElementById('jsNpIndex');
    const remainEl = document.getElementById('jsNpRemain');
    const nextEl = document.getElementById('jsNpNext');

    let remaining = null;
    let lastFetchAt = 0;

    function fmt(seconds) {
      const s = Math.max(0, Number(seconds || 0));
      const hh = Math.floor(s / 3600);
      const mm = Math.floor((s % 3600) / 60);
      const ss = Math.floor(s % 60);
      const pad = (n) => String(n).padStart(2, '0');
      return pad(hh) + ':' + pad(mm) + ':' + pad(ss);
    }

    function renderIdle(msg) {
      if (stEl) stEl.textContent = msg || 'Not running';
      if (titleEl) titleEl.textContent = '—';
      if (idxEl) idxEl.textContent = '';
      if (remainEl) remainEl.textContent = '—';
      if (nextEl) nextEl.textContent = '—';
      remaining = null;
    }

    async function fetchNow() {
      const res = await fetch(`/vod-channels/${channelId}/now-playing`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || json.status !== 'success') return;

      lastFetchAt = Date.now();

      if (!json.is_running) {
        renderIdle('Not running');
        return;
      }
      if (!json.has_playlist) {
        renderIdle('No encoded playlist');
        return;
      }
      if (!json.now) {
        renderIdle('—');
        return;
      }

      if (stEl) stEl.textContent = 'LIVE';
      if (titleEl) titleEl.textContent = String(json.now.title || '—');
      if (idxEl) idxEl.textContent = json.now.index ? `(#${Number(json.now.index)})` : '';
      remaining = Number(json.now.remaining_seconds || 0);
      if (remainEl) remainEl.textContent = fmt(remaining);

      const next = Array.isArray(json.next) ? json.next : [];
      const list = next.map(n => String(n.title || '')).filter(Boolean).join(' · ');
      if (nextEl) nextEl.textContent = list || '—';
    }

    setInterval(() => {
      if (remaining === null) return;
      remaining = Math.max(0, remaining - 1);
      if (remainEl) remainEl.textContent = fmt(remaining);
      if (remaining === 0 && Date.now() - lastFetchAt > 1500) {
        fetchNow().catch(() => {});
      }
    }, 1000);

    setInterval(() => { fetchNow().catch(() => {}); }, 5000);
    fetchNow().catch(() => {});
  })();
</script>

<div class="card">
  <div class="card-h">
    <div class="card-t">Encoded Items</div>
  </div>
  <div class="card-b table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:70px;">#</th>
          <th>Title</th>
          <th style="width:130px;">Duration</th>
          <th style="width:120px;">Resolution</th>
          <th style="width:140px;">Format</th>
          <th style="width:110px;">Bitrate</th>
          <th style="width:110px;">Status</th>
          <th class="cell-actions">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($playlistItems as $index => $item)
          <?php
            $video = $item->video;
            $displayTitle = $vodDisplayTitle($video);
            $duration = (int) ($video?->duration_seconds ?? 0);
            $durationText = $duration > 0 ? gmdate('H:i:s', $duration) : '—';
            $posterUrl = ($video && !empty($video->tmdb_poster_path)) ? ('https://image.tmdb.org/t/p/w92' . $video->tmdb_poster_path) : '';
            $tsFile = $item->ts_file ?? ("video_" . (int) $item->id . ".ts");
            $tsUrl = url("/streams/{$channel->id}/{$tsFile}");
            $popupUrl = route('vod-channels.playlist.player', [$channel, $item]);
            $job = ($video && isset($jobByVideoId)) ? ($jobByVideoId->get($video->id) ?? null) : null;
            $jobStatus = $job ? strtoupper((string)($job->status ?? '')) : '';
          ?>
          <tr data-duration="{{ $duration }}">
            <td class="muted">{{ $index + 1 }}</td>
            <td class="title-cell">
              <div class="title-wrap">
                @if($posterUrl !== '')
                  <img class="poster" src="{{ $posterUrl }}" alt="">
                @endif
                <div>
                  <div>{{ $displayTitle }}</div>
                  @if($video && !empty($video->tmdb_genres))
                    <div class="muted" style="font-size:11px; font-weight:800; margin-top:2px;">{{ $video->tmdb_genres }}</div>
                  @endif
                </div>
              </div>
            </td>
            <td class="muted">{{ $durationText }}</td>
            <td class="muted">{{ $video?->resolution ?: '—' }}</td>
            <td class="muted">{{ $video?->format ?: '—' }}</td>
            <td class="muted">{{ $video?->bitrate_kbps ? ($video->bitrate_kbps . ' kbps') : '—' }}</td>
            <td>
              <span class="status-ok">TS READY</span>
              @if($jobStatus !== '' && $jobStatus !== 'COMPLETED')
                <span class="muted">({{ $jobStatus }})</span>
              @endif
            </td>
            <td class="cell-actions">
              <div class="btn-col">
                <button
                  type="button"
                  class="btn btn-green btn-sm js-play"
                  data-title="{{ e($displayTitle) }}"
                  data-duration="{{ $duration }}"
                  data-popup-url="{{ $popupUrl }}"
                >
                  Play
                </button>

                <button type="button" class="btn btn-outline btn-sm js-watch" data-popup-url="{{ $popupUrl }}">Watch</button>

                @if($video)
                  <button
                    type="button"
                    class="btn btn-blue btn-sm js-video-info"
                    data-video-id="{{ (int) $video->id }}"
                  >Info</button>
                  <a class="btn btn-outline btn-sm" href="{{ route('videos.edit', $video) }}">Edit</a>
                @else
                  <button class="btn btn-blue btn-sm" type="button" disabled>Info</button>
                  <button class="btn btn-outline btn-sm" type="button" disabled>Edit</button>
                @endif

                <form method="POST" action="{{ route('vod-channels.playlist.move-up', [$channel, $item]) }}" style="margin:0;">
                  @csrf
                  <button class="btn btn-blue btn-sm" type="submit">↑</button>
                </form>

                <form method="POST" action="{{ route('vod-channels.playlist.move-down', [$channel, $item]) }}" style="margin:0;">
                  @csrf
                  <button class="btn btn-blue btn-sm" type="submit">↓</button>
                </form>

                <form method="POST" action="{{ route('vod-channels.playlist.remove', [$channel, $item]) }}" style="margin:0;" onsubmit="return confirm('Remove this item from playlist?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-red btn-sm" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="muted" style="padding:18px;">No encoded items found for this channel.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div id="jsVideoInfoModal" class="fox-modal" style="display:none; position:fixed; inset:0; z-index: 9999;">
    <div class="fox-modal-backdrop" style="position:absolute; inset:0; background: rgba(0,0,0,.55);"></div>
    <div class="fox-modal-panel" style="position:relative; max-width: 980px; margin: 6vh auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-lg); overflow:hidden;">
      <div style="padding: 12px 14px; border-bottom: 1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap: 10px;">
        <div style="font-size: 14px; font-weight: 900; color: var(--text-primary);">TMDB Info</div>
        <button type="button" class="btn btn-outline btn-sm" id="jsVideoInfoClose">Close</button>
      </div>
      <div style="padding: 14px;">
        <div id="jsVideoInfoBody" class="muted" style="font-size: 13px;">Loading…</div>
      </div>
    </div>
  </div>
</div>

<div class="bottom-bar">
  <div class="bottom-actions">
    <form method="POST" action="{{ route('vod-channels.sync-playlist-from-category', $channel) }}" style="margin:0; flex:1 1 220px;">
      @csrf
      <button class="btn btn-green" type="submit">Update Playlist</button>
    </form>

    <a class="btn btn-dark" href="{{ route('vod-channels.index') }}">Channels</a>

    <a class="btn btn-blue" href="{{ route('create-video.show', $channel) }}">Create Video</a>

    <a class="btn btn-outline" href="{{ route('vod-channels.encoding-now', $channel) }}">Encodare acum</a>
  </div>

  <div class="total-pill">
    <div>
      <div class="label">Total Time</div>
      <div class="value" id="bottomTotalTime">—</div>
    </div>
    <div class="muted" style="font-weight:900;">Playlist length</div>
  </div>
 </div>

<script>
(function () {
  const totalTime = document.getElementById('totalTime');
  const bottomTotalTime = document.getElementById('bottomTotalTime');

  function fmtTotal(seconds) {
    if (!Number.isFinite(seconds) || seconds <= 0) return '—';
    const s = Math.floor(seconds);
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const r = s % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return pad(h) + ':' + pad(m) + ':' + pad(r);
  }

  // Total time (encoded-only list)
  try {
    let sum = 0;
    document.querySelectorAll('tbody tr[data-duration]').forEach((tr) => {
      const d = Number(tr.getAttribute('data-duration') || '0');
      if (Number.isFinite(d) && d > 0) sum += d;
    });
    const t = fmtTotal(sum);
    totalTime.textContent = 'Total Time: ' + t;
    if (bottomTotalTime) bottomTotalTime.textContent = t;
  } catch (e) {
    totalTime.textContent = 'Total Time: —';
    if (bottomTotalTime) bottomTotalTime.textContent = '—';
  }


  function openPopup(url) {
    if (!url) return;
    const w = 980;
    const h = 620;
    const left = Math.max(0, Math.floor((window.screen.width - w) / 2));
    const top = Math.max(0, Math.floor((window.screen.height - h) / 2));
    const win = window.open(url, 'encoded_player', `width=${w},height=${h},left=${left},top=${top},resizable=yes,scrollbars=no`);
    if (win) win.focus();
  }

  document.querySelectorAll('.js-play, .js-watch').forEach((btn) => {
    btn.addEventListener('click', () => {
      const url = btn.getAttribute('data-popup-url') || '';
      openPopup(url);
    });
  });
})();
</script>

<script>
  (function () {
    const modal = document.getElementById('jsVideoInfoModal');
    const body = document.getElementById('jsVideoInfoBody');
    const closeBtn = document.getElementById('jsVideoInfoClose');
    const backdrop = modal ? modal.querySelector('.fox-modal-backdrop') : null;

    function close() {
      if (!modal) return;
      modal.style.display = 'none';
      if (body) body.innerHTML = '';
    }

    async function open(videoId) {
      if (!modal || !body) return;
      modal.style.display = 'block';
      body.textContent = 'Loading…';

      try {
        const res = await fetch(`/videos/${videoId}/info`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.success) {
          body.textContent = (json && (json.error || json.message)) ? (json.error || json.message) : 'Failed to load info.';
          return;
        }

        const v = json.video || {};
        const t = json.tmdb;
        const tmdbOk = t && t.ok === true;

        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const line = (k, val) => `<div style="display:flex; gap:10px; padding:6px 0; border-bottom:1px solid var(--border-light);"><div style="width:140px; font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.4px;">${esc(k)}</div><div style="flex:1; color:var(--text-primary);">${val}</div></div>`;

        let html = '';
        html += `<div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">`;
        const poster = tmdbOk && t.poster_url ? `<img src="${esc(t.poster_url)}" alt="" style="width:160px; border-radius:6px; border:1px solid var(--border-color);" />` : (v.tmdb_poster_url ? `<img src="${esc(v.tmdb_poster_url)}" alt="" style="width:160px; border-radius:6px; border:1px solid var(--border-color);" />` : '');
        if (poster) {
          html += `<div style="flex:0 0 auto;">${poster}</div>`;
        }
        html += `<div style="flex:1; min-width: 320px;">`;

        html += `<div style="font-size:16px; font-weight:900; color:var(--text-primary); margin-bottom:8px;">${esc(v.title || '')}</div>`;
        html += `<div class="muted" style="font-size:12px; margin-bottom:10px;">Video ID: ${esc(v.id)}${v.tmdb_id ? ` · TMDB: #${esc(v.tmdb_id)}` : ''}${v.duration ? ` · Duration: ${esc(v.duration)}` : ''}</div>`;

        if (tmdbOk) {
          html += line('TMDB Title', esc(t.title || '—'));
          html += line('Original Title', esc(t.original_title || '—'));
          html += line('Release Date', esc(t.release_date || '—'));
          html += line('Runtime', t.runtime ? esc(String(t.runtime) + ' min') : '—');
          html += line('Genres', Array.isArray(t.genres) && t.genres.length ? esc(t.genres.join(', ')) : '—');
          html += line('Rating', (t.vote_average != null) ? esc(String(t.vote_average)) + (t.vote_count ? ` <span class="muted">(${esc(String(t.vote_count))} votes)</span>` : '') : '—');
          if (t.imdb_id) {
            html += line('IMDB', `<a href="https://www.imdb.com/title/${esc(t.imdb_id)}/" target="_blank" rel="noopener" style="color: var(--fox-blue); text-decoration:none; font-weight:900;">${esc(t.imdb_id)}</a>`);
          }
          if (t.overview) {
            html += `<div style="margin-top:10px; padding:10px 12px; border:1px solid var(--border-color); border-radius:6px; background: var(--card-bg);">
              <div style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Overview</div>
              <div style="color:var(--text-primary); line-height:1.45;">${esc(t.overview)}</div>
            </div>`;
          }
        } else {
          const msg = t && t.message ? String(t.message) : (v.tmdb_id ? 'TMDB details unavailable.' : 'This video has no TMDB ID yet.');
          html += `<div class="muted" style="padding:10px 12px; border:1px solid var(--border-color); border-radius:6px;">${esc(msg)}</div>`;
        }

        html += `</div></div>`;
        body.innerHTML = html;
      } catch (e) {
        body.textContent = 'Failed to load info.';
      }
    }

    document.addEventListener('click', function (e) {
      const btn = e.target && e.target.closest ? e.target.closest('.js-video-info') : null;
      if (!btn) return;
      const videoId = Number(btn.getAttribute('data-video-id') || 0);
      if (!videoId) return;
      open(videoId);
    });

    closeBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  })();
</script>
@endsection
