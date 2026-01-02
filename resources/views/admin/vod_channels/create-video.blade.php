@extends('layouts.panel')

@section('full_width', true)

@section('content')

<style>
  * {
    box-sizing: border-box;
  }

  body {
    background: #f3f4f6;
    overflow-x: hidden;
  }

  .create-video-container {
    display: grid;
    grid-template-columns: 480px 1fr;
    gap: 20px;
    padding: 20px;
    max-width: 100%;
    margin: 0 auto;
    overflow-x: hidden;
  }

  @media (max-width: 1200px) {
    .create-video-container {
      grid-template-columns: 1fr;
    }
  }

  /* CARDS */
  .card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    overflow-x: hidden;
  }

  .card-title {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e5e7eb;
  }

  /* FORM ELEMENTS */
  .form-group {
    margin-bottom: 14px;
  }

  .form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-sizing: border-box;
    color: #111827;
    background: #ffffff;
  }

  .form-group input::placeholder,
  .form-group textarea::placeholder {
    color: #9ca3af;
  }

  .form-group select[multiple] {
    min-height: 72px;
  }

  .form-group textarea {
    min-height: 90px;
    resize: vertical;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
  }

  /* CHECKBOX */
  .form-check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 12px 0;
  }

  .form-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .form-check label {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
  }

  /* GRID HELPERS */
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .grid-2 .form-group {
    margin-bottom: 0;
  }

  .grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
  }

  .grid-3 .form-group {
    margin-bottom: 0;
  }

  /* SLIDER */
  .slider-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .slider-wrap input[type="range"] {
    flex: 1;
    width: 100%;
    max-width: 100%;
    height: 6px;
    padding: 0;
  }

  .slider-val {
    min-width: 50px;
    text-align: right;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
  }

  /* INFO BOX */
  .info-box {
    background: #dbeafe;
    border: 1px solid #3b82f6;
    border-radius: 6px;
    padding: 12px;
    font-size: 13px;
    color: #1e40af;
  }

  .info-box strong {
    display: block;
    font-weight: 600;
    color: #1e3a8a;
  }

  .info-details {
    display: none;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid rgba(30, 64, 175, 0.2);
  }

  .info-details.visible {
    display: block;
  }

  .info-detail-row {
    font-size: 12px;
    margin: 4px 0;
  }

  .selected-list {
    margin-top: 10px;
    display: grid;
    gap: 6px;
  }

  .selected-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.55);
    border: 1px solid rgba(30, 64, 175, 0.18);
  }

  .selected-item-title {
    font-size: 12px;
    font-weight: 700;
    color: #1e3a8a;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .btn-mini {
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 6px;
    border: 1px solid rgba(30, 64, 175, 0.25);
    background: rgba(255, 255, 255, 0.9);
    color: #1e3a8a;
    cursor: pointer;
  }

  .btn-mini:hover {
    background: #ffffff;
  }

  .btn-mini-secondary {
    border-color: rgba(30, 64, 175, 0.35);
  }

  .btn-mini-danger {
    border-color: rgba(220, 38, 38, 0.5);
    color: #dc2626;
  }

  .btn-mini-danger:hover {
    border-color: rgba(220, 38, 38, 0.8);
  }

  .btn-mini-primary {
    border-color: rgba(37, 99, 235, 0.45);
    color: #1d4ed8;
  }

  .btn-mini-primary:hover {
    border-color: rgba(37, 99, 235, 0.7);
  }

  .selected-item-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex: 0 0 auto;
  }

  /* Preview modal */
  .preview-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.55);
    z-index: 9999;
    padding: 18px;
  }
  .test-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }
  .test-modal.open { display: flex; }
  .test-modal .modal-inner {
    background: #fff;
    width: min(980px, 96vw);
    max-height: 90vh;
    overflow: auto;
    border-radius: 10px;
    padding: 14px;
  }
  .test-modal .modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
  }
  .test-modal .modal-title { font-weight: 800; }
  .test-modal video {
    width: 100%;
    max-height: 60vh;
    background: #000;
    border-radius: 8px;
  }
  .test-modal .modal-meta {
    margin-top: 10px;
    font-size: 12px;
    color: #555;
  }

  .preview-modal.open {
    display: flex;
  }

  .preview-modal-card {
    width: min(980px, 96vw);
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
  }

  .preview-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid #e5e7eb;
  }

  .preview-modal-title {
    font-size: 13px;
    font-weight: 800;
    color: #111827;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .preview-modal-close {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 800;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #f3f4f6;
    cursor: pointer;
  }

  .preview-modal-body {
    padding: 14px;
  }

  .preview-frame {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: #111827;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
  }

  .preview-frame img.preview-frame-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: none;
  }

  .preview-loading {
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
    margin-top: 10px;
  }

  .selected-actions {
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }

  .selected-count {
    font-size: 12px;
    font-weight: 700;
    color: rgba(30, 64, 175, 0.75);
  }

  /* BUTTONS */
  .button-group {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
  }

  button {
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-cancel {
    background: #e5e7eb;
    color: #111827;
  }

  .btn-cancel:hover {
    background: #d1d5db;
  }

  .btn-primary {
    background: #ef4444;
    color: #ffffff;
  }

  .btn-primary:hover {
    background: #dc2626;
  }

  .btn-success {
    background: #22c55e;
    color: #ffffff;
  }

  .btn-success:hover {
    background: #16a34a;
  }

  .btn-danger {
    background: #ef4444;
    color: #ffffff;
  }

  .btn-danger:hover {
    background: #dc2626;
  }

  /* RECOMMENDED SIZES TABLE */
  .recommended-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin-top: 12px;
  }

  .recommended-table thead {
    background: #f3f4f6;
    border-bottom: 1px solid #d1d5db;
  }

  .recommended-table th {
    padding: 8px 10px;
    text-align: left;
    font-weight: 600;
    color: #374151;
  }

  .recommended-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #e5e7eb;
    color: #111827;
  }

  .recommended-table tbody tr {
    cursor: pointer;
    transition: background 0.2s;
  }

  .recommended-table tbody tr:hover {
    background: #f9fafb;
  }

  .recommended-table tbody tr.highlight {
    background: #fee2e2;
    font-weight: 600;
  }

  .recommended-table tbody tr.highlight:hover {
    background: #fecaca;
  }

  /* TABLES */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }

  table thead {
    background: #f9fafb;
    border-bottom: 1px solid #d1d5db;
  }

  table th {
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
  }

  table td {
    padding: 10px 12px;
    border-bottom: 1px solid #e5e7eb;
    color: #111827;
  }

  table tbody tr:hover {
    background: #f9fafb;
  }

  .show-entries {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 13px;
    color: #374151;
  }

  .show-entries select {
    width: 70px;
    padding: 6px 8px;
    font-size: 13px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
  }

  .table-actions {
    display: flex;
    gap: 6px;
  }

  .table-actions button {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
  }

  .bulk-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 12px;
  }

  .no-data {
    text-align: center;
    color: #6b7280;
    padding: 20px;
    font-weight: 500;
  }

  /* RIGHT COLUMN CHANNEL */
  .channel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
  }

  .channel-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .channel-icon {
    width: 48px;
    height: 48px;
    background: #f3f4f6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
  }

  .channel-text h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
  }

  .channel-text p {
    margin: 4px 0 0 0;
    font-size: 12px;
    color: #6b7280;
  }

  .category-select {
    flex: 0 0 280px;
  }

  .category-select label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    display: block;
    margin-bottom: 6px;
  }

  .category-select select {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    font-weight: 500;
    color: #111827;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #ffffff;
  }

  .category-select select option {
    color: #111827;
    font-size: 14px;
  }

  /* STATUS COLORS */
  .status-pending {
    color: #3b82f6;
    font-weight: 500;
  }

  .status-running {
    color: #f97316;
    font-weight: 500;
  }

  .status-done {
    color: #22c55e;
    font-weight: 500;
  }

  .job-status-wrap {
    display: grid;
    gap: 6px;
  }

  .job-progress {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .job-progress-bar {
    position: relative;
    height: 8px;
    flex: 1;
    background: #e5e7eb;
    border-radius: 999px;
    overflow: hidden;
  }

  .job-progress-fill {
    height: 100%;
    width: 0%;
    background: #3b82f6;
  }

  .job-progress-pct {
    min-width: 52px;
    text-align: right;
    font-size: 12px;
    font-weight: 800;
    color: #111827;
  }

  .job-progress-meta {
    font-size: 11px;
    color: #6b7280;
    font-weight: 600;
  }

  .status-failed {
    color: #ef4444;
    font-weight: 500;
  }

  /* COLUMNS */
  .left-column {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .right-column {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  /* CHANNEL + CATEGORY PICKERS */
  .pickers {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    align-items: end;
  }

  @media (max-width: 900px) {
    .pickers {
      grid-template-columns: 1fr;
    }
  }

  .picker-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 6px;
  }

  .picker-select {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #ffffff;
    color: #111827;
  }

  .channel-banner {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .channel-logo {
    width: 90px;
    height: 90px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex: 0 0 auto;
  }

  .channel-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }

  .channel-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #111827;
    line-height: 1.2;
  }

  .channel-subtitle {
    margin: 4px 0 0;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
  }

  .hidden {
    display: none !important;
  }
</style>

<div class="create-video-container">
  <!-- LEFT COLUMN -->
  <div class="left-column">
    <!-- INFO BOX -->
    <div class="card">
      <div class="info-box">
        <strong id="selected-title">ℹ️ Please Select Video</strong>
        <div id="selected-meta">Choose a video from the table on the right</div>

        <div id="selected-list" class="selected-list"></div>

        <div class="selected-actions">
          <button type="button" class="btn-mini btn-mini-secondary" id="clearSelectedBtn">Clear</button>
          <div class="selected-count" id="selected-count">0 selected</div>
        </div>

        <div id="info-details" class="info-details">
          <div class="info-detail-row"><strong>Duration:</strong> <span id="detail-duration">—</span></div>
          <div class="info-detail-row"><strong>Resolution:</strong> <span id="detail-resolution">—</span></div>
          <div class="info-detail-row"><strong>Format:</strong> <span id="detail-format">—</span></div>
          <div class="info-detail-row"><strong>Size:</strong> <span id="detail-size">—</span></div>
        </div>
      </div>
    </div>

    <!-- Test playback modal (HLS .m3u8 with TS segments) -->
    <div class="test-modal" id="testPlayerModal" aria-hidden="true">
      <div class="modal-inner" role="dialog" aria-modal="true">
        <div class="modal-head">
          <div class="modal-title" id="testModalTitle">Test Playback</div>
          <button type="button" class="btn-danger" id="testModalCloseBtn">Close</button>
        </div>
        <video id="testPlayerVideo" controls playsinline></video>
        <div class="modal-meta" id="testModalMeta">Preparing test…</div>
      </div>
    </div>

    <!-- Real FFmpeg overlay preview modal (matches final encoding filter_complex) -->
    <div class="preview-modal" id="vodPreviewModal" aria-hidden="true">
      <div class="preview-modal-card" role="dialog" aria-modal="true">
        <div class="preview-modal-header">
          <div class="preview-modal-title" id="vodPreviewTitle">Preview</div>
          <button type="button" class="preview-modal-close" id="vodPreviewCloseBtn">Close</button>
        </div>
        <div class="preview-modal-body">
          <div class="preview-frame">
            <img id="vodPreviewImg" class="preview-frame-img" alt="Overlay preview" />
          </div>
          <div class="preview-loading" id="vodPreviewStatus">Ready.</div>
        </div>
      </div>
    </div>

    <!-- FORM -->
    <form id="createVideoForm" class="card">
      @csrf
      <input type="hidden" name="video_id" id="video_id">
      <input type="hidden" name="live_channel_id" value="{{ $channel->id }}">

      <!-- ENCODER SETTINGS -->
      <div class="card-title">🔧 Encoder Settings</div>

      <div class="form-group">
        <label>Channels</label>
        <select id="channel_switch_left" class="picker-select">
          @foreach(($channels ?? []) as $ch)
            <option value="{{ $ch->id }}" @selected((int)$ch->id === (int)$channel->id)>
              {{ $ch->name }}@if((int)$ch->id === (int)$channel->id) (current)@endif
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label>Encoder</label>
        <select name="encoder" id="encoder">
          <option value="libx264" selected>H.264 (libx264)</option>
          <option value="libx265">H.265 (libx265)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Video Codec</label>
        <select name="video_codec" id="video_codec">
          <option value="h264" selected>H.264</option>
          <option value="h265">H.265</option>
        </select>
      </div>

      <div class="form-group">
        <label>Transcoding Preset</label>
        <select name="preset" id="preset">
          <option value="fast" selected>Fast</option>
          <option value="medium">Medium</option>
          <option value="slow">Slow</option>
        </select>
      </div>

      <div class="form-group">
        <label>Tune</label>
        <select name="tune" id="tune">
          <option value="film" selected>Film</option>
          <option value="animation">Animation</option>
          <option value="grain">Grain</option>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label>Test Time Limit (sec)</label>
          <input type="number" name="test_time_limit" id="test_time_limit" value="60" min="10">
        </div>
        <div class="form-group">
          <label>Test Start Time (sec)</label>
          <input type="number" name="test_start_time" id="test_start_time" value="0" min="0">
        </div>
      </div>

      <div class="form-group">
        <label>Constant Rate Factor (CRF)</label>
        <select name="crf_mode" id="crf_mode">
          <option value="auto" selected>Auto</option>
          <option value="manual">Manual</option>
        </select>
        <input type="number" name="crf_value" id="crf_value" value="23" min="0" max="51" class="hidden" style="margin-top: 6px;">
      </div>

      <div class="form-group">
        <label>Video Aspect</label>
        <select name="video_aspect" id="video_aspect">
          <option value="16:9" selected>16:9</option>
          <option value="4:3">4:3</option>
          <option value="1:1">1:1</option>
        </select>
      </div>

      <div class="form-group">
        <label>Video Bitrate</label>
        <select name="video_bitrate" id="video_bitrate">
          <option value="1000">1000 kbps</option>
          <option value="1500">1500 kbps</option>
          <option value="2000">2000 kbps</option>
          <option value="2500">2500 kbps</option>
          <option value="3000">3000 kbps</option>
          <option value="3500">3500 kbps</option>
          <option value="4000" selected>4000 kbps (Standard)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Audio Bitrate</label>
        <select name="audio_bitrate" id="audio_bitrate">
          <option value="128" selected>128 kbps</option>
          <option value="192">192 kbps</option>
          <option value="256">256 kbps</option>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label>Stereo Type</label>
          <select name="stereo_type" id="stereo_type">
            <option value="stereo" selected>Stereo</option>
            <option value="mono">Mono</option>
          </select>
        </div>
        <div class="form-group">
          <label>Frame Rate</label>
          <select name="frame_rate" id="frame_rate">
            <option value="24">24 fps</option>
            <option value="25">25 fps</option>
            <option value="30" selected>30 fps</option>
            <option value="60">60 fps</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Audio Language</label>
        <select name="audio_language" id="audio_language">
          <option value="en" selected>English</option>
          <option value="es">Spanish</option>
          <option value="fr">French</option>
          <option value="de">German</option>
          <option value="it">Italian</option>
        </select>
      </div>

      <div class="form-group">
        <label>Audio Files</label>
        <select name="audio_files" id="audio_files" multiple>
          <option value="track1" selected>Track 1 (Default)</option>
          <option value="track2">Track 2 (Commentary)</option>
          <option value="track3">Track 3 (Descriptive)</option>
        </select>
        <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">Hold Ctrl/Cmd to select multiple</div>
      </div>

      <div class="form-group">
        <label>Banner Advertisement</label>
        <input type="text" name="banner_ad" id="banner_ad" placeholder="Enter banner text or URL">
      </div>

      <div class="form-group">
        <label>Subtitle Language (SRT)</label>
        <select name="subtitle_language" id="subtitle_language">
          <option value="none" selected>None</option>
          <option value="en">English</option>
          <option value="es">Spanish</option>
          <option value="fr">French</option>
        </select>
      </div>

      <div class="form-group">
        <label>Video Note</label>
        <textarea name="video_note" id="video_note" placeholder="Add notes about this video..."></textarea>
      </div>

      <div class="form-check">
        <input type="checkbox" name="remove_watermark" id="remove_watermark">
        <label for="remove_watermark">Enable watermark removal filter</label>
      </div>

      <!-- LOGO OVERLAY -->
      <div class="card-title" style="margin-top: 16px;">🖼️ Logo Overlay</div>

      <div class="form-check">
        <input type="checkbox" name="logo_enabled" id="logo_enabled" @checked((bool)($channel->overlay_logo_enabled ?? false) || !empty($channel->overlay_logo_path ?? $channel->logo_path ?? null))>
        <label for="logo_enabled">Transparent Logo</label>
      </div>

      <div id="logo-section">
        <button type="button" class="btn-cancel" style="width: 100%; margin-bottom: 12px; background: #6b7280; color: white;">Live Edit Logo Position</button>

        @php
          $resStr = (string)($channel->resolution ?? '1280x720');
          $rw = 1280; $rh = 720;
          if (preg_match('/(\d{3,5})\s*[xX]\s*(\d{3,5})/', $resStr, $m)) {
            $rw = max(1, (int)$m[1]);
            $rh = max(1, (int)$m[2]);
          }
          // Recommended logo sizes by common output resolution.
          $recLogoW = 128; $recLogoH = 40;
          if ($rw >= 1900 && $rh >= 1000) { $recLogoW = 192; $recLogoH = 60; }
          if ($rw >= 2500 && $rh >= 1300) { $recLogoW = 256; $recLogoH = 80; }
          if ($rw >= 3800 && $rh >= 2000) { $recLogoW = 384; $recLogoH = 120; }

          $lp = strtolower((string)($channel->overlay_logo_position ?? 'TL'));

          $logoWVal = (int)($channel->overlay_logo_width ?? 0);
          $logoHVal = (int)($channel->overlay_logo_height ?? 0);
          if ($logoWVal <= 0) $logoWVal = $recLogoW;
          if ($logoHVal <= 0) $logoHVal = $recLogoH;
        @endphp

        <div class="grid-3">
          <div class="form-group">
            <label>Logo Position</label>
            <select name="logo_position" id="logo_position">
              <option value="tl" @selected($lp === 'tl')>Top Left</option>
              <option value="tr" @selected($lp === 'tr')>Top Right</option>
              <option value="bl" @selected($lp === 'bl')>Bottom Left</option>
              <option value="br" @selected($lp === 'br')>Bottom Right</option>
            </select>
          </div>
          <div class="form-group">
            <label>Logo Position: X</label>
            <input type="number" name="logo_x" id="logo_x" value="{{ (int)($channel->overlay_logo_x ?? 20) }}">
          </div>
          <div class="form-group">
            <label>Logo Position: Y</label>
            <input type="number" name="logo_y" id="logo_y" value="{{ (int)($channel->overlay_logo_y ?? 20) }}">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Logo Width <span style="font-size: 11px; color: #6b7280;">| Logo 1200x375</span></label>
            <input type="number" name="logo_width" id="logo_width" value="{{ (int)($logoWVal ?? 128) }}">
          </div>
          <div class="form-group">
            <label>Logo Height</label>
            <input type="number" name="logo_height" id="logo_height" value="{{ (int)($logoHVal ?? 40) }}">
          </div>
        </div>

        <div style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">
          <strong>Original Logo Size:</strong> 1200 x 375
        </div>

        <div class="form-group">
          <label>Logo Opacity</label>
          <div class="slider-wrap">
            @php
              $lop = (float)($channel->overlay_logo_opacity ?? 80);
              $lop01 = $lop > 1 ? $lop/100 : $lop;
            @endphp
            <input type="range" name="logo_opacity" id="logo_opacity" min="0" max="1" step="0.01" value="{{ number_format($lop01, 2, '.', '') }}">
            <div class="slider-val" id="logo_opacity_val">{{ number_format($lop01, 2, '.', '') }}</div>
          </div>
        </div>

        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
          <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Recommended Logo Sizes: 1080p: 192x60, 720p: 128x40</div>
          
          <table class="recommended-table">
            <thead>
              <tr>
                <th>Video Size</th>
                <th>Logo Width</th>
                <th>Logo Height</th>
              </tr>
            </thead>
            <tbody>
              <tr data-width="384" data-height="120">
                <td>2160p: 3840x2160</td>
                <td>384</td>
                <td>120</td>
              </tr>
              <tr data-width="256" data-height="80">
                <td>1440p: 2560x1440</td>
                <td>256</td>
                <td>80</td>
              </tr>
              <tr data-width="192" data-height="60">
                <td>1080p: 1920x1080</td>
                <td>192</td>
                <td>60</td>
              </tr>
              <tr data-width="128" data-height="40" class="highlight">
                <td>720p: 1280x720</td>
                <td>128</td>
                <td>40</td>
              </tr>
              <tr data-width="85" data-height="27">
                <td>480p: 854x480</td>
                <td>85</td>
                <td>27</td>
              </tr>
              <tr data-width="64" data-height="20">
                <td>360p: 640x360</td>
                <td>64</td>
                <td>20</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TEXT OVERLAY -->
      <div class="card-title" style="margin-top: 16px;">📝 Text Overlay</div>

      <div class="form-check">
        <input type="checkbox" name="text_enabled" id="text_enabled" @checked((bool)($channel->overlay_text_enabled ?? true))>
        <label for="text_enabled">Text Overlay</label>
      </div>

      <div id="text-section">
        <div class="form-group">
          <label>Text Type</label>
          <select name="text_type" id="text_type">
            @php
              $tc = (string)($channel->overlay_text_content ?? 'title');
            @endphp
            <option value="title" @selected($tc === 'title')>VOD Name (Title)</option>
            <option value="custom" @selected($tc === 'custom')>Custom Text</option>
            <option value="channel_name" @selected($tc === 'channel_name')>Channel Name</option>
          </select>
        </div>

        <div class="form-group">
          <label>Text</label>
          <input type="text" name="text_value" id="text_value" placeholder="Enter overlay text" maxlength="100" value="{{ (string)($channel->overlay_text_custom ?? '') }}">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Font</label>
            <select name="text_font" id="text_font">
              @php
                $tf = (string)($channel->overlay_text_font_family ?? 'Ubuntu');
              @endphp
              <option value="Ubuntu" @selected($tf === 'Ubuntu')>Ubuntu</option>
              <option value="Arial" @selected($tf === 'Arial')>Arial</option>
              <option value="DejaVuSans" @selected($tf === 'DejaVuSans')>DejaVu Sans</option>
              <option value="Helvetica" @selected($tf === 'Helvetica')>Helvetica</option>
              <option value="Courier" @selected($tf === 'Courier')>Courier</option>
              <option value="Times" @selected($tf === 'Times')>Times New Roman</option>
            </select>
          </div>
          <div class="form-group">
            <label>Font Size</label>
            <input type="number" name="text_size" id="text_size" value="{{ max(8, (int)($channel->overlay_text_font_size ?? 24)) }}" min="8" max="72">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Font Color</label>
            <select name="text_color" id="text_color">
              @php
                $tcol = strtolower((string)($channel->overlay_text_color ?? 'white'));
              @endphp
              <option value="white" @selected($tcol === 'white' || $tcol === '#ffffff')>White</option>
              <option value="black" @selected($tcol === 'black' || $tcol === '#000000')>Black</option>
              <option value="yellow" @selected($tcol === 'yellow')>Yellow</option>
            </select>
          </div>
          <div class="form-group">
            <label>Text Position</label>
            <select name="text_position" id="text_position">
              @php
                $tp = strtolower((string)($channel->overlay_text_position ?? 'BR'));
              @endphp
              <option value="br" @selected($tp === 'br')>Bottom Right</option>
              <option value="bl" @selected($tp === 'bl')>Bottom Left</option>
              <option value="tr" @selected($tp === 'tr')>Top Right</option>
              <option value="tl" @selected($tp === 'tl')>Top Left</option>
            </select>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>X</label>
            <input type="number" name="text_x" id="text_x" value="{{ (int)($channel->overlay_text_x ?? 30) }}">
          </div>
          <div class="form-group">
            <label>Y</label>
            <input type="number" name="text_y" id="text_y" value="{{ (int)($channel->overlay_text_y ?? 30) }}">
          </div>
        </div>

        <div class="form-group">
          <label>Text Opacity</label>
          <div class="slider-wrap">
            @php
              $top = (float)($channel->overlay_text_opacity ?? 100);
              $top01 = $top > 1 ? $top/100 : $top;
            @endphp
            <input type="range" name="text_opacity" id="text_opacity" min="0" max="1" step="0.01" value="{{ number_format($top01, 2, '.', '') }}">
            <div class="slider-val" id="text_opacity_val">{{ number_format($top01, 2, '.', '') }}</div>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Text Background</label>
            <select name="text_background" id="text_background">
              <option value="disabled" selected>Disabled</option>
              <option value="enabled">Enabled</option>
            </select>
          </div>
          <div class="form-group">
            <label>Box Color</label>
            <select name="text_box_color" id="text_box_color">
              <option value="black" selected>Black</option>
              <option value="white">White</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Padding</label>
          <input type="number" name="text_padding" id="text_padding" value="5" min="0" max="20">
        </div>
      </div>

      <!-- COUNTDOWN / TIMER -->
      <div class="card-title" style="margin-top: 16px;">⏳ Countdown</div>

      <div class="form-check">
        <input type="checkbox" name="timer_enabled" id="timer_enabled" @checked((bool)($channel->overlay_timer_enabled ?? true))>
        <label for="timer_enabled">Show Countdown (HH:MM:SS)</label>
      </div>

      <div id="timer-section">
        <div class="grid-2">
          <div class="form-group">
            <label>Countdown Position</label>
            <select name="timer_position" id="timer_position">
              @php
                $cp = strtolower((string)($channel->overlay_timer_position ?? 'BR'));
              @endphp
              <option value="br" @selected($cp === 'br')>Bottom Right</option>
              <option value="bl" @selected($cp === 'bl')>Bottom Left</option>
              <option value="tr" @selected($cp === 'tr')>Top Right</option>
              <option value="tl" @selected($cp === 'tl')>Top Left</option>
              <option value="custom" @selected($cp === 'custom')>Custom</option>
            </select>
          </div>
          <div class="form-group">
            <label>Countdown Size</label>
            <input type="number" name="timer_size" id="timer_size" value="{{ max(8, (int)($channel->overlay_timer_font_size ?? 24)) }}" min="8" max="96">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>X</label>
            <input type="number" name="timer_x" id="timer_x" value="{{ (int)($channel->overlay_timer_x ?? 30) }}">
          </div>
          <div class="form-group">
            <label>Y</label>
            <input type="number" name="timer_y" id="timer_y" value="{{ (int)($channel->overlay_timer_y ?? 30) }}">
          </div>
        </div>
      </div>

      <!-- BUTTONS -->
      <div class="button-group">
        <button type="button" class="btn-cancel" id="btn-cancel">Cancel</button>
        <button type="button" class="btn-cancel" id="btn-save-channel">💾 Save to Channel</button>
        <button type="button" class="btn-primary" id="btn-create">Create Video (TEST)</button>
      </div>
    </form>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="right-column">
    <!-- CHANNEL HEADER -->
    <div class="card">
      <div class="channel-banner" style="margin-bottom: 14px;">
        <div class="channel-logo">
          <img
            src="{{ route('vod-channels.logo.preview', $channel) }}"
            alt=""
            onerror="this.style.display='none'"
          />
          <span class="channel-icon" style="display:none;">📺</span>
        </div>
        <div>
          <h2 class="channel-title">{{ $channel->name }}</h2>
          <div class="channel-subtitle">Resolution: {{ $channel->resolution ?? '1280x720' }}</div>
        </div>
      </div>

      <div class="pickers" data-create-video-base="{{ url('/create-video') }}">
        <div>
          <label class="picker-label" for="channel_switch">Channel</label>
          <select id="channel_switch" class="picker-select">
            @foreach(($channels ?? []) as $ch)
              <option value="{{ $ch->id }}" @selected((int)$ch->id === (int)$channel->id)>
                {{ $ch->name }}@if((int)$ch->id === (int)$channel->id) (current)@endif
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="picker-label" for="category_id">Video Category</label>
          @php
            $selectedCategoryId = old('video_category_id', $channel->video_category_id ?? null);
          @endphp
          <select id="category_id" class="picker-select" data-default-category-id="{{ (int)($selectedCategoryId ?? 0) }}">
            <option value="">Select Category</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected((int)$cat->id === (int)($selectedCategoryId ?? 0))>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <!-- VIDEOS TABLE -->
    <div class="card">
      <div class="card-title">📹 Videos</div>

      <div class="show-entries">
        <span>Show</span>
        <select id="pageLength">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span>entries</span>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 36px;"><input type="checkbox" id="selectAllVideos"></th>
            <th style="width:80px;">Poster</th>
            <th>Name</th>
            <th>Server</th>
            <th>Screen</th>
            <th>Size</th>
            <th>Format</th>
            <th style="width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody id="videosList">
          <tr><td colspan="8" class="no-data">Select a category to load videos</td></tr>
        </tbody>
      </table>
    </div>

    <!-- TEST VIDEO TABLE -->
    <div class="card">
      <div class="card-title">🎬 Test Video ({{ $channel->name }})</div>

      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Text</th>
            <th>Codec</th>
            <th>Bitrate</th>
            <th>Status</th>
            <th style="width: 100px;">Actions</th>
          </tr>
        </thead>
        <tbody id="testVideoList">
          <tr><td colspan="6" class="no-data">No jobs yet</td></tr>
        </tbody>
      </table>

      <div class="bulk-actions">
        <button type="button" class="btn-success" id="convertAllBtn">✓ Convert All Videos</button>
        <button type="button" class="btn-success" id="encodeSelectedFromTestsBtn">✓ Encode Selected</button>
        <button type="button" class="btn-danger" id="deleteAllBtn">✕ Delete All Videos</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script>
let selectedVideo = null;
let selectedVideos = new Map();

document.addEventListener('DOMContentLoaded', function() {
  const CHANNEL_ID = {{ (int) $channel->id }};
  const ENCODING_NOW_URL = @json(route('vod-channels.encoding-now', $channel));
  let lastFetchedVideos = [];
  let currentCategoryId = '';

  const LOCAL_KEY = `create_video_defaults_channel_${CHANNEL_ID}`;

  function getPreviewSsForVideo(v) {
    const dur = parseInt(v?.duration_seconds || 0, 10) || 0;
    if (dur > 0) {
      const ss = Math.round(dur * 0.10);
      return Math.max(2, Math.min(600, Math.min(ss, Math.max(0, dur - 2))));
    }
    return 30;
  }

  function applyUiDefaultsFromLocalStorage() {
    try {
      const raw = localStorage.getItem(LOCAL_KEY);
      if (!raw) return;
      const saved = JSON.parse(raw);
      if (!saved || typeof saved !== 'object') return;

      const fields = ['encoder', 'video_codec', 'preset', 'tune', 'crf_mode', 'crf_value', 'frame_rate'];
      fields.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const v = saved[id];
        if (v === undefined || v === null) return;
        el.value = String(v);
      });

      const crfModeEl = document.getElementById('crf_mode');
      const crfValEl = document.getElementById('crf_value');
      if (crfModeEl && crfValEl) {
        crfValEl.classList.toggle('hidden', crfModeEl.value !== 'manual');
      }
    } catch (e) {
      // ignore
    }
  }

  function persistUiDefaultsToLocalStorage() {
    try {
      localStorage.setItem(LOCAL_KEY, JSON.stringify({
        encoder: document.getElementById('encoder')?.value || 'libx264',
        video_codec: document.getElementById('video_codec')?.value || 'h264',
        preset: document.getElementById('preset')?.value || 'fast',
        tune: document.getElementById('tune')?.value || 'film',
        crf_mode: document.getElementById('crf_mode')?.value || 'auto',
        crf_value: document.getElementById('crf_value')?.value || '23',
        frame_rate: document.getElementById('frame_rate')?.value || '30',
      }));
    } catch (e) {
      // ignore
    }
  }

  (function syncOverlayPreviewAspectRatio() {
    const setFromResolution = (el) => {
      if (!el) return;
      const raw = String(el.getAttribute('data-channel-resolution') || '').trim();
      const m = raw.match(/^(\d{2,5})\s*x\s*(\d{2,5})$/i);
      if (!m) return;
      const w = parseInt(m[1], 10);
      const h = parseInt(m[2], 10);
      if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) return;
      el.style.aspectRatio = `${w} / ${h}`;
    };

    setFromResolution(document.getElementById('overlayPreview'));
    setFromResolution(document.getElementById('overlayModalPreview'));
  })();

  // By default, keep countdown anchored to the same corner/X/Y as the VOD title.
  // If the user edits countdown controls manually, we stop auto-syncing.
  let countdownManuallySet = false;

  // Make sure preview shows all elements by default.
  // User can still uncheck them afterwards.
  (function ensurePreviewElementsEnabled() {
    const logoCb = document.getElementById('logo_enabled');
    const textCb = document.getElementById('text_enabled');
    const timerCb = document.getElementById('timer_enabled');

    // Default preview text type to VOD title.
    const textTypeEl = document.getElementById('text_type');
    if (textTypeEl && String(textTypeEl.value || '').toLowerCase() !== 'title') {
      textTypeEl.value = 'title';
    }

    if (logoCb && !logoCb.checked) logoCb.checked = true;
    if (textCb && !textCb.checked) textCb.checked = true;
    if (timerCb && !timerCb.checked) timerCb.checked = true;

    const logoSection = document.getElementById('logo-section');
    const textSection = document.getElementById('text-section');
    const timerSection = document.getElementById('timer-section');
    if (logoSection && logoCb) logoSection.style.display = logoCb.checked ? 'block' : 'none';
    if (textSection && textCb) textSection.style.display = textCb.checked ? 'block' : 'none';
    if (timerSection && timerCb) timerSection.style.display = timerCb.checked ? 'block' : 'none';
  })();

  function syncCountdownToTextIfAllowed() {
    if (countdownManuallySet) return;

    const textPos = document.getElementById('text_position');
    const textX = document.getElementById('text_x');
    const textY = document.getElementById('text_y');

    const timerPos = document.getElementById('timer_position');
    const timerX = document.getElementById('timer_x');
    const timerY = document.getElementById('timer_y');

    if (textPos && timerPos) timerPos.value = textPos.value;
    if (textX && timerX) timerX.value = textX.value;
    if (textY && timerY) timerY.value = textY.value;
  }

  // Initial sync so countdown starts in the expected spot (bottom-right above title).
  syncCountdownToTextIfAllowed();

  function getVideoFromCache(videoId) {
    const id = parseInt(videoId, 10);
    if (!Number.isFinite(id) || id <= 0) return null;
    if (selectedVideo && parseInt(selectedVideo.id, 10) === id) return selectedVideo;
    if (selectedVideos.has(id)) return selectedVideos.get(id);
    if (Array.isArray(lastFetchedVideos)) {
      const v = lastFetchedVideos.find(x => parseInt(x.id, 10) === id);
      if (v) return v;
    }
    return null;
  }

  function renderSelectedList() {
    const listEl = document.getElementById('selected-list');
    const titleEl = document.getElementById('selected-title');
    const metaEl = document.getElementById('selected-meta');
    const countEl = document.getElementById('selected-count');
    const videoIdEl = document.getElementById('video_id');
    const infoDetailsEl = document.getElementById('info-details');

    const ids = Array.from(selectedVideos.keys());
    if (countEl) countEl.textContent = `${ids.length} selected`;

    if (ids.length === 0) {
      if (titleEl) titleEl.textContent = 'ℹ️ Please Select Video';
      if (metaEl) metaEl.textContent = 'Choose one or more videos from the table on the right';
      if (listEl) listEl.innerHTML = '';
      if (videoIdEl) videoIdEl.value = '';
      if (infoDetailsEl) infoDetailsEl.classList.remove('visible');
      return;
    }

    if (ids.length === 1) {
      const v = selectedVideos.get(ids[0]);
      const t = (v && v.title) ? v.title : ('Video #' + ids[0]);
      if (titleEl) titleEl.textContent = '✅ ' + t;
      if (metaEl) metaEl.textContent = 'Selected video is ready to encode.';
    } else {
      if (titleEl) titleEl.textContent = '✅ Selected Videos';
      if (metaEl) metaEl.textContent = `Ready to encode ${ids.length} videos.`;
    }

    if (videoIdEl) videoIdEl.value = String(ids[0] || '');

    if (listEl) {
      listEl.innerHTML = ids.map(id => {
        const v = selectedVideos.get(id);
        const t = (v && v.title) ? v.title : ('Video #' + id);
        const safeTitle = String(t).replace(/"/g, '&quot;');
        return `
          <div class="selected-item" data-selected-id="${id}">
            <div class="selected-item-title" title="${safeTitle}">${t}</div>
            <div class="selected-item-actions">
              <button type="button" class="btn-mini btn-mini-primary js-preview-selected" data-preview-id="${id}">Preview</button>
              <button type="button" class="btn-mini btn-mini-danger js-remove-selected" data-remove-id="${id}">Remove</button>
            </div>
          </div>
        `;
      }).join('');

      listEl.querySelectorAll('.js-remove-selected').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = parseInt(btn.getAttribute('data-remove-id') || '0', 10);
          if (!Number.isFinite(id) || id <= 0) return;
          removeVideoFromSelection(id);
        });
      });

      listEl.querySelectorAll('.js-preview-selected').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = parseInt(btn.getAttribute('data-preview-id') || '0', 10);
          if (!Number.isFinite(id) || id <= 0) return;
          openRealOverlayPreview(id);
        });
      });

    }

    // Keep visible checkboxes in sync.
    document.querySelectorAll('#videosList .video-checkbox').forEach(cb => {
      const id = parseInt(cb.value, 10);
      if (!Number.isFinite(id) || id <= 0) return;
      cb.checked = selectedVideos.has(id);
    });
  }

  function openRealOverlayPreview(videoId) {
    const modal = document.getElementById('vodPreviewModal');
    const img = document.getElementById('vodPreviewImg');
    const status = document.getElementById('vodPreviewStatus');
    const titleEl = document.getElementById('vodPreviewTitle');

    const v = getVideoFromCache(videoId);
    const title = (v && v.title) ? v.title : ('Video #' + videoId);
    if (titleEl) titleEl.textContent = `Preview: ${title}`;

    if (status) status.textContent = 'Generating real FFmpeg preview…';
    if (img) {
      img.style.display = 'none';
      img.removeAttribute('src');
    }

    if (modal) modal.classList.add('open');

    window.__currentPreviewVideoId = parseInt(videoId || 0, 10) || 0;

    const csrfEl = document.querySelector('input[name="_token"]');
    const csrf = csrfEl ? csrfEl.value : '';

    const ss = getPreviewSsForVideo(v);

    fetch(`/api/videos/${videoId}/overlay-preview`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        live_channel_id: CHANNEL_ID,
        ss: ss,
        settings: buildSettingsFromForm(),
      }),
    })
      .then(async r => {
        const json = await r.json().catch(() => ({}));
        if (!r.ok || !json.ok) {
          throw new Error(json.message || 'Preview failed');
        }
        return json;
      })
      .then(json => {
        if (status) status.textContent = 'Preview ready.';
        if (img) {
          img.setAttribute('src', `${json.preview_url}?t=${Date.now()}`);
          img.style.display = 'block';
        }
      })
      .catch(err => {
        if (status) status.textContent = err?.message ? String(err.message) : 'Preview failed';
      });
  }

  (function wirePreviewModal() {
    const modal = document.getElementById('vodPreviewModal');
    const closeBtn = document.getElementById('vodPreviewCloseBtn');

    let refreshTimer = null;
    function refreshPreviewIfOpen() {
      if (!modal || !modal.classList.contains('open')) return;
      const id = parseInt(window.__currentPreviewVideoId || 0, 10) || 0;
      if (id <= 0) return;
      if (refreshTimer) clearTimeout(refreshTimer);
      refreshTimer = setTimeout(() => openRealOverlayPreview(id), 350);
    }

    function close() {
      if (!modal) return;
      modal.classList.remove('open');
      const img = document.getElementById('vodPreviewImg');
      if (img) {
        img.style.display = 'none';
        img.removeAttribute('src');
      }
      const status = document.getElementById('vodPreviewStatus');
      if (status) status.textContent = 'Ready.';
    }

    if (closeBtn) closeBtn.addEventListener('click', close);
    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
      });
    }

    // Auto-refresh the real FFmpeg preview when logo settings change.
    ['logo_enabled','logo_position','logo_x','logo_y','logo_width','logo_height','logo_opacity'].forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', refreshPreviewIfOpen);
      el.addEventListener('change', refreshPreviewIfOpen);
    });
  })();

  function addVideoToSelection(video) {
    const id = parseInt(video?.id ?? 0, 10);
    if (!Number.isFinite(id) || id <= 0) return;
    selectedVideos.set(id, video || { id });
    renderSelectedList();
  }

  function removeVideoFromSelection(videoId) {
    const id = parseInt(videoId, 10);
    if (!Number.isFinite(id) || id <= 0) return;
    selectedVideos.delete(id);
    if (selectedVideo && parseInt(selectedVideo.id, 10) === id) {
      selectedVideo = null;
    }

    const cb = document.querySelector(`#videosList .video-checkbox[value="${id}"]`);
    if (cb) cb.checked = false;

    renderSelectedList();
    updateOverlayPreview();
  }

  function clearSelection() {
    selectedVideos.clear();
    selectedVideo = null;
    document.querySelectorAll('#videosList .video-checkbox').forEach(cb => { cb.checked = false; });
    const selectAllEl = document.getElementById('selectAllVideos');
    if (selectAllEl) selectAllEl.checked = false;
    renderSelectedList();
    updateOverlayPreview();
  }

  function getSelectedLimit() {
    const pageLengthEl = document.getElementById('pageLength');
    const raw = (pageLengthEl && pageLengthEl.value) ? String(pageLengthEl.value) : '10';
    const limit = parseInt(raw, 10);
    if (Number.isFinite(limit) && limit > 0) return limit;
    return 10;
  }

  function loadVideosForCategory(categoryId) {
    currentCategoryId = String(categoryId || '');

    if (!currentCategoryId) {
      document.getElementById('videosList').innerHTML = '<tr><td colspan="8" class="no-data">Select a category to load videos</td></tr>';
      lastFetchedVideos = [];
      const selectAllEl = document.getElementById('selectAllVideos');
      if (selectAllEl) selectAllEl.checked = false;
      return;
    }

    const limit = getSelectedLimit();

    fetch(`/api/videos?category_id=${encodeURIComponent(currentCategoryId)}&channel_id=${encodeURIComponent(String(CHANNEL_ID))}&exclude_in_playlist=1&limit=${encodeURIComponent(String(limit))}`, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(r => r.json())
      .then(videos => {
        lastFetchedVideos = Array.isArray(videos) ? videos : [];
        renderVideosTable();
      })
      .catch(e => console.error('Error:', e));
  }

  function normalizeCornerPos(pos) {
    const p = String(pos || '').trim().toLowerCase();
    if (p === 'custom') return 'CUSTOM';
    if (p === 'tl') return 'TL';
    if (p === 'tr') return 'TR';
    if (p === 'bl') return 'BL';
    if (p === 'br') return 'BR';
    const up = String(pos || '').trim().toUpperCase();
    return up;
  }

  function buildSettingsFromForm() {
    const textMode = document.getElementById('text_type')?.value || 'title';

    // EncodingService expects ffmpeg encoder names (e.g. libx264/libx265).
    // The UI also has a "Video Codec" selector (h264/h265) but that is not a valid encoder name.
    const encoderSel = (document.getElementById('encoder')?.value || '').trim();
    const videoCodecSel = String(document.getElementById('video_codec')?.value || '').toLowerCase().trim();
    const encoderName = encoderSel !== ''
      ? encoderSel
      : (videoCodecSel === 'h265' ? 'libx265' : 'libx264');

    return {
      // EncodingService Create Video keys
      encoder: encoderName,
      preset: document.getElementById('preset')?.value || '',
      tune: document.getElementById('tune')?.value || '',
      video_bitrate: parseInt(document.getElementById('video_bitrate')?.value || '0', 10) || 0,
      audio_bitrate: parseInt(document.getElementById('audio_bitrate')?.value || '0', 10) || 0,
      frame_rate: document.getElementById('frame_rate')?.value || '',
      crf_mode: document.getElementById('crf_mode')?.value || 'auto',
      crf_value: parseInt(document.getElementById('crf_value')?.value || '0', 10) || 0,
      output_container: 'ts',

      // Overlay: logo
      overlay_logo_enabled: !!document.getElementById('logo_enabled')?.checked,
      overlay_logo_position: normalizeCornerPos(document.getElementById('logo_position')?.value || 'tl'),
      overlay_logo_x: parseInt(document.getElementById('logo_x')?.value || '0', 10) || 0,
      overlay_logo_y: parseInt(document.getElementById('logo_y')?.value || '0', 10) || 0,
      overlay_logo_width: parseInt(document.getElementById('logo_width')?.value || '0', 10) || 0,
      overlay_logo_height: parseInt(document.getElementById('logo_height')?.value || '0', 10) || 0,
      overlay_logo_opacity: Math.round((parseFloat(document.getElementById('logo_opacity')?.value || '1') || 1) * 100),

      // Overlay: text (VOD name)
      overlay_text_enabled: !!document.getElementById('text_enabled')?.checked,
      overlay_text_content: textMode,
      overlay_text_custom: (textMode === 'custom') ? (document.getElementById('text_value')?.value || '') : '',
      overlay_text_font_family: document.getElementById('text_font')?.value || 'Ubuntu',
      overlay_text_font_size: parseInt(document.getElementById('text_size')?.value || '0', 10) || 0,
      overlay_text_color: document.getElementById('text_color')?.value || 'white',
      overlay_text_position: normalizeCornerPos(document.getElementById('text_position')?.value || 'br'),
      overlay_text_x: parseInt(document.getElementById('text_x')?.value || '0', 10) || 0,
      overlay_text_y: parseInt(document.getElementById('text_y')?.value || '0', 10) || 0,
      overlay_text_opacity: Math.round((parseFloat(document.getElementById('text_opacity')?.value || '1') || 1) * 100),
      overlay_text_bg_color: (document.getElementById('text_box_color')?.value || 'black') === 'white' ? 'white' : 'black',
      overlay_text_bg_opacity: (document.getElementById('text_background')?.value || 'disabled') === 'enabled' ? 50 : 0,
      overlay_text_padding: parseInt(document.getElementById('text_padding')?.value || '0', 10) || 0,

      // Overlay: timer (countdown)
      overlay_timer_enabled: !!document.getElementById('timer_enabled')?.checked,
      overlay_timer_mode: 'countdown',
      overlay_timer_format: 'HH:mm:ss',
      overlay_timer_position: normalizeCornerPos(document.getElementById('timer_position')?.value || 'br'),
      overlay_timer_x: parseInt(document.getElementById('timer_x')?.value || '0', 10) || 0,
      overlay_timer_y: parseInt(document.getElementById('timer_y')?.value || '0', 10) || 0,
      overlay_timer_font_size: parseInt(document.getElementById('timer_size')?.value || '0', 10) || 0,
      overlay_timer_color: '#FFFFFF',
    };
  }

  function settingsForSaveToChannel() {
    const s = buildSettingsFromForm();
    const fps = parseInt(String(s.frame_rate || '').replace(/[^0-9]/g, ''), 10) || null;

    return {
      video_bitrate: parseInt(s.video_bitrate || 0, 10) || null,
      audio_bitrate: parseInt(s.audio_bitrate || 0, 10) || null,
      fps: fps,
      resolution: '{{ addslashes($channel->resolution ?? '') }}' || null,

      overlay_logo_enabled: !!s.overlay_logo_enabled,
      overlay_logo_position: s.overlay_logo_position,
      overlay_logo_x: parseInt(s.overlay_logo_x || 0, 10) || 0,
      overlay_logo_y: parseInt(s.overlay_logo_y || 0, 10) || 0,
      overlay_logo_width: parseInt(s.overlay_logo_width || 0, 10) || 0,
      overlay_logo_height: parseInt(s.overlay_logo_height || 0, 10) || 0,
      overlay_logo_opacity: Math.round((parseFloat(document.getElementById('logo_opacity')?.value || '1') || 1) * 100),

      overlay_text_enabled: !!s.overlay_text_enabled,
      overlay_text_content: s.overlay_text_content,
      overlay_text_custom: s.overlay_text_custom,
      overlay_text_font_family: s.overlay_text_font_family,
      overlay_text_font_size: parseInt(s.overlay_text_font_size || 0, 10) || 0,
      overlay_text_color: s.overlay_text_color,
      overlay_text_position: s.overlay_text_position,
      overlay_text_x: parseInt(s.overlay_text_x || 0, 10) || 0,
      overlay_text_y: parseInt(s.overlay_text_y || 0, 10) || 0,
      overlay_text_opacity: Math.round((parseFloat(document.getElementById('text_opacity')?.value || '1') || 1) * 100),
      overlay_text_bg_opacity: (document.getElementById('text_background')?.value || 'disabled') === 'enabled' ? 50 : 0,
      overlay_text_bg_color: (document.getElementById('text_box_color')?.value || 'black') === 'white' ? '#FFFFFF' : '#000000',
      overlay_text_padding: parseInt(s.overlay_text_padding || 0, 10) || 0,

      overlay_timer_enabled: !!s.overlay_timer_enabled,
      overlay_timer_mode: 'countdown',
      overlay_timer_format: 'HH:mm:ss',
      overlay_timer_position: s.overlay_timer_position,
      overlay_timer_x: parseInt(s.overlay_timer_x || 0, 10) || 0,
      overlay_timer_y: parseInt(s.overlay_timer_y || 0, 10) || 0,
      overlay_timer_font_size: parseInt(s.overlay_timer_font_size || 0, 10) || 0,
      overlay_timer_color: '#FFFFFF',
    };
  }

  function placePreviewEl(el, pos, x, y) {
    el.style.top = '';
    el.style.right = '';
    el.style.bottom = '';
    el.style.left = '';

    const p = normalizeCornerPos(pos);
    const rawX = Math.max(0, parseInt(x || 0, 10) || 0);
    const rawY = Math.max(0, parseInt(y || 0, 10) || 0);
    const pxX = `${rawX}px`;
    const pxY = `${rawY}px`;

    if (p === 'TR') { el.style.top = pxY; el.style.right = pxX; return; }
    if (p === 'BL') { el.style.bottom = pxY; el.style.left = pxX; return; }
    if (p === 'BR') { el.style.bottom = pxY; el.style.right = pxX; return; }
    if (p === 'CUSTOM') { el.style.left = pxX; el.style.top = pxY; return; }
    el.style.top = pxY;
    el.style.left = pxX;
  }

  function updateOverlayPreview() {
    // Disabled: the HTML preview was misleading vs. real FFmpeg output.
    return;
    function renderOverlayPreviewTo(previewEl, logoEl, textEl, timerEl) {
      if (!logoEl || !textEl || !timerEl || !previewEl) return;

      const s = buildSettingsFromForm();

      function getPreviewVideo() {
      if (selectedVideo && selectedVideo.title) return selectedVideo;

      const ids = Array.from(selectedVideos.keys());
      if (ids.length > 0) {
        const v = selectedVideos.get(ids[0]);
        if (v) return v;
      }

      // If user checked a row but didn't press "Select", still preview that VOD name.
      const checked = document.querySelector('#videosList .video-checkbox:checked');
      if (checked) {
        const id = parseInt(checked.value, 10);
        if (Number.isFinite(id) && Array.isArray(lastFetchedVideos)) {
          const v = lastFetchedVideos.find(x => parseInt(x.id, 10) === id);
          if (v) return v;
        }
      }

      return null;
    }

      const pv = getPreviewVideo();

      // IMPORTANT: keep preview sizes exactly as entered (no auto scaling).

      // Logo
      if (s.overlay_logo_enabled) {
        logoEl.style.display = '';
        const alpha = Math.max(0, Math.min(1, (parseFloat(s.overlay_logo_opacity ?? 100) || 100) / 100));
        logoEl.style.opacity = String(alpha);
        logoEl.style.width = `${Math.max(1, (parseInt(s.overlay_logo_width || 0, 10) || 1))}px`;
        logoEl.style.height = `${Math.max(1, (parseInt(s.overlay_logo_height || 0, 10) || 1))}px`;
        placePreviewEl(logoEl, s.overlay_logo_position, s.overlay_logo_x, s.overlay_logo_y);
      } else {
        logoEl.style.display = 'none';
      }

      // Text (VOD name)
      if (s.overlay_text_enabled) {
        textEl.style.display = '';
        const alpha = Math.max(0, Math.min(1, (parseFloat(s.overlay_text_opacity ?? 100) || 100) / 100));
        textEl.style.opacity = String(alpha);
        const titleFontPx = Math.max(1, (parseInt(s.overlay_text_font_size || 0, 10) || 16));
        textEl.style.fontSize = `${titleFontPx}px`;

        // Keep preview readable for long titles
        textEl.style.maxWidth = '72%';
        textEl.style.whiteSpace = 'normal';
        textEl.style.lineHeight = '1.15';

        // Apply styling from settings
        textEl.style.fontFamily = String(s.overlay_text_font_family || 'Ubuntu');
        textEl.style.color = String(s.overlay_text_color || 'white');

        const bgOpacity = Math.max(0, Math.min(100, parseInt(s.overlay_text_bg_opacity || 0, 10) || 0));
        const pad = Math.max(0, (parseInt(s.overlay_text_padding || 0, 10) || 0));
        if (bgOpacity > 0) {
          const a = bgOpacity / 100;
          const bg = String(s.overlay_text_bg_color || 'black').toLowerCase() === 'white'
            ? `rgba(255,255,255,${a})`
            : `rgba(0,0,0,${a})`;
          textEl.style.backgroundColor = bg;
          textEl.style.padding = `${pad}px`;
          textEl.style.borderRadius = '6px';
        } else {
          textEl.style.backgroundColor = 'transparent';
          textEl.style.padding = '0px';
          textEl.style.borderRadius = '0px';
        }

        let text = '';
        if (s.overlay_text_content === 'title') {
          text = pv?.title || 'Select a video';
        } else if (s.overlay_text_content === 'custom') {
          text = s.overlay_text_custom || 'Custom Text';
        } else if (s.overlay_text_content === 'channel_name') {
          text = '{{ addslashes($channel->name) }}';
        } else {
          text = pv?.title || 'Select a video';
        }
        textEl.textContent = text;

        placePreviewEl(textEl, s.overlay_text_position, s.overlay_text_x, s.overlay_text_y);
      } else {
        textEl.style.display = 'none';
      }

      // Timer
      if (s.overlay_timer_enabled) {
        timerEl.style.display = '';
        const timerFontPx = Math.max(1, (parseInt(s.overlay_timer_font_size || 0, 10) || 20));
        timerEl.style.fontSize = `${timerFontPx}px`;
        timerEl.style.fontFamily = String(s.overlay_text_font_family || 'Ubuntu');
        timerEl.style.color = '#FFFFFF';

        // Preview countdown: use video duration when available, otherwise 00:10:00
        const dur = parseInt(pv?.duration_seconds || pv?.duration || '0', 10) || 0;
        if (dur > 0) {
          const h = String(Math.floor(dur / 3600)).padStart(2, '0');
          const m = String(Math.floor((dur % 3600) / 60)).padStart(2, '0');
          const sec = String(Math.floor(dur % 60)).padStart(2, '0');
          timerEl.textContent = `${h}:${m}:${sec}`;
        } else {
          timerEl.textContent = '00:10:00';
        }

        // Default placement
        placePreviewEl(timerEl, s.overlay_timer_position, s.overlay_timer_x, s.overlay_timer_y);

        // Stack rule for preview: keep BOTH inside the same marked corner.
        // Bottom corners: VOD title at the bottom, countdown ABOVE it.
        // Top corners: title at the top, countdown BELOW it.
        const tPos = normalizeCornerPos(s.overlay_text_position);
        const cPos = normalizeCornerPos(s.overlay_timer_position);
        const sameCorner = (tPos === cPos) && ['TL', 'TR', 'BL', 'BR'].includes(cPos);

        if (s.overlay_text_enabled && sameCorner) {
          const gap = 6;
          const xBase = parseInt(s.overlay_text_x || 0, 10) || 0;
          const yBase = parseInt(s.overlay_text_y || 0, 10) || 0;

          // Force both to the same corner anchor in preview (using text X/Y as base)
          placePreviewEl(textEl, tPos, xBase, yBase);
          placePreviewEl(timerEl, tPos, xBase, yBase);

          // Measure after placement
          const titleH = Math.ceil(textEl.getBoundingClientRect().height || 0);

          if (tPos === 'BR' || tPos === 'BL') {
            const baseBottom = Math.max(0, yBase);
            textEl.style.bottom = `${baseBottom}px`;
            timerEl.style.bottom = `${baseBottom + titleH + gap}px`;
          } else if (tPos === 'TR' || tPos === 'TL') {
            const baseTop = Math.max(0, yBase);
            textEl.style.top = `${baseTop}px`;
            timerEl.style.top = `${baseTop + titleH + gap}px`;
          }
        }
      } else {
        timerEl.style.display = 'none';
      }
    }

    const logoEl = document.getElementById('overlayPreviewLogo');
    const textEl = document.getElementById('overlayPreviewText');
    const timerEl = document.getElementById('overlayPreviewTimer');
    const previewEl = document.getElementById('overlayPreview');
    renderOverlayPreviewTo(previewEl, logoEl, textEl, timerEl);

    // If modal is open, keep its overlay in sync too.
    const modalEl = document.getElementById('vodPreviewModal');
    if (modalEl && modalEl.classList.contains('open')) {
      renderOverlayPreviewTo(
        document.getElementById('overlayModalPreview'),
        document.getElementById('overlayModalLogo'),
        document.getElementById('overlayModalText'),
        document.getElementById('overlayModalTimer')
      );
    }
  }

  function renderVideosTable() {
    const tbody = document.getElementById('videosList');
    const pageLengthEl = document.getElementById('pageLength');
    const selectAllEl = document.getElementById('selectAllVideos');

    const limit = getSelectedLimit();
    const videos = (Array.isArray(lastFetchedVideos) ? lastFetchedVideos : []).slice(0, limit);

    if (!videos.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="no-data">No videos in this category</td></tr>';
      if (selectAllEl) selectAllEl.checked = false;
      return;
    }

    let html = '';
    videos.forEach(v => {
      const size = v.size_bytes ? `${(v.size_bytes / (1024*1024)).toFixed(0)} MB` : '—';
      const resolution = v.resolution || '—';
      const format = (v.format || 'mp4').toUpperCase();
      const videoData = JSON.stringify(v).replace(/"/g, '&quot;');
      const checkedAttr = selectedVideos.has(parseInt(v.id, 10)) ? 'checked' : '';

      const ss = getPreviewSsForVideo(v);
      const thumbUrl = `/api/videos/${v.id}/preview-frame?ss=${encodeURIComponent(String(ss))}`;
      const posterPath = (v && v.tmdb_poster_path) ? String(v.tmdb_poster_path) : '';
      const tmdbUrl = posterPath ? `https://image.tmdb.org/t/p/w92${posterPath}` : '';

      const posterHtml = `
        <img
          src="${thumbUrl}"
          alt="Preview"
          style="width:64px;height:36px;border-radius:6px;border:1px solid var(--border-color);display:inline-block;object-fit:cover;background:#111827;"
          loading="lazy"
          onerror="${tmdbUrl ? `this.onerror=null;this.src='${tmdbUrl}';this.style.width='46px';this.style.height='auto';this.style.objectFit='contain';` : `this.style.display='none';` }"
        >
      `;

      html += `
        <tr>
          <td><input type="checkbox" class="video-checkbox" value="${v.id}" ${checkedAttr}></td>
          <td style="text-align:center;">${posterHtml}</td>
          <td><strong>${v.title}</strong></td>
          <td>${v.server || 'primary'}</td>
          <td>${resolution}</td>
          <td>${size}</td>
          <td>${format}</td>
          <td>
            <div class="table-actions">
              <button type="button" class="btn-cancel btn-select" data-video="${videoData}">Select</button>
              <button type="button" class="btn-cancel btn-watch" data-video-id="${v.id}" data-video-title="${v.title}">Watch</button>
              <button type="button" class="btn-danger btn-delete" data-video-id="${v.id}">Delete</button>
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    if (selectAllEl) selectAllEl.checked = false;

    document.querySelectorAll('.btn-select').forEach(btn => {
      btn.addEventListener('click', function() {
        const videoData = JSON.parse(this.getAttribute('data-video'));
        selectVideo(videoData);
      });
    });

    document.querySelectorAll('.btn-watch').forEach(btn => {
      btn.addEventListener('click', function() {
        const videoId = this.getAttribute('data-video-id');
        const videoTitle = this.getAttribute('data-video-title');
        watchVideo(videoId, videoTitle);
      });
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', function() {
        const videoId = this.getAttribute('data-video-id');
        deleteVideo(videoId);
      });
    });

    renderSelectedList();
  }

  // CRF toggle
  document.getElementById('crf_mode').addEventListener('change', function() {
    document.getElementById('crf_value').classList.toggle('hidden', this.value !== 'manual');
  });

  // Logo toggle
  document.getElementById('logo_enabled').addEventListener('change', function() {
    document.getElementById('logo-section').style.display = this.checked ? 'block' : 'none';
  });

  // Text toggle
  document.getElementById('text_enabled').addEventListener('change', function() {
    document.getElementById('text-section').style.display = this.checked ? 'block' : 'none';
  });

  // If the user changes text position/X/Y, keep countdown aligned (unless user overrides countdown manually).
  ['text_position', 'text_x', 'text_y'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      syncCountdownToTextIfAllowed();
      updateOverlayPreview();
    });
  });

  // Any manual change in countdown controls disables auto-sync.
  ['timer_position', 'timer_x', 'timer_y'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      countdownManuallySet = true;
      updateOverlayPreview();
    });
  });

  // Sliders
  document.getElementById('logo_opacity').addEventListener('input', function() {
    document.getElementById('logo_opacity_val').textContent = parseFloat(this.value).toFixed(2);
  });

  document.getElementById('text_opacity').addEventListener('input', function() {
    document.getElementById('text_opacity_val').textContent = parseFloat(this.value).toFixed(2);
  });

  // Recommended sizes click
  document.querySelectorAll('.recommended-table tbody tr').forEach(row => {
    row.addEventListener('click', function() {
      document.getElementById('logo_width').value = this.dataset.width;
      document.getElementById('logo_height').value = this.dataset.height;
    });
  });

  // Category change
  document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    loadVideosForCategory(categoryId);
  });

  // Page length change
  const pageLengthEl = document.getElementById('pageLength');
  if (pageLengthEl) {
    pageLengthEl.addEventListener('change', function() {
      // Re-load with limit so it truly shows exactly that many rows.
      if (currentCategoryId) {
        loadVideosForCategory(currentCategoryId);
      } else {
        renderVideosTable();
      }
    });
  }

  // Select all visible videos
  const selectAllEl = document.getElementById('selectAllVideos');
  if (selectAllEl) {
    selectAllEl.addEventListener('change', function() {
      document.querySelectorAll('#videosList .video-checkbox').forEach(cb => {
        cb.checked = selectAllEl.checked;

        const id = parseInt(cb.value, 10);
        if (!Number.isFinite(id) || id <= 0) return;

        if (cb.checked) {
          const v = getVideoFromCache(id) || { id };
          selectedVideos.set(id, v);
        } else {
          selectedVideos.delete(id);
          if (selectedVideo && parseInt(selectedVideo.id, 10) === id) selectedVideo = null;
        }
      });

      renderSelectedList();
      updateOverlayPreview();
    });
  }

  const clearBtn = document.getElementById('clearSelectedBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      if (selectedVideos.size === 0) return;
      if (!confirm('Clear selected videos?')) return;
      clearSelection();
    });
  }

  // Channel switch (navigate to selected channel page)
  function bindChannelSwitch(selectId) {
    const el = document.getElementById(selectId);
    if (!el) return;
    el.addEventListener('change', function () {
      const base = (document.querySelector('[data-create-video-base]')?.getAttribute('data-create-video-base')) || '/create-video';
      const id = this.value;
      if (!id) return;
      window.location.href = `${base}/${id}`;
    });
  }

  bindChannelSwitch('channel_switch');
  bindChannelSwitch('channel_switch_left');

  // Select video
  window.selectVideo = function(video) {
    selectedVideo = video;
    addVideoToSelection(video);
    
    const duration = video.duration_seconds ? `${Math.floor(video.duration_seconds / 60)}:${String(video.duration_seconds % 60).padStart(2, '0')}` : '—';
    const size = video.size_bytes ? `${(video.size_bytes / (1024*1024*1024)).toFixed(2)} GB` : '—';

    document.getElementById('detail-duration').textContent = duration;
    document.getElementById('detail-resolution').textContent = video.resolution || '—';
    document.getElementById('detail-format').textContent = (video.format || 'mp4').toUpperCase();
    document.getElementById('detail-size').textContent = size;
    document.getElementById('info-details').classList.add('visible');

    console.log('Video selected:', video);
    updateOverlayPreview();
  };

  // Live preview updates on any settings change
  document.querySelectorAll('#createVideoForm input, #createVideoForm select, #createVideoForm textarea')
    .forEach(el => el.addEventListener('input', updateOverlayPreview));

  // Preview should also update when user checks/unchecks videos in the table.
  const videosListEl = document.getElementById('videosList');
  if (videosListEl) {
    videosListEl.addEventListener('change', (e) => {
      const target = e.target;
      if (target && target.classList && target.classList.contains('video-checkbox')) {
        const id = parseInt(target.value, 10);
        if (Number.isFinite(id) && id > 0) {
          if (target.checked) {
            const v = getVideoFromCache(id) || { id };
            addVideoToSelection(v);
          } else {
            removeVideoFromSelection(id);
          }
        }
        updateOverlayPreview();
      }
    });
  }

  updateOverlayPreview();

  // Watch video
  window.watchVideo = function(videoId, videoTitle) {
    alert(`▶️ Watch: ${videoTitle}\n(Player would open here)`);
    // TODO: Implement video player modal
  };

  // Delete video
  window.deleteVideo = function(videoId) {
    if (!confirm('Delete this video?')) return;
    alert('🗑️ Delete video #' + videoId + '\n(Backend implementation needed)');
    // TODO: Implement delete via API
  };

  // Create video
  document.getElementById('createVideoForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // IMPORTANT: "Create Video" = TEST ONLY (HLS, time-limited). No production TS encoding here.
    let videoIds = Array.from(selectedVideos.keys());
    if (videoIds.length === 0) {
      // Fallback: allow selecting from the videos list checkboxes.
      videoIds = Array.from(document.querySelectorAll('#videosList .video-checkbox:checked'))
        .map(cb => parseInt(cb.value, 10))
        .filter(v => Number.isFinite(v) && v > 0);
    }

    videoIds = Array.from(new Set(videoIds.map(v => parseInt(v, 10)).filter(v => Number.isFinite(v) && v > 0)));

    if (videoIds.length === 0) {
      alert('❌ Selectează cel puțin un video ca să rulezi TEST-ul');
      return;
    }

    const csrf = document.querySelector('input[name="_token"]').value;
    const durRaw = parseInt(document.getElementById('test_time_limit')?.value || '60', 10);
    const startRaw = parseInt(document.getElementById('test_start_time')?.value || '0', 10);

    let testDuration = Number.isFinite(durRaw) ? durRaw : 60;
    if (testDuration < 5) testDuration = 5;
    if (testDuration > 60) testDuration = 60;

    let testStart = Number.isFinite(startRaw) ? startRaw : 0;
    if (testStart < 0) testStart = 0;

    const settings = buildSettingsFromForm();
    openTestModal('Test Encode', `Queueing ${videoIds.length} test(s)…`);

    (async () => {
      let okCount = 0;
      let failCount = 0;
      let lastOk = null;

      for (let i = 0; i < videoIds.length; i++) {
        const videoId = videoIds[i];

        try {
          const r = await fetch('/api/encoding-jobs/test-from-video', {
            method: 'POST',
            credentials: 'include',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              live_channel_id: CHANNEL_ID,
              video_id: videoId,
              test_duration: testDuration,
              test_start: testStart,
              settings,
            })
          });

          const data = await r.json().catch(() => ({}));
          if (!r.ok || !data.ok) {
            throw new Error(data.message || 'Failed to start test');
          }

          okCount++;
          lastOk = data;
          activeTestJobId = data.test_job_id || activeTestJobId || null;
        } catch (err) {
          failCount++;
          console.error('Test queue failed for video', videoId, err);
        }
      }

      startJobsPolling();
      loadTestJobs();

      // For single selection, keep the old convenience: auto-play if test is instantly done.
      if (videoIds.length === 1 && lastOk && lastOk.status && String(lastOk.status).toLowerCase() === 'done' && lastOk.output_url) {
        playHlsUrl(lastOk.output_url);
      }

      closeTestModal();
      if (failCount > 0) {
        alert(`⚠️ Queued ${okCount} test(s), failed ${failCount}`);
      } else {
        alert(`✅ Queued ${okCount} test(s)`);
      }
    })();
  });

  // Create Video (TEST) button triggers the same handler (no browser submit)
  document.getElementById('btn-create')?.addEventListener('click', function() {
    document.getElementById('createVideoForm')?.dispatchEvent(new Event('submit', { cancelable: true }));
  });

  // Save defaults to channel
  const saveBtn = document.getElementById('btn-save-channel');
  if (saveBtn) {
    saveBtn.addEventListener('click', function() {
      if (!confirm('Save these settings as default for this channel?')) return;

      fetch(`/api/live-channels/${CHANNEL_ID}/settings`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(settingsForSaveToChannel())
      })
        .then(async (r) => {
          const data = await r.json().catch(() => ({}));
          if (!r.ok) throw new Error(data.message || 'Save failed');
          return data;
        })
        .then(() => {
          // The API stores bitrate/fps/overlay defaults; persist encoder-side dropdowns locally.
          persistUiDefaultsToLocalStorage();
          alert('✅ Saved to channel');
        })
        .catch(e => alert('❌ Error: ' + e.message));
    });
  }

  // Restore saved encoder-side defaults (if any)
  applyUiDefaultsFromLocalStorage();

  // Load test jobs
  let jobsPollTimer = null;
  function startJobsPolling() {
    if (jobsPollTimer) return;
    jobsPollTimer = setInterval(loadTestJobs, 2000);
  }

  function stopJobsPolling() {
    if (!jobsPollTimer) return;
    clearInterval(jobsPollTimer);
    jobsPollTimer = null;
  }

  function loadTestJobs() {
    fetch(`/api/encoding-jobs?live_channel_id=${CHANNEL_ID}&hide_done_in_playlist=1`, {
      credentials: 'include',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache'
      }
    })
      .then(r => r.json())
      .then(jobs => {
        const tbody = document.getElementById('testVideoList');
        
        if (!jobs.length) {
          tbody.innerHTML = '<tr><td colspan="6" class="no-data">No jobs yet</td></tr>';
          stopJobsPolling();
          return;
        }

        const clampPct = (p) => {
          const n = parseInt(p || 0, 10);
          if (!Number.isFinite(n)) return 0;
          return Math.max(0, Math.min(100, n));
        };

        const isActiveStatus = (s) => {
          const st = String(s || '').toLowerCase();
          return ['pending', 'queued', 'running', 'test_running', 'processing'].includes(st);
        };

        // One row per video.
        // Prefer showing an ACTIVE TEST (queued/running) status when present,
        // otherwise show the latest available job for that video.
        // Jobs are ordered desc by id from API.
        const byVideo = new Map();
        const order = [];
        (Array.isArray(jobs) ? jobs : []).forEach(j => {
          const vid = String(j.video_id || '');
          if (!vid) return;

          if (!byVideo.has(vid)) {
            byVideo.set(vid, {
              latestAny: null,
              prod: null,
              testAny: null,
              testActive: null,
              testDone: null,
            });
            order.push(vid);
          }

          const rec = byVideo.get(vid);
          if (!rec.latestAny) rec.latestAny = j;

          if (j && !j.is_test && !rec.prod) {
            rec.prod = j;
          }

          if (j && j.is_test && !rec.testAny) {
            rec.testAny = j;
          }

          if (j && j.is_test && !rec.testActive && isActiveStatus(j.status)) {
            rec.testActive = j;
          }

          const outputUrl = j && j.output_url ? String(j.output_url) : '';
          const isHlsUrl = outputUrl.toLowerCase().includes('.m3u8');
          const isDone = String(j && j.status ? j.status : '').toLowerCase() === 'done';
          if (j && j.is_test && isDone && isHlsUrl && !rec.testDone) {
            rec.testDone = j;
          }
        });

        const jobsForTable = order
          .map(vid => {
            const rec = byVideo.get(vid);
            if (!rec) return null;
            return rec.prod || rec.testActive || rec.testAny || rec.latestAny;
          })
          .filter(Boolean);

        const anyActive = (Array.isArray(jobs) ? jobs : []).some(j => isActiveStatus(j.status) && clampPct(j.progress) < 100);
        if (anyActive) startJobsPolling(); else stopJobsPolling();

        let html = '';
        jobsForTable.forEach(job => {
          const vidKey = String(job.video_id || '');
          const rec = byVideo.get(vidKey);
          const displayJob = (rec && rec.testActive) ? rec.testActive : job;

          const statusRaw = (displayJob.status || 'pending');
          const statusClass = `status-${statusRaw}`;
          const statusText = statusRaw.charAt(0).toUpperCase() + statusRaw.slice(1);
          const kindText = (displayJob && displayJob.is_test) ? 'TEST' : 'PROD';

          const pct = clampPct(displayJob.progress);
          const fill = pct;
          const metaParts = [];
          if (displayJob.speed) metaParts.push(`speed ${displayJob.speed}`);
          if (displayJob.out_time) metaParts.push(`pos ${displayJob.out_time}`);
          if (displayJob.eta) metaParts.push(`eta ${displayJob.eta}`);
          const meta = metaParts.length ? metaParts.join(' • ') : '';

          const baseJobId = (rec && (rec.prod || rec.testAny || rec.latestAny))
            ? (rec.prod || rec.testAny || rec.latestAny).id
            : job.id;

          const deleteJobId = (rec && rec.testActive) ? rec.testActive.id : baseJobId;

          const outputUrl = (rec && rec.testDone && rec.testDone.output_url) ? String(rec.testDone.output_url) : '';
          const canPlay = !!outputUrl;
          const canSelectForTotalEncode = !!outputUrl;

          html += `
            <tr>
              <td>${(displayJob.video_title ?? job.video_title ?? 'N/A')}</td>
              <td>${displayJob.text_overlay || job.text_overlay || '—'}</td>
              <td>${displayJob.codec || job.codec || '—'}</td>
              <td>${displayJob.bitrate || job.bitrate || '—'}</td>
              <td>
                <div class="job-status-wrap">
                  <div><span class="${statusClass}">${statusText} (${kindText})</span></div>
                  <div class="job-progress">
                    <div class="job-progress-bar"><div class="job-progress-fill" style="width:${fill}%;"></div></div>
                    <div class="job-progress-pct">${pct}%</div>
                  </div>
                  ${meta ? `<div class="job-progress-meta">${meta}</div>` : ''}
                </div>
              </td>
              <td>
                <div class="table-actions">
                  ${canSelectForTotalEncode ? `<label style="display:inline-flex;align-items:center;gap:6px;margin-right:8px;"><input type="checkbox" class="js-total-encode-pick" value="${job.video_id}"> Encode</label>` : ''}
                  <button type="button" class="btn-cancel" onclick="runJobTest(${baseJobId}, this)">Test</button>
                  ${canPlay ? `<button type="button" class="btn-success" onclick='openTestPlayerFromUrl(${JSON.stringify(outputUrl)})'>Play</button>` : ''}
                  <button type="button" class="btn-danger" onclick="deleteJob(${deleteJobId}, event, this)">Delete</button>
                </div>
              </td>
            </tr>
          `;
        });

        tbody.innerHTML = html;

        // Keep the test popup in sync with latest job data.
        syncTestModalFromJobs(jobs);
      })
      .catch(e => console.error('Error:', e));
  }

  window.deleteJob = function(jobId, ev, btnEl) {
    try {
      if (ev && typeof ev.preventDefault === 'function') ev.preventDefault();
      if (ev && typeof ev.stopPropagation === 'function') ev.stopPropagation();
    } catch (e) {}

    if (!confirm('Delete this job?')) return;

    if (btnEl && btnEl.disabled !== undefined) btnEl.disabled = true;

    const tokenEl = document.querySelector('input[name="_token"]');
    const csrf = tokenEl ? tokenEl.value : '';
    if (!csrf) {
      if (btnEl && btnEl.disabled !== undefined) btnEl.disabled = false;
      alert('❌ Missing CSRF token on page. Please refresh and try again.');
      return;
    }

    fetch(`/api/encoding-jobs/${jobId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(async (r) => {
      const data = await r.json().catch(() => ({}));
      if (!r.ok) {
        throw new Error(data.message || `Delete failed (${r.status})`);
      }
      return data;
    })
    .then(() => {
      // If we deleted the currently active test job, close the modal.
      try {
        if (activeTestJobId && String(activeTestJobId) === String(jobId)) {
          closeTestModal();
        }
      } catch (e) {}

      alert('✅ Job deleted');
      loadTestJobs();
    })
    .catch(e => {
      alert('❌ Error: ' + e.message);
    })
    .finally(() => {
      if (btnEl && btnEl.disabled !== undefined) btnEl.disabled = false;
    });
  };

  // Test popup playback (HLS)
  let testModalHls = null;
  let activeTestJobId = null;
  let testRequestInFlight = false;
  let lastPlayedUrl = null;

  function openTestModal(titleText, metaText) {
    const modal = document.getElementById('testPlayerModal');
    const title = document.getElementById('testModalTitle');
    const meta = document.getElementById('testModalMeta');
    const video = document.getElementById('testPlayerVideo');

    if (title) title.textContent = titleText || 'Test Playback';
    if (meta) meta.textContent = metaText || 'Preparing test…';

    // Reset player
    try {
      if (testModalHls) {
        testModalHls.destroy();
        testModalHls = null;
      }
    } catch (e) {}
    lastPlayedUrl = null;
    if (video) {
      video.pause();
      video.removeAttribute('src');
      video.load();
    }

    if (modal) {
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
    }
  }

  function closeTestModal() {
    const modal = document.getElementById('testPlayerModal');
    const video = document.getElementById('testPlayerVideo');
    try {
      if (testModalHls) {
        testModalHls.destroy();
        testModalHls = null;
      }
    } catch (e) {}
    lastPlayedUrl = null;
    activeTestJobId = null;

    if (video) {
      video.pause();
      video.removeAttribute('src');
      video.load();
    }
    if (modal) {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  function playHlsUrl(url) {
    const video = document.getElementById('testPlayerVideo');
    const meta = document.getElementById('testModalMeta');
    if (!video || !url) return;
    if (lastPlayedUrl === url) return;

    // Cleanup any previous instance
    try {
      if (testModalHls) {
        testModalHls.destroy();
        testModalHls = null;
      }
    } catch (e) {}

    lastPlayedUrl = url;
    if (meta) meta.textContent = 'Loading test stream…';

    // Native HLS (Safari)
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
      video.src = url;
      video.play().catch(() => {});
      if (meta) meta.textContent = 'Ready.';
      return;
    }

    // hls.js (Chrome/Firefox)
    if (window.Hls && window.Hls.isSupported()) {
      testModalHls = new window.Hls({ lowLatencyMode: false });
      testModalHls.loadSource(url);
      testModalHls.attachMedia(video);
      testModalHls.on(window.Hls.Events.MANIFEST_PARSED, function() {
        video.play().catch(() => {});
        if (meta) meta.textContent = 'Ready.';
      });
      testModalHls.on(window.Hls.Events.ERROR, function(_, data) {
        if (data && data.fatal) {
          if (meta) meta.textContent = 'Cannot play this HLS stream.';
        }
      });
      return;
    }

    if (meta) meta.textContent = 'HLS playback not supported in this browser.';
  }

  window.openTestPlayerFromUrl = function(url) {
    openTestModal('Test Playback', 'Loading…');
    playHlsUrl(url);
  };

  function syncTestModalFromJobs(jobs) {
    const modal = document.getElementById('testPlayerModal');
    if (!modal || !modal.classList.contains('open')) return;
    if (!activeTestJobId) return;

    const meta = document.getElementById('testModalMeta');
    const job = Array.isArray(jobs) ? jobs.find(j => String(j.id) === String(activeTestJobId)) : null;
    if (!job) {
      if (meta) meta.textContent = 'Waiting for test job…';
      return;
    }

    const st = String(job.status || '').toLowerCase();
    const pct = parseInt(job.progress || 0, 10);
    const parts = [];
    parts.push(`status ${st || 'pending'}`);
    if (Number.isFinite(pct)) parts.push(`${pct}%`);
    if (job.speed) parts.push(`speed ${job.speed}`);
    if (job.eta) parts.push(`eta ${job.eta}`);
    if (meta) meta.textContent = parts.join(' • ');

    if (st === 'done' && job.output_url) {
      playHlsUrl(job.output_url);
    }
  }

  // Start a test encode (HLS) and open popup
  window.runJobTest = function(jobId, btnEl) {
    if (testRequestInFlight) return;
    testRequestInFlight = true;

    const csrf = document.querySelector('input[name="_token"]').value;
    const durRaw = parseInt(document.getElementById('test_time_limit')?.value || '60', 10);
    const startRaw = parseInt(document.getElementById('test_start_time')?.value || '0', 10);

    let testDuration = Number.isFinite(durRaw) ? durRaw : 60;
    if (testDuration < 5) testDuration = 5;
    if (testDuration > 60) testDuration = 60;

    let testStart = Number.isFinite(startRaw) ? startRaw : 0;
    if (testStart < 0) testStart = 0;

    if (btnEl && btnEl.disabled !== undefined) btnEl.disabled = true;
    openTestModal('Test Encode', 'Starting test…');

    fetch(`/api/encoding-jobs/${jobId}/test`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        test_duration: testDuration,
        test_start: testStart,
        settings: buildSettingsFromForm(),
      })
    })
    .then(async (r) => {
      const data = await r.json().catch(() => ({}));
      if (!r.ok) throw new Error(data.message || 'Failed to start test');
      return data;
    })
    .then((data) => {
      activeTestJobId = data.test_job_id || null;
      startJobsPolling();
      loadTestJobs();
      if (data.status && String(data.status).toLowerCase() === 'done' && data.output_url) {
        playHlsUrl(data.output_url);
      }
    })
    .catch(e => {
      closeTestModal();
      alert('❌ Error: ' + e.message);
    })
    .finally(() => {
      testRequestInFlight = false;
      if (btnEl && btnEl.disabled !== undefined) btnEl.disabled = false;
    });
  };

  document.getElementById('testModalCloseBtn')?.addEventListener('click', closeTestModal);
  document.getElementById('testPlayerModal')?.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'testPlayerModal') closeTestModal();
  });

  document.getElementById('btn-cancel').addEventListener('click', function() {
    if (confirm('Cancel without saving?')) history.back();
  });

  document.getElementById('convertAllBtn').addEventListener('click', function() {
    // Convert All Videos = queue TOTAL (PROD) encoding only for the videos
    // explicitly selected in the bottom TEST table (pre-encoded 60s previews).
    const videoIds = Array.from(document.querySelectorAll('#testVideoList .js-total-encode-pick:checked'))
      .map(cb => parseInt(cb.value, 10))
      .filter(v => Number.isFinite(v) && v > 0);

    if (videoIds.length === 0) {
      alert('❌ Select at least one TEST video (bottom Encode checkbox)');
      return;
    }

    if (!confirm(`Queue encoding for ${videoIds.length} video(s)?`)) return;

    fetch('/api/encoding-jobs/bulk', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        live_channel_id: CHANNEL_ID,
        video_ids: videoIds,
        settings: buildSettingsFromForm(),
      })
    })
      .then(async (r) => {
        const data = await r.json().catch(() => ({}));
        if (!r.ok) {
          const message = data.message || 'Failed to queue jobs';
          throw new Error(message);
        }
        return data;
      })
      .then(data => {
        if (!data.ok) throw new Error('Failed to queue jobs');
        alert(`✅ Queued ${data.count || 0} job(s)`);
        // After queueing TOTAL (TS) encodes, jump to the monitor page for this channel.
        window.location.href = `/vod-channels/${CHANNEL_ID}/encoding-now`;
      })
      .catch(e => alert('❌ Error: ' + e.message));
  });

  // Start TOTAL (production) encoding only for videos you tested and selected.
  // Selection checkboxes appear only for rows that have a completed TEST.
  const encodeSelectedFromTestsBtn = document.getElementById('encodeSelectedFromTestsBtn');
  if (encodeSelectedFromTestsBtn) {
    encodeSelectedFromTestsBtn.addEventListener('click', function() {
      const videoIds = Array.from(document.querySelectorAll('#testVideoList .js-total-encode-pick:checked'))
        .map(cb => parseInt(cb.value, 10))
        .filter(v => Number.isFinite(v) && v > 0);

      if (videoIds.length === 0) {
        alert('❌ Select at least one TEST-encoded video to encode.');
        return;
      }

      if (!confirm(`Queue TOTAL encoding for ${videoIds.length} selected video(s)?`)) return;

      fetch('/api/encoding-jobs/bulk', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          live_channel_id: CHANNEL_ID,
          video_ids: videoIds,
          settings: buildSettingsFromForm(),
        })
      })
        .then(async (r) => {
          const data = await r.json().catch(() => ({}));
          if (!r.ok) throw new Error(data.message || 'Failed to queue jobs');
          return data;
        })
        .then(() => {
          alert('✅ Queued TOTAL encoding for selected videos');
          document.querySelectorAll('#testVideoList .js-total-encode-pick:checked').forEach(cb => { cb.checked = false; });
          // After queueing TOTAL (TS) encodes, jump to the monitor page for this channel.
          window.location.href = `/vod-channels/${CHANNEL_ID}/encoding-now`;
        })
        .catch(e => alert('❌ Error: ' + e.message));
    });
  }

  document.getElementById('deleteAllBtn').addEventListener('click', function() {
    if (!confirm('Delete all jobs for this channel?')) return;

    fetch(`/api/encoding-jobs?live_channel_id=${CHANNEL_ID}`, {
      credentials: 'include',
      cache: 'no-store',
      headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
    })
      .then(r => r.json())
      .then(async (jobs) => {
        const ids = Array.isArray(jobs) ? jobs.map(j => j.id).filter(Boolean) : [];
        if (ids.length === 0) {
          alert('No jobs to delete');
          return;
        }

        for (const id of ids) {
          const r = await fetch(`/api/encoding-jobs/${id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          if (!r.ok) {
            const data = await r.json().catch(() => ({}));
            throw new Error(data.message || `Delete failed for job ${id} (${r.status})`);
          }
        }

        alert(`✅ Deleted ${ids.length} job(s)`);
        loadTestJobs();
      })
      .catch(e => alert('❌ Error: ' + e.message));
  });

  loadTestJobs();

  // Auto-load the channel's default category (if configured).
  (function initCategoryAndVideos() {
    const catEl = document.getElementById('category_id');
    if (!catEl) return;

    const currentVal = String(catEl.value || '').trim();
    const defaultVal = String(catEl.getAttribute('data-default-category-id') || '').trim();
    const initial = (currentVal !== '' && currentVal !== '0') ? currentVal : ((defaultVal !== '' && defaultVal !== '0') ? defaultVal : '');
    if (!initial) return;
    catEl.value = initial;
    loadVideosForCategory(initial);
  })();

  renderSelectedList();
});
</script>

@endsection
