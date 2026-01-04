@extends('layouts.panel')

@section('content')
<style>
  .page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px; }
  .page-title { font-size:22px; font-weight:900; color:var(--text-primary); margin:0; }
  .page-subtitle { font-size:12px; color:var(--text-muted); margin-top:6px; }

  .card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:6px; box-shadow:var(--shadow-sm); overflow:hidden; }
  .card-h { padding:12px 14px; border-bottom:1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap:12px; }
  .card-t { font-size:13px; font-weight:900; color:var(--text-primary); }
  .card-b { padding:14px; }

  .btn-row { display:flex; gap:10px; flex-wrap:wrap; }
  .btn { padding:10px 12px; border-radius:6px; color:#fff; font-weight:900; font-size:12px; border:0; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
  .btn-blue { background:var(--fox-blue); }
  .btn-dark { background:var(--text-primary); }
  .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); }

  .kpis { display:flex; gap:10px; flex-wrap:wrap; }
  .kpi { flex:1 1 200px; border:1px solid var(--border-color); background:var(--card-bg); border-radius:6px; padding:12px 14px; box-shadow:var(--shadow-sm); }
  .kpi .label { font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; font-weight:900; }
  .kpi .value { font-size:18px; font-weight:900; color:var(--text-primary); margin-top:6px; }

  .table-wrap { overflow:auto; }
  table { width:100%; border-collapse:separate; border-spacing:0; }
  thead th { position:sticky; top:0; z-index:2; background:var(--card-bg); font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.4px; text-align:left; padding:10px 12px; border-bottom:1px solid var(--border-light); }
  tbody td { padding:10px 12px; border-bottom:1px solid var(--border-light); font-size:12px; color:var(--text-primary); vertical-align:middle; }
  tbody tr:hover { background:rgba(255,255,255,.03); }
  .muted { color:var(--text-muted); }

  .pill { display:inline-flex; align-items:center; justify-content:center; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:900; border:1px solid var(--border-color); }
  .pill-ok { color:var(--fox-green); }
  .pill-warn { color:var(--fox-yellow); }
  .pill-bad { color:var(--fox-red); }

  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
</style>

<div class="page-header">
  <div>
    <h1 class="page-title">Encodare acum — {{ $channel->name }}</h1>
    <div class="page-subtitle">Monitorizează joburile de encodare (queued/running/done/failed) pentru acest canal.</div>
  </div>
  <div class="btn-row">
    <a class="btn btn-outline" href="{{ route('vod-channels.playlist', $channel) }}">Playlist</a>
    <a class="btn btn-blue" href="{{ route('create-video.show', $channel) }}">Create Video</a>
    <a class="btn btn-dark" href="{{ route('vod-channels.index') }}">Channels</a>
  </div>
</div>

<div class="kpis" style="margin-bottom:14px;">
  <div class="kpi"><div class="label">Running</div><div class="value" id="jsRunning">—</div></div>
  <div class="kpi"><div class="label">Queued</div><div class="value" id="jsQueued">—</div></div>
  <div class="kpi"><div class="label">Done</div><div class="value" id="jsDone">—</div></div>
  <div class="kpi"><div class="label">Failed</div><div class="value" id="jsFailed">—</div></div>
</div>

<div class="card">
  <div class="card-h">
    <div class="card-t">Joburi de encodare</div>
    <div class="muted" style="font-size:12px; font-weight:900;" id="jsLastUpdate">—</div>
  </div>
  <div class="card-b table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:90px;">Job</th>
          <th>VOD</th>
          <th style="width:140px;">Status</th>
          <th style="width:120px;">Progress</th>
          <th style="width:120px;">ETA</th>
          <th style="width:120px;">Speed</th>
          <th style="width:140px;">Out Time</th>
        </tr>
      </thead>
      <tbody id="jsJobsBody">
        <tr><td colspan="7" class="muted" style="padding:18px;">Se încarcă…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  (function () {
    const channelId = {{ (int) $channel->id }};

    const runningEl = document.getElementById('jsRunning');
    const queuedEl = document.getElementById('jsQueued');
    const doneEl = document.getElementById('jsDone');
    const failedEl = document.getElementById('jsFailed');
    const lastUpdateEl = document.getElementById('jsLastUpdate');
    const bodyEl = document.getElementById('jsJobsBody');

    function esc(s) {
      return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function pill(status, queuedPos) {
      const st = String(status || '').toUpperCase();
      if (!st) return '<span class="pill">—</span>';
      if (st === 'RUNNING') return '<span class="pill pill-warn">RUNNING</span>';
      if (st === 'QUEUED') return `<span class="pill">QUEUED${queuedPos ? ' #' + Number(queuedPos) : ''}</span>`;
      if (st === 'DONE') return '<span class="pill pill-ok">DONE</span>';
      if (st === 'FAILED') return '<span class="pill pill-bad">FAILED</span>';
      return `<span class="pill">${esc(st)}</span>`;
    }

    function render(jobs) {
      if (!bodyEl) return;
      if (!Array.isArray(jobs) || jobs.length === 0) {
        bodyEl.innerHTML = '<tr><td colspan="7" class="muted" style="padding:18px;">Nu există joburi pentru acest canal.</td></tr>';
        return;
      }

      bodyEl.innerHTML = jobs.map((j) => {
        const id = Number(j.id || 0);
        const title = esc(j.video_title || 'Unknown');
        const st = String(j.status || '').toUpperCase();
        const pct = (typeof j.progress === 'number') ? j.progress : Number(j.progress || 0);
        const eta = esc(j.eta || '—');
        const speed = esc(j.speed || '—');
        const outTime = esc(j.out_time || '—');

        return `
          <tr>
            <td class="muted mono">#${id || '—'}</td>
            <td>${title}</td>
            <td class="muted">${pill(st, j.queued_position)}</td>
            <td class="muted">${(st === 'RUNNING' || st === 'DONE' || st === 'FAILED') ? (Number.isFinite(pct) ? (Math.max(0, Math.min(100, Math.floor(pct))) + '%') : '—') : '—'}</td>
            <td class="muted">${(st === 'RUNNING') ? eta : '—'}</td>
            <td class="muted">${(st === 'RUNNING') ? speed : '—'}</td>
            <td class="muted">${(st === 'RUNNING') ? outTime : '—'}</td>
          </tr>
        `;
      }).join('');
    }

    function showLoadError(message) {
      if (runningEl) runningEl.textContent = '—';
      if (queuedEl) queuedEl.textContent = '—';
      if (doneEl) doneEl.textContent = '—';
      if (failedEl) failedEl.textContent = '—';
      if (lastUpdateEl) lastUpdateEl.textContent = 'Eroare la încărcare';
      if (bodyEl) {
        bodyEl.innerHTML = `<tr><td colspan="7" class="muted" style="padding:18px;">Nu se poate încărca lista joburilor. ${esc(message || '')}</td></tr>`;
      }
    }

    async function poll() {
      let res;
      try {
        res = await fetch(`/vod-channels/${channelId}/engine/encoding-jobs`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store',
        });
      } catch (e) {
        showLoadError('Network error');
        return;
      }

      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        showLoadError(`HTTP ${res.status}`);
        return;
      }
      if (json.status !== 'success') {
        showLoadError(json.message || 'Unknown error');
        return;
      }

      const jobs = Array.isArray(json.jobs) ? json.jobs : [];
      const running = jobs.filter(j => String(j.status || '').toLowerCase() === 'running').length;
      const queued = jobs.filter(j => String(j.status || '').toLowerCase() === 'queued').length;
      const done = jobs.filter(j => String(j.status || '').toLowerCase() === 'done').length;
      const failed = jobs.filter(j => String(j.status || '').toLowerCase() === 'failed').length;

      if (runningEl) runningEl.textContent = String(running);
      if (queuedEl) queuedEl.textContent = String(queued);
      if (doneEl) doneEl.textContent = String(done);
      if (failedEl) failedEl.textContent = String(failed);

      if (lastUpdateEl) {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        lastUpdateEl.textContent = `Update: ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
      }

      // Sort: running first, then queued by position, then newest.
      const order = (j) => {
        const st = String(j.status || '').toLowerCase();
        if (st === 'running') return 0;
        if (st === 'queued') return 1;
        if (st === 'failed') return 2;
        if (st === 'done') return 3;
        return 9;
      };

      const sorted = jobs.slice().sort((a, b) => {
        const oa = order(a);
        const ob = order(b);
        if (oa !== ob) return oa - ob;

        const sa = Number(a.queued_position || 0);
        const sb = Number(b.queued_position || 0);
        if (oa === 1 && sa !== sb) {
          if (sa === 0) return 1;
          if (sb === 0) return -1;
          return sa - sb;
        }

        return Number(b.id || 0) - Number(a.id || 0);
      });

      render(sorted);
    }

    poll().catch(() => {});
    setInterval(() => poll().catch(() => {}), 1500);
  })();
</script>
@endsection
