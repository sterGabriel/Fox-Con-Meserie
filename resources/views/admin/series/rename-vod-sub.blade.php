@extends('layouts.panel')

@section('content')
<style>
  .vodsub-wrap { max-width: 1400px; margin: 0 auto; padding: 16px; }
  .vodsub-toolbar { display:flex; align-items:center; justify-content:space-between; gap: 12px; padding: 12px 14px; border:1px solid #e5e7eb; border-radius: 12px; background: #fff; }
  .vodsub-title { font-size: 18px; font-weight: 900; color: #111827; }
  .vodsub-subtitle { font-size: 12px; font-weight: 700; color: #6b7280; }
  .vodsub-actions { display:flex; align-items:center; gap: 10px; flex-wrap: wrap; }
  .btn { padding: 10px 12px; border-radius: 10px; border:1px solid #e5e7eb; background:#fff; font-weight: 900; color:#111827; cursor:pointer; }
  .btn-primary { border-color: rgba(37, 99, 235, 0.45); background: rgba(37, 99, 235, 0.08); color: #1d4ed8; }
  .btn-danger { border-color: rgba(220, 38, 38, 0.45); background: rgba(220, 38, 38, 0.06); color: #b91c1c; }
  .btn:disabled { opacity: 0.55; cursor: not-allowed; }
  .picker { padding: 10px 12px; border-radius: 10px; border:1px solid #e5e7eb; background:#fff; font-weight: 800; color:#111827; }

  .vodsub-grid { display:grid; grid-template-columns: 1.15fr 0.85fr; gap: 14px; margin-top: 14px; }
  @media (max-width: 1200px) { .vodsub-grid { grid-template-columns: 1fr; } }

  .card { background:#fff; border:1px solid #e5e7eb; border-radius: 12px; padding: 14px; }
  .card-title { font-weight: 900; color: #111827; margin-bottom: 10px; }
  .muted { font-size: 12px; color: #6b7280; font-weight: 700; }
  .hint { font-size: 12px; color: #6b7280; }

  table { width:100%; border-collapse: collapse; }
  th, td { text-align:left; padding: 10px 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
  th { font-size: 12px; color: #6b7280; font-weight: 900; text-transform: uppercase; letter-spacing: .02em; }
  .row-actions { display:flex; gap: 8px; align-items:center; }
  .mini { padding: 6px 10px; border-radius: 10px; border:1px solid #e5e7eb; background:#fff; font-weight: 900; cursor:pointer; }
  .mini-primary { border-color: rgba(37, 99, 235, 0.45); background: rgba(37, 99, 235, 0.08); color: #1d4ed8; }
  .mini-danger { border-color: rgba(220, 38, 38, 0.45); background: rgba(220, 38, 38, 0.06); color: #b91c1c; }

  .queue-empty { border: 2px dashed #dbeafe; border-radius: 12px; padding: 28px; text-align:center; }
  .queue-empty .big { font-weight: 900; color:#111827; font-size: 16px; }
  .queue-empty .small { color:#6b7280; font-size: 12px; font-weight: 700; margin-top: 6px; }

  .form-row { display:grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  @media (max-width: 900px) { .form-row { grid-template-columns: 1fr; } }
  label { display:block; font-size: 12px; font-weight: 900; color:#374151; margin-bottom: 6px; }
  input[type="text"], input[type="number"], select { width:100%; padding: 10px 12px; border-radius: 10px; border:1px solid #e5e7eb; background:#fff; font-weight: 800; color:#111827; }
  input[type="checkbox"] { width: 18px; height: 18px; }
  .check { display:flex; gap: 10px; align-items:center; margin-top: 10px; }

  .preview { border-radius: 12px; border: 1px solid #e5e7eb; background: #0b1220; aspect-ratio: 16 / 9; display:flex; align-items:center; justify-content:center; overflow:hidden; }
  .preview img { width:100%; height:100%; object-fit: contain; display:none; }

  .bottom-bar { margin-top: 14px; border:1px solid #e5e7eb; border-radius: 12px; background:#fff; padding: 12px 14px; display:flex; align-items:center; justify-content:space-between; gap: 12px; flex-wrap: wrap; }
  .bottom-left { display:flex; gap: 10px; align-items:center; flex-wrap: wrap; }
  .status { font-size: 12px; font-weight: 800; color: #6b7280; }
</style>

<div class="vodsub-wrap">
  <div class="vodsub-toolbar">
    <div>
      <div class="vodsub-title">VOD Sub — Encoder Workbench</div>
      <div class="vodsub-subtitle">Selectezi filme, setezi logo/nume/countdown/sub, vezi preview, apoi Convert All.</div>
    </div>

    <div class="vodsub-actions">
      <select class="picker" id="channel_id">
        @foreach(($channels ?? []) as $ch)
          <option value="{{ (int)$ch->id }}">{{ $ch->name }}</option>
        @endforeach
      </select>

      <select class="picker" id="category_id">
        <option value="">Select category…</option>
        @foreach(($categories ?? []) as $cat)
          <option value="{{ (int)$cat->id }}">{{ $cat->name }}</option>
        @endforeach
      </select>

      <button type="button" class="btn btn-primary" id="btn-add-files">＋ Add Files</button>
      <button type="button" class="btn btn-danger" id="btn-clear">Clear</button>
    </div>
  </div>

  <div class="vodsub-grid">
    <div class="card">
      <div class="card-title">Files</div>
      <div class="muted">Alege categoria, apoi apasă Add Files și selectează filmele din listă.</div>

      <div style="margin-top: 10px;" id="filesArea">
        <div class="queue-empty" id="filesEmpty">
          <div class="big">Getting Started</div>
          <div class="small">Step 1: select category → Add Files</div>
          <div class="small">Step 2: select items → Preview</div>
          <div class="small">Step 3: Convert All</div>
        </div>

        <div id="filesTableWrap" style="display:none;">
          <table>
            <thead>
              <tr>
                <th style="width: 40px;"></th>
                <th>Title</th>
                <th style="width: 120px;">Duration</th>
                <th style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody id="filesTbody">
              <tr><td colspan="4" class="hint">No files loaded</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div style="margin-top: 14px;">
        <div class="card-title" style="margin-bottom: 8px;">Queue</div>
        <div class="hint">Filmele selectate pentru Test/Convert. Setările de subtitrare sunt per-film (Apply to all există).</div>
        <div id="queueList" style="margin-top: 10px; display:grid; gap: 8px;"></div>
      </div>
    </div>

    <div style="display:grid; gap: 14px;">
      <div class="card">
        <div class="card-title">Settings</div>

        <div class="form-row">
          <div>
            <label>Subtitle Mode</label>
            <select id="subtitle_mode_ui">
              <option value="none" selected>None</option>
              <option value="burn_in">Burn-in (scris pe video)</option>
            </select>
          </div>
          <div>
            <label>Subtitle Language</label>
            <select id="subtitle_language_ui">
              <option value="none" selected>None</option>
              <option value="ro">Romanian</option>
              <option value="en">English</option>
              <option value="es">Spanish</option>
              <option value="fr">French</option>
              <option value="de">German</option>
              <option value="it">Italian</option>
            </select>
          </div>
        </div>

        <div class="check">
          <input type="checkbox" id="subtitle_auto_ui" checked>
          <label for="subtitle_auto_ui" style="margin:0;">Auto-detect lângă video (movie.ro.srt / movie.srt)</label>
        </div>

        <div style="margin-top: 10px;">
          <label>SRT Path (manual)</label>
          <input type="text" id="subtitle_path_ui" placeholder="/path/to/movie.srt">
          <div class="hint" style="margin-top: 6px;">Dacă este completat, are prioritate peste auto-detect.</div>
        </div>

        <div style="margin-top: 12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <button type="button" class="btn btn-primary" id="subtitle_apply_all_btn">Apply subtitles to all</button>
          <div class="status" id="subtitle_target_hint">—</div>
        </div>

        <div style="height: 1px; background:#e5e7eb; margin: 14px 0;"></div>

        <div class="form-row">
          <div>
            <label>Logo</label>
            <select id="overlay_logo_enabled">
              <option value="0" selected>Disabled</option>
              <option value="1">Enabled</option>
            </select>
          </div>
          <div>
            <label>Logo Opacity (%)</label>
            <input type="number" id="overlay_logo_opacity" value="100" min="0" max="100">
          </div>
        </div>

        <div class="form-row" style="margin-top: 10px;">
          <div>
            <label>Logo Position</label>
            <select id="overlay_logo_position">
              <option value="TL" selected>Top Left</option>
              <option value="TR">Top Right</option>
              <option value="BL">Bottom Left</option>
              <option value="BR">Bottom Right</option>
              <option value="CUSTOM">Custom</option>
            </select>
          </div>
          <div>
            <label>Logo Size (WxH)</label>
            <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 10px;">
              <input type="number" id="overlay_logo_width" value="192" min="1">
              <input type="number" id="overlay_logo_height" value="60" min="1">
            </div>
          </div>
        </div>

        <div class="form-row" style="margin-top: 10px;">
          <div>
            <label>Logo X</label>
            <input type="number" id="overlay_logo_x" value="30" min="0">
          </div>
          <div>
            <label>Logo Y</label>
            <input type="number" id="overlay_logo_y" value="30" min="0">
          </div>
        </div>

        <div style="height: 1px; background:#e5e7eb; margin: 14px 0;"></div>

        <div class="form-row">
          <div>
            <label>Title/Text</label>
            <select id="overlay_text_enabled">
              <option value="0">Disabled</option>
              <option value="1" selected>Enabled</option>
            </select>
          </div>
          <div>
            <label>Text Mode</label>
            <select id="overlay_text_content">
              <option value="title" selected>Video Title</option>
              <option value="channel_name">Channel Name</option>
              <option value="custom">Custom</option>
            </select>
          </div>
        </div>

        <div style="margin-top: 10px;">
          <label>Custom Text</label>
          <input type="text" id="overlay_text_custom" placeholder="Text...">
        </div>

        <div class="form-row" style="margin-top: 10px;">
          <div>
            <label>Text Position</label>
            <select id="overlay_text_position">
              <option value="BL" selected>Bottom Left</option>
              <option value="BR">Bottom Right</option>
              <option value="TL">Top Left</option>
              <option value="TR">Top Right</option>
              <option value="CUSTOM">Custom</option>
            </select>
          </div>
          <div>
            <label>Text Size</label>
            <input type="number" id="overlay_text_font_size" value="28" min="1">
          </div>
        </div>

        <div class="form-row" style="margin-top: 10px;">
          <div>
            <label>Text X</label>
            <input type="number" id="overlay_text_x" value="30" min="0">
          </div>
          <div>
            <label>Text Y</label>
            <input type="number" id="overlay_text_y" value="30" min="0">
          </div>
        </div>

        <div style="margin-top: 10px;">
          <label>Text Color</label>
          <select id="overlay_text_color">
            <option value="white" selected>White</option>
            <option value="yellow">Yellow</option>
            <option value="black">Black</option>
          </select>
        </div>

        <input type="hidden" id="overlay_text_font_family" value="Ubuntu">

        <div style="height: 1px; background:#e5e7eb; margin: 14px 0;"></div>

        <div class="form-row">
          <div>
            <label>Countdown</label>
            <select id="overlay_timer_enabled">
              <option value="0">Disabled</option>
              <option value="1" selected>Enabled</option>
            </select>
          </div>
          <div>
            <label>Timer Size</label>
            <input type="number" id="overlay_timer_font_size" value="28" min="1">
          </div>
        </div>

        <div class="form-row" style="margin-top: 10px;">
          <div>
            <label>Timer Position</label>
            <select id="overlay_timer_position">
              <option value="BL" selected>Bottom Left</option>
              <option value="BR">Bottom Right</option>
              <option value="TL">Top Left</option>
              <option value="TR">Top Right</option>
              <option value="CUSTOM">Custom</option>
            </select>
          </div>
          <div>
            <label>Timer Color</label>
            <select id="overlay_timer_color">
              <option value="#FFFFFF" selected>White</option>
              <option value="#FFD700">Gold</option>
            </select>
          </div>
        </div>

        <div class="form-row" style="margin-top: 10px;">
          <div>
            <label>Timer X</label>
            <input type="number" id="overlay_timer_x" value="30" min="0">
          </div>
          <div>
            <label>Timer Y</label>
            <input type="number" id="overlay_timer_y" value="70" min="0">
          </div>
        </div>

        <input type="hidden" id="overlay_timer_mode" value="countdown">
        <input type="hidden" id="overlay_timer_format" value="HH:mm:ss">
      </div>

      <div class="card">
        <div class="card-title">Preview</div>
        <div class="preview" id="previewBox">
          <img id="previewImg" alt="preview">
          <div class="hint" id="previewHint" style="padding: 12px; text-align:center; color:#cbd5e1; font-weight: 900;">Select a film → Preview</div>
        </div>
        <div class="hint" id="previewStatus" style="margin-top: 10px;">Ready.</div>
        <div style="margin-top: 10px; display:flex; gap: 10px; flex-wrap: wrap;">
          <button type="button" class="btn btn-primary" id="btn-preview" disabled>Preview</button>
        </div>
      </div>
    </div>
  </div>

  <div class="bottom-bar">
    <div class="bottom-left">
      <div class="status"><strong>Output:</strong></div>
      <select class="picker" id="output_container" disabled>
        <option value="ts" selected>TS (24/7 Channel)</option>
      </select>
      <div class="status" id="queueStatus">0 files selected</div>
    </div>
    <div style="display:flex; gap: 10px; align-items:center; flex-wrap: wrap;">
      <button type="button" class="btn btn-primary" id="btn-convert-all" disabled>Convert All</button>
    </div>
  </div>
</div>

<script>
  const CHANNELS = @json(collect($channels ?? [])->map(fn($c) => ['id' => (int)$c->id, 'name' => $c->name])->values());
  const CATEGORIES = @json(collect($categories ?? [])->map(fn($c) => ['id' => (int)$c->id, 'name' => $c->name])->values());

  let loadedVideos = [];
  let selected = new Map();
  let subtitleSettingsByVideoId = new Map();
  let currentPreviewVideoId = 0;

  function qs(id) { return document.getElementById(id); }

  function normalizeSubtitleMode(mode) {
    const m = String(mode || '').trim().toLowerCase();
    if (m === 'burn' || m === 'burnin' || m === 'burn-in' || m === 'hard' || m === 'hardcode') return 'burn_in';
    if (m === 'burn_in') return 'burn_in';
    return 'none';
  }

  function getChannelId() {
    const v = parseInt(qs('channel_id')?.value || '0', 10);
    return Number.isFinite(v) && v > 0 ? v : 0;
  }
  function getCategoryId() {
    const v = parseInt(qs('category_id')?.value || '0', 10);
    return Number.isFinite(v) && v > 0 ? v : 0;
  }

  function getSubtitleSettingsForVideo(videoId) {
    const id = parseInt(videoId || 0, 10);
    if (!Number.isFinite(id) || id <= 0) return { subtitle_mode: 'none', subtitle_language: 'none', subtitle_auto: true, subtitle_path: '' };
    const existing = subtitleSettingsByVideoId.get(id);
    if (existing && typeof existing === 'object') {
      return {
        subtitle_mode: normalizeSubtitleMode(existing.subtitle_mode || 'none'),
        subtitle_language: String(existing.subtitle_language || 'none').trim().toLowerCase() || 'none',
        subtitle_auto: !!existing.subtitle_auto,
        subtitle_path: String(existing.subtitle_path || '').trim(),
      };
    }
    return { subtitle_mode: 'none', subtitle_language: 'none', subtitle_auto: true, subtitle_path: '' };
  }

  function setSubtitleSettingsForVideo(videoId, s) {
    const id = parseInt(videoId || 0, 10);
    if (!Number.isFinite(id) || id <= 0) return;
    subtitleSettingsByVideoId.set(id, {
      subtitle_mode: normalizeSubtitleMode(s?.subtitle_mode || 'none'),
      subtitle_language: String(s?.subtitle_language || 'none').trim().toLowerCase() || 'none',
      subtitle_auto: !!s?.subtitle_auto,
      subtitle_path: String(s?.subtitle_path || '').trim(),
    });
  }

  function readSubtitleUi() {
    return {
      subtitle_mode: normalizeSubtitleMode(qs('subtitle_mode_ui')?.value || 'none'),
      subtitle_language: String(qs('subtitle_language_ui')?.value || 'none').trim().toLowerCase(),
      subtitle_auto: !!qs('subtitle_auto_ui')?.checked,
      subtitle_path: String(qs('subtitle_path_ui')?.value || '').trim(),
    };
  }

  function writeSubtitleUi(s) {
    if (qs('subtitle_mode_ui')) qs('subtitle_mode_ui').value = normalizeSubtitleMode(s?.subtitle_mode || 'none');
    if (qs('subtitle_language_ui')) qs('subtitle_language_ui').value = String(s?.subtitle_language || 'none').trim().toLowerCase() || 'none';
    if (qs('subtitle_auto_ui')) qs('subtitle_auto_ui').checked = !!s?.subtitle_auto;
    if (qs('subtitle_path_ui')) qs('subtitle_path_ui').value = String(s?.subtitle_path || '').trim();
  }

  function buildSettingsForVideo(videoId) {
    const s = {};

    // minimal encode keys used by EncodingService overlay + our subtitle keys
    s.output_container = 'ts';
    s.encoder = 'libx264';
    s.preset = 'fast';
    s.tune = '';
    s.video_bitrate = 0;
    s.audio_bitrate = 0;
    s.frame_rate = '';
    s.crf_mode = 'auto';
    s.crf_value = 0;

    // logo
    s.overlay_logo_enabled = String(qs('overlay_logo_enabled')?.value || '0') === '1';
    s.overlay_logo_position = String(qs('overlay_logo_position')?.value || 'TL');
    s.overlay_logo_x = parseInt(qs('overlay_logo_x')?.value || '0', 10) || 0;
    s.overlay_logo_y = parseInt(qs('overlay_logo_y')?.value || '0', 10) || 0;
    s.overlay_logo_width = parseInt(qs('overlay_logo_width')?.value || '0', 10) || 0;
    s.overlay_logo_height = parseInt(qs('overlay_logo_height')?.value || '0', 10) || 0;
    s.overlay_logo_opacity = parseInt(qs('overlay_logo_opacity')?.value || '100', 10) || 100;

    // text
    s.overlay_text_enabled = String(qs('overlay_text_enabled')?.value || '0') === '1';
    s.overlay_text_content = String(qs('overlay_text_content')?.value || 'title');
    s.overlay_text_custom = String(qs('overlay_text_custom')?.value || '');
    s.overlay_text_font_family = String(qs('overlay_text_font_family')?.value || 'Ubuntu');
    s.overlay_text_font_size = parseInt(qs('overlay_text_font_size')?.value || '28', 10) || 28;
    s.overlay_text_color = String(qs('overlay_text_color')?.value || 'white');
    s.overlay_text_position = String(qs('overlay_text_position')?.value || 'BL');
    s.overlay_text_x = parseInt(qs('overlay_text_x')?.value || '0', 10) || 0;
    s.overlay_text_y = parseInt(qs('overlay_text_y')?.value || '0', 10) || 0;
    s.overlay_text_opacity = 100;
    s.overlay_text_bg_color = 'black';
    s.overlay_text_bg_opacity = 0;
    s.overlay_text_padding = 0;

    // timer
    s.overlay_timer_enabled = String(qs('overlay_timer_enabled')?.value || '0') === '1';
    s.overlay_timer_mode = String(qs('overlay_timer_mode')?.value || 'countdown');
    s.overlay_timer_format = String(qs('overlay_timer_format')?.value || 'HH:mm:ss');
    s.overlay_timer_position = String(qs('overlay_timer_position')?.value || 'BL');
    s.overlay_timer_x = parseInt(qs('overlay_timer_x')?.value || '0', 10) || 0;
    s.overlay_timer_y = parseInt(qs('overlay_timer_y')?.value || '0', 10) || 0;
    s.overlay_timer_font_size = parseInt(qs('overlay_timer_font_size')?.value || '28', 10) || 28;
    s.overlay_timer_color = String(qs('overlay_timer_color')?.value || '#FFFFFF');

    // subtitles (per video)
    const sub = getSubtitleSettingsForVideo(videoId);
    s.subtitle_mode = sub.subtitle_mode;
    s.subtitle_language = sub.subtitle_language;
    s.subtitle_auto = !!sub.subtitle_auto;
    s.subtitle_path = sub.subtitle_path;

    return s;
  }

  function setQueueStatus() {
    const n = selected.size;
    if (qs('queueStatus')) qs('queueStatus').textContent = `${n} file(s) selected`;
    if (qs('btn-convert-all')) qs('btn-convert-all').disabled = n === 0;
    if (qs('btn-preview')) qs('btn-preview').disabled = currentPreviewVideoId <= 0;
  }

  function renderQueue() {
    const list = qs('queueList');
    if (!list) return;
    const ids = Array.from(selected.keys());
    if (!ids.length) {
      list.innerHTML = '<div class="hint">No files selected</div>';
      currentPreviewVideoId = 0;
      setQueueStatus();
      return;
    }

    list.innerHTML = ids.map(id => {
      const v = selected.get(id) || { id };
      const title = (v && v.title) ? v.title : ('Video #' + id);
      return `
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:12px;">
          <div style="font-weight:900; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${title}</div>
          <div class="row-actions">
            <button type="button" class="mini mini-primary" data-preview="${id}">Preview</button>
            <button type="button" class="mini mini-danger" data-remove="${id}">Remove</button>
          </div>
        </div>
      `;
    }).join('');

    list.querySelectorAll('[data-remove]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.getAttribute('data-remove') || '0', 10) || 0;
        selected.delete(id);
        if (currentPreviewVideoId === id) currentPreviewVideoId = 0;
        renderQueue();
      });
    });

    list.querySelectorAll('[data-preview]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.getAttribute('data-preview') || '0', 10) || 0;
        if (id > 0) {
          currentPreviewVideoId = id;
          syncSubtitleUiFromCurrentPreview();
          setQueueStatus();
          doPreview();
        }
      });
    });

    // pick first as default preview target
    if (currentPreviewVideoId <= 0) {
      currentPreviewVideoId = ids[0];
      syncSubtitleUiFromCurrentPreview();
    }

    setQueueStatus();
  }

  function syncSubtitleUiFromCurrentPreview() {
    if (currentPreviewVideoId <= 0) return;
    writeSubtitleUi(getSubtitleSettingsForVideo(currentPreviewVideoId));
    if (qs('subtitle_target_hint')) qs('subtitle_target_hint').textContent = `Editing subtitles for video #${currentPreviewVideoId}`;
  }

  async function loadVideos() {
    const catId = getCategoryId();
    const chId = getChannelId();
    if (!catId) {
      loadedVideos = [];
      renderFilesTable();
      return;
    }

    const url = `/api/videos?category_id=${encodeURIComponent(String(catId))}&channel_id=${encodeURIComponent(String(chId))}&exclude_in_playlist=0&limit=50`;
    const r = await fetch(url, { credentials: 'include', headers: { 'Accept': 'application/json' } });
    const data = await r.json().catch(() => ([]));
    loadedVideos = Array.isArray(data) ? data : [];
    renderFilesTable();
  }

  function renderFilesTable() {
    const empty = qs('filesEmpty');
    const wrap = qs('filesTableWrap');
    const tbody = qs('filesTbody');
    if (!tbody || !wrap || !empty) return;

    if (!loadedVideos.length) {
      wrap.style.display = 'none';
      empty.style.display = 'block';
      return;
    }

    empty.style.display = 'none';
    wrap.style.display = 'block';

    const fmtDur = (v) => {
      const s = parseInt(v?.duration_seconds || 0, 10) || 0;
      if (!s) return '—';
      const h = Math.floor(s / 3600);
      const m = Math.floor((s % 3600) / 60);
      const sec = Math.floor(s % 60);
      return (h > 0) ? `${h}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}` : `${m}:${String(sec).padStart(2,'0')}`;
    };

    tbody.innerHTML = loadedVideos.map(v => {
      const id = parseInt(v.id || 0, 10) || 0;
      const title = String(v.title || ('Video #' + id));
      return `
        <tr>
          <td><input type="checkbox" data-pick="${id}" ${selected.has(id) ? 'checked' : ''}></td>
          <td>${title}</td>
          <td>${fmtDur(v)}</td>
          <td>
            <div class="row-actions">
              <button type="button" class="mini mini-primary" data-add="${id}">Add</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    tbody.querySelectorAll('[data-add]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.getAttribute('data-add') || '0', 10) || 0;
        const v = loadedVideos.find(x => parseInt(x.id || 0, 10) === id);
        if (!id) return;
        selected.set(id, v || { id });
        if (!subtitleSettingsByVideoId.has(id)) {
          subtitleSettingsByVideoId.set(id, { subtitle_mode: 'none', subtitle_language: 'none', subtitle_auto: true, subtitle_path: '' });
        }
        renderQueue();
      });
    });

    tbody.querySelectorAll('[data-pick]').forEach(cb => {
      cb.addEventListener('change', () => {
        const id = parseInt(cb.getAttribute('data-pick') || '0', 10) || 0;
        const v = loadedVideos.find(x => parseInt(x.id || 0, 10) === id);
        if (!id) return;
        if (cb.checked) {
          selected.set(id, v || { id });
          if (!subtitleSettingsByVideoId.has(id)) {
            subtitleSettingsByVideoId.set(id, { subtitle_mode: 'none', subtitle_language: 'none', subtitle_auto: true, subtitle_path: '' });
          }
        } else {
          selected.delete(id);
          if (currentPreviewVideoId === id) currentPreviewVideoId = 0;
        }
        renderQueue();
      });
    });
  }

  async function doPreview() {
    const vid = currentPreviewVideoId;
    if (vid <= 0) return;
    const chId = getChannelId();
    if (chId <= 0) return;

    const previewImg = qs('previewImg');
    const hint = qs('previewHint');
    const status = qs('previewStatus');
    if (status) status.textContent = 'Generating preview…';
    if (hint) hint.style.display = 'none';
    if (previewImg) { previewImg.style.display = 'none'; previewImg.removeAttribute('src'); }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const r = await fetch(`/api/videos/${vid}/overlay-preview`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        live_channel_id: chId,
        ss: 30,
        settings: buildSettingsForVideo(vid),
      }),
    });

    const data = await r.json().catch(() => ({}));
    if (!r.ok || !data.ok) {
      if (status) status.textContent = data.message || 'Preview failed';
      if (hint) hint.style.display = 'block';
      return;
    }

    if (previewImg) {
      previewImg.src = `${data.preview_url}?t=${Date.now()}`;
      previewImg.style.display = 'block';
    }
    if (status) status.textContent = 'Preview ready.';
  }

  async function queueAll() {
    const ids = Array.from(selected.keys());
    if (!ids.length) return;
    const chId = getChannelId();
    if (chId <= 0) return;

    const btn = qs('btn-convert-all');
    if (btn) btn.disabled = true;
    if (qs('queueStatus')) qs('queueStatus').textContent = 'Queueing…';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let ok = 0;
    let fail = 0;

    for (const vid of ids) {
      try {
        const r = await fetch('/api/encoding-jobs', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          },
          body: JSON.stringify({
            live_channel_id: chId,
            video_id: vid,
            settings: buildSettingsForVideo(vid),
          }),
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data.ok) throw new Error(data.message || 'Queue failed');
        ok++;
      } catch (e) {
        fail++;
        console.error('Queue failed', vid, e);
      }
    }

    if (qs('queueStatus')) qs('queueStatus').textContent = `Queued ${ok}, failed ${fail}`;
    if (btn) btn.disabled = false;
  }

  (function wire() {
    const addBtn = qs('btn-add-files');
    const clearBtn = qs('btn-clear');
    const previewBtn = qs('btn-preview');
    const convertBtn = qs('btn-convert-all');

    if (qs('category_id')) {
      qs('category_id').addEventListener('change', () => loadVideos());
    }
    if (qs('channel_id')) {
      qs('channel_id').addEventListener('change', () => {
        loadVideos();
        if (currentPreviewVideoId > 0) doPreview();
      });
    }

    if (addBtn) addBtn.addEventListener('click', async () => {
      await loadVideos();
    });

    if (clearBtn) clearBtn.addEventListener('click', () => {
      selected.clear();
      currentPreviewVideoId = 0;
      renderQueue();
      const img = qs('previewImg');
      if (img) { img.style.display = 'none'; img.removeAttribute('src'); }
      if (qs('previewHint')) qs('previewHint').style.display = 'block';
      if (qs('previewStatus')) qs('previewStatus').textContent = 'Ready.';
    });

    // subtitle UI wiring
    ['subtitle_mode_ui','subtitle_language_ui','subtitle_auto_ui','subtitle_path_ui'].forEach(id => {
      const el = qs(id);
      if (!el) return;
      el.addEventListener('input', () => {
        if (currentPreviewVideoId <= 0) return;
        setSubtitleSettingsForVideo(currentPreviewVideoId, readSubtitleUi());
      });
      el.addEventListener('change', () => {
        if (currentPreviewVideoId <= 0) return;
        setSubtitleSettingsForVideo(currentPreviewVideoId, readSubtitleUi());
      });
    });
    if (qs('subtitle_apply_all_btn')) {
      qs('subtitle_apply_all_btn').addEventListener('click', () => {
        const ids = Array.from(selected.keys());
        if (!ids.length) return;
        const s = readSubtitleUi();
        ids.forEach(id => setSubtitleSettingsForVideo(id, s));
        if (qs('subtitle_target_hint')) qs('subtitle_target_hint').textContent = `Applied subtitles to ${ids.length} video(s)`;
      });
    }

    // settings change auto-preview (simple)
    const previewOn = () => { if (currentPreviewVideoId > 0) doPreview(); };
    [
      'overlay_logo_enabled','overlay_logo_opacity','overlay_logo_position','overlay_logo_width','overlay_logo_height','overlay_logo_x','overlay_logo_y',
      'overlay_text_enabled','overlay_text_content','overlay_text_custom','overlay_text_font_size','overlay_text_position','overlay_text_x','overlay_text_y','overlay_text_color',
      'overlay_timer_enabled','overlay_timer_font_size','overlay_timer_position','overlay_timer_x','overlay_timer_y','overlay_timer_color',
      'subtitle_mode_ui','subtitle_language_ui','subtitle_auto_ui','subtitle_path_ui'
    ].forEach(id => {
      const el = qs(id);
      if (!el) return;
      el.addEventListener('change', previewOn);
    });

    if (previewBtn) previewBtn.addEventListener('click', doPreview);
    if (convertBtn) convertBtn.addEventListener('click', queueAll);

    // initial
    renderQueue();
    setQueueStatus();
  })();
</script>
@endsection
