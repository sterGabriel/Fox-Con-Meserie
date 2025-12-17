@extends('layouts.panel')

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
    grid-template-columns: 420px 1fr;
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
        <div id="info-details" class="info-details">
          <div class="info-detail-row"><strong>Duration:</strong> <span id="detail-duration">—</span></div>
          <div class="info-detail-row"><strong>Resolution:</strong> <span id="detail-resolution">—</span></div>
          <div class="info-detail-row"><strong>Format:</strong> <span id="detail-format">—</span></div>
          <div class="info-detail-row"><strong>Size:</strong> <span id="detail-size">—</span></div>
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
        <select disabled>
          <option>{{ $channel->name }} (current)</option>
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
        <input type="checkbox" name="logo_enabled" id="logo_enabled" checked>
        <label for="logo_enabled">Transparent Logo</label>
      </div>

      <div id="logo-section">
        <button type="button" class="btn-cancel" style="width: 100%; margin-bottom: 12px; background: #6b7280; color: white;">Live Edit Logo Position</button>

        <div class="grid-3">
          <div class="form-group">
            <label>Logo Position</label>
            <select name="logo_position" id="logo_position">
              <option value="tl" selected>Top Left</option>
              <option value="tr">Top Right</option>
              <option value="bl">Bottom Left</option>
              <option value="br">Bottom Right</option>
            </select>
          </div>
          <div class="form-group">
            <label>Logo Position: X</label>
            <input type="number" name="logo_x" id="logo_x" value="20">
          </div>
          <div class="form-group">
            <label>Logo Position: Y</label>
            <input type="number" name="logo_y" id="logo_y" value="20">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Logo Width <span style="font-size: 11px; color: #6b7280;">| Logo 1200x375</span></label>
            <input type="number" name="logo_width" id="logo_width" value="180">
          </div>
          <div class="form-group">
            <label>Logo Height</label>
            <input type="number" name="logo_height" id="logo_height" value="56">
          </div>
        </div>

        <div style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">
          <strong>Original Logo Size:</strong> 1200 x 375
        </div>

        <div class="form-group">
          <label>Logo Opacity</label>
          <div class="slider-wrap">
            <input type="range" name="logo_opacity" id="logo_opacity" min="0" max="1" step="0.01" value="0.80">
            <div class="slider-val" id="logo_opacity_val">0.80</div>
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
        <input type="checkbox" name="text_enabled" id="text_enabled" checked>
        <label for="text_enabled">Text Overlay</label>
      </div>

      <div id="text-section">
        <div class="form-group">
          <label>Text Type</label>
          <select name="text_type" id="text_type">
            <option value="static" selected>Static</option>
            <option value="scrolling">Scrolling</option>
          </select>
        </div>

        <div class="form-group">
          <label>Text</label>
          <input type="text" name="text_value" id="text_value" placeholder="Enter overlay text" maxlength="100">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Font</label>
            <select name="text_font" id="text_font">
              <option value="Ubuntu" selected>Ubuntu</option>
              <option value="Arial">Arial</option>
              <option value="DejaVuSans">DejaVu Sans</option>
            </select>
          </div>
          <div class="form-group">
            <label>Font Size</label>
            <input type="number" name="text_size" id="text_size" value="15" min="8" max="72">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Font Color</label>
            <select name="text_color" id="text_color">
              <option value="white" selected>White</option>
              <option value="black">Black</option>
              <option value="yellow">Yellow</option>
            </select>
          </div>
          <div class="form-group">
            <label>Text Position</label>
            <select name="text_position" id="text_position">
              <option value="br" selected>Bottom Right</option>
              <option value="bl">Bottom Left</option>
              <option value="tr">Top Right</option>
              <option value="tl">Top Left</option>
            </select>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>X</label>
            <input type="number" name="text_x" id="text_x" value="30">
          </div>
          <div class="form-group">
            <label>Y</label>
            <input type="number" name="text_y" id="text_y" value="30">
          </div>
        </div>

        <div class="form-group">
          <label>Text Opacity</label>
          <div class="slider-wrap">
            <input type="range" name="text_opacity" id="text_opacity" min="0" max="1" step="0.01" value="1.00">
            <div class="slider-val" id="text_opacity_val">1.00</div>
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

      <!-- BUTTONS -->
      <div class="button-group">
        <button type="button" class="btn-cancel" id="btn-cancel">Cancel</button>
        <button type="submit" class="btn-primary" id="btn-create">Create Video</button>
      </div>
    </form>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="right-column">
    <!-- CHANNEL HEADER -->
    <div class="card">
      <div class="channel-header">
        <div class="channel-info">
          <div class="channel-icon">📺</div>
          <div class="channel-text">
            <h5>{{ $channel->name }}</h5>
            <p>Resolution: {{ $channel->resolution ?? '1280x720' }}</p>
          </div>
        </div>
        <div class="category-select">
          <label>Video Category</label>
          <select id="category_id">
            <option value="">Select Category</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
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
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100" selected>100</option>
        </select>
        <span>entries</span>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 36px;"><input type="checkbox" id="selectAllVideos"></th>
            <th>Name</th>
            <th>Server</th>
            <th>Screen</th>
            <th>Size</th>
            <th>Format</th>
            <th style="width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody id="videosList">
          <tr><td colspan="7" class="no-data">Select a category to load videos</td></tr>
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
        <button type="button" class="btn-danger" id="deleteAllBtn">✕ Delete All Videos</button>
      </div>
    </div>
  </div>
</div>

<script>
let selectedVideo = null;

document.addEventListener('DOMContentLoaded', function() {
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
    if (!categoryId) {
      document.getElementById('videosList').innerHTML = '<tr><td colspan="7" class="no-data">Select a category to load videos</td></tr>';
      return;
    }

    fetch(`/api/videos?category_id=${categoryId}`, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(r => r.json())
      .then(videos => {
        if (!videos.length) {
          document.getElementById('videosList').innerHTML = '<tr><td colspan="7" class="no-data">No videos in this category</td></tr>';
          return;
        }

        let html = '';
        videos.forEach(v => {
          const duration = v.duration_seconds ? `${Math.floor(v.duration_seconds / 60)}:${String(v.duration_seconds % 60).padStart(2, '0')}` : '—';
          const size = v.size_bytes ? `${(v.size_bytes / (1024*1024)).toFixed(0)} MB` : '—';
          const resolution = v.resolution || '—';
          const format = (v.format || 'mp4').toUpperCase();
          const videoData = JSON.stringify(v).replace(/"/g, '&quot;');

          html += `
            <tr>
              <td><input type="checkbox" class="video-checkbox" value="${v.id}"></td>
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

        document.getElementById('videosList').innerHTML = html;
        
        // Attach event listeners to new buttons
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
      })
      .catch(e => console.error('Error:', e));
  });

  // Select video
  window.selectVideo = function(video) {
    selectedVideo = video;
    document.getElementById('video_id').value = video.id;
    
    const duration = video.duration_seconds ? `${Math.floor(video.duration_seconds / 60)}:${String(video.duration_seconds % 60).padStart(2, '0')}` : '—';
    const size = video.size_bytes ? `${(video.size_bytes / (1024*1024*1024)).toFixed(2)} GB` : '—';

    document.getElementById('selected-title').textContent = '✅ ' + video.title;
    document.getElementById('selected-meta').textContent = 'Video selected. Ready to encode.';
    document.getElementById('detail-duration').textContent = duration;
    document.getElementById('detail-resolution').textContent = video.resolution || '—';
    document.getElementById('detail-format').textContent = (video.format || 'mp4').toUpperCase();
    document.getElementById('detail-size').textContent = size;
    document.getElementById('info-details').classList.add('visible');

    console.log('Video selected:', video);
  };

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

    if (!selectedVideo) {
      alert('❌ Please select a video first');
      return;
    }

    const jobData = {
      video_id: selectedVideo.id,
      live_channel_id: {{ $channel->id }},
      encoder: document.getElementById('encoder').value,
      preset: document.getElementById('preset').value,
      video_bitrate: document.getElementById('video_bitrate').value,
      video_codec: document.getElementById('video_codec').value,
      tune: document.getElementById('tune').value,
      frame_rate: document.getElementById('frame_rate').value,
      crf_mode: document.getElementById('crf_mode').value,
      crf_value: document.getElementById('crf_value').value,
      audio_bitrate: document.getElementById('audio_bitrate').value,
      stereo_type: document.getElementById('stereo_type').value,
      audio_language: document.getElementById('audio_language').value,
      video_aspect: document.getElementById('video_aspect').value,
      logo_enabled: document.getElementById('logo_enabled').checked ? 1 : 0,
      logo_position: document.getElementById('logo_position').value,
      logo_x: document.getElementById('logo_x').value,
      logo_y: document.getElementById('logo_y').value,
      logo_width: document.getElementById('logo_width').value,
      logo_height: document.getElementById('logo_height').value,
      logo_opacity: document.getElementById('logo_opacity').value,
      text_enabled: document.getElementById('text_enabled').checked ? 1 : 0,
      text_type: document.getElementById('text_type').value,
      text_value: document.getElementById('text_value').value,
      text_font: document.getElementById('text_font').value,
      text_size: document.getElementById('text_size').value,
      text_color: document.getElementById('text_color').value,
      text_position: document.getElementById('text_position').value,
      text_x: document.getElementById('text_x').value,
      text_y: document.getElementById('text_y').value,
      text_opacity: document.getElementById('text_opacity').value,
      text_background: document.getElementById('text_background').value,
      text_box_color: document.getElementById('text_box_color').value,
      text_padding: document.getElementById('text_padding').value,
      output_format: 'TS'
    };

    fetch('/api/encoding-jobs', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(jobData)
    })
    .then(r => r.json())
    .then(data => {
      alert('✅ Encoding job created!');
      loadTestJobs();
      selectedVideo = null;
      document.getElementById('selected-title').textContent = 'ℹ️ Please Select Video';
      document.getElementById('info-details').classList.remove('visible');
    })
    .catch(e => alert('❌ Error: ' + e.message));
  });

  // Load test jobs
  function loadTestJobs() {
    fetch(`/api/encoding-jobs?live_channel_id={{ $channel->id }}`, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(r => r.json())
      .then(jobs => {
        const tbody = document.getElementById('testVideoList');
        
        if (!jobs.length) {
          tbody.innerHTML = '<tr><td colspan="6" class="no-data">No jobs yet</td></tr>';
          return;
        }

        let html = '';
        jobs.forEach(job => {
          const statusClass = `status-${job.status || 'pending'}`;
          const statusText = (job.status || 'pending').charAt(0).toUpperCase() + (job.status || 'pending').slice(1);
          
          html += `
            <tr>
              <td>${job.video ? job.video.title : 'N/A'}</td>
              <td>${job.text_value || '—'}</td>
              <td>${job.video_codec || '—'}</td>
              <td>${job.video_bitrate || '—'}</td>
              <td><span class="${statusClass}">${statusText}</span></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="btn-cancel">Test</button>
                  <button type="button" class="btn-danger" onclick="deleteJob(${job.id})">Delete</button>
                </div>
              </td>
            </tr>
          `;
        });

        tbody.innerHTML = html;
      })
      .catch(e => console.error('Error:', e));
  }

  window.deleteJob = function(jobId) {
    if (!confirm('Delete this job?')) return;

    fetch(`/api/encoding-jobs/${jobId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        'Accept': 'application/json'
      }
    })
    .then(r => r.json())
    .then(data => {
      alert('✅ Job deleted');
      loadTestJobs();
    })
    .catch(e => alert('❌ Error: ' + e.message));
  };

  document.getElementById('btn-cancel').addEventListener('click', function() {
    if (confirm('Cancel without saving?')) history.back();
  });

  document.getElementById('convertAllBtn').addEventListener('click', function() {
    alert('Convert All – coming soon');
  });

  document.getElementById('deleteAllBtn').addEventListener('click', function() {
    if (confirm('Delete all jobs?')) alert('Deleting – coming soon');
  });

  loadTestJobs();
});
</script>

@endsection
