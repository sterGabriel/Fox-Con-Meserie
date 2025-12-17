@extends('layouts.panel')

@section('content')

<style>
  .fox-dashboard {
    padding: 24px;
    background: #f4f5f7;
  }

  .fox-server-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
  }

  .fox-tab-btn {
    padding: 10px 16px;
    background: #2a2a2a;
    color: #ffffff;
    border: 1px solid #3a3a3a;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .fox-tab-btn.active {
    background: #e30613;
    border-color: #e30613;
  }

  .fox-tab-btn:hover {
    border-color: #e30613;
  }

  .fox-header-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    padding: 16px 20px;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .fox-header-title {
    font-size: 16px;
    font-weight: 700;
    color: #333;
  }

  .fox-restart-header-btn {
    background: #e30613;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .fox-restart-header-btn:hover {
    background: #c80510;
  }

  /* Cards Grid */
  .fox-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .fox-metric-card {
    background: #ffffff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    border-left: 4px solid #2f6fed;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
  }

  .fox-metric-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
  }

  .fox-metric-icon {
    font-size: 24px;
    margin-bottom: 8px;
  }

  .fox-metric-label {
    font-size: 12px;
    color: #999;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
  }

  .fox-metric-value {
    font-size: 24px;
    font-weight: 700;
    color: #333;
  }

  /* Charts Grid */
  .fox-charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
  }

  .fox-chart-box {
    background: #ffffff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .fox-chart-title {
    font-size: 14px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
  }

  .fox-chart-placeholder {
    height: 200px;
    background: #f8f8f8;
    border: 1px dashed #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 13px;
  }

  @media (max-width: 1024px) {
    .fox-charts-row {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .fox-cards-grid {
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }

    .fox-metric-value {
      font-size: 20px;
    }
  }

  /* Recent Channels Table */
  .fox-recent-section {
    margin-top: 32px;
  }

  .fox-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }

  .fox-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #333;
  }

  .fox-view-all-link {
    color: #2f6fed;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
  }

  .fox-view-all-link:hover {
    text-decoration: underline;
  }

  .fox-recent-table {
    width: 100%;
    background: #ffffff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .fox-table-header {
    display: grid;
    grid-template-columns: 40px 1fr 100px 200px 120px 250px;
    gap: 12px;
    padding: 12px 16px;
    background: #f3f4f6;
    border-bottom: 1px solid #e8e8e8;
    font-size: 12px;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .fox-table-row {
    display: grid;
    grid-template-columns: 40px 1fr 100px 200px 120px 250px;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    align-items: center;
    transition: all 0.2s ease;
  }

  .fox-table-row:hover {
    background: #f9fafb;
  }

  .fox-table-row:last-child {
    border-bottom: none;
  }

  .fox-channel-logo {
    width: 40px;
    height: 40px;
    border-radius: 4px;
    background: #e8e8e8;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  .fox-channel-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .fox-channel-name {
    font-size: 13px;
    font-weight: 600;
    color: #333;
  }

  .fox-channel-id {
    font-size: 11px;
    color: #999;
  }

  .fox-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    text-align: center;
  }

  .fox-status-idle {
    background: #dbeafe;
    color: #1e40af;
  }

  .fox-status-running {
    background: #dcfce7;
    color: #166534;
  }

  .fox-status-error {
    background: #fee2e2;
    color: #991b1b;
  }

  .fox-profile-text {
    font-size: 12px;
    color: #666;
  }

  .fox-updated-text {
    font-size: 12px;
    color: #999;
  }

  .fox-actions {
    display: flex;
    gap: 6px;
  }

  .fox-action-btn {
    padding: 4px 8px;
    border: 1px solid #e8e8e8;
    background: #f9fafb;
    color: #2f6fed;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .fox-action-btn:hover {
    background: #2f6fed;
    color: #fff;
    border-color: #2f6fed;
  }

  .fox-action-btn.danger {
    color: #e30613;
  }

  .fox-action-btn.danger:hover {
    background: #e30613;
    color: #fff;
    border-color: #e30613;
  }

  .fox-action-btn.warning {
    color: #f59e0b;
  }

  .fox-action-btn.warning:hover {
    background: #f59e0b;
    color: #fff;
    border-color: #f59e0b;
  }

</style>

<div class="fox-dashboard">

  <!-- SERVER TABS -->
  <div class="fox-server-tabs">
    <button class="fox-tab-btn active" onclick="selectServer(1)">Server 1</button>
    <button class="fox-tab-btn" onclick="selectServer(2)">Server 2</button>
  </div>

  <!-- HEADER BOX [Server1] + Restart -->
  <div class="fox-header-box">
    <div class="fox-header-title">📊 [Server 1]</div>
    <button class="fox-restart-header-btn" onclick="restartServer()">🔄 Restart</button>
  </div>

  <!-- METRIC CARDS GRID (2 rows × 6 cards) -->
  <div class="fox-cards-grid">
    <!-- ROW 1 -->
    <div class="fox-metric-card">
      <div class="fox-metric-icon">💻</div>
      <div class="fox-metric-label">CPU</div>
      <div class="fox-metric-value">{{ number_format($cpuUsage ?? 0, 1) }}%</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">🧠</div>
      <div class="fox-metric-label">RAM</div>
      <div class="fox-metric-value">{{ number_format($ramUsage ?? 0, 1) }}%</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">📥</div>
      <div class="fox-metric-label">Input (Mbps)</div>
      <div class="fox-metric-value">{{ number_format($networkStats['input_mbps'] ?? 0, 0) }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">📤</div>
      <div class="fox-metric-label">Output (Mbps)</div>
      <div class="fox-metric-value">{{ number_format($networkStats['output_mbps'] ?? 0, 0) }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">👥</div>
      <div class="fox-metric-label">Online Connections</div>
      <div class="fox-metric-value">{{ $runningChannels ?? 0 }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">🔗</div>
      <div class="fox-metric-label">Open Connections</div>
      <div class="fox-metric-value">{{ $enabledChannels ?? 0 }}</div>
    </div>

    <!-- ROW 2 -->
    <div class="fox-metric-card">
      <div class="fox-metric-icon">⚙️</div>
      <div class="fox-metric-label">Transcoding Videos</div>
      <div class="fox-metric-value">{{ $jobsStats['running'] ?? 0 }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">🎬</div>
      <div class="fox-metric-label">Trailer Videos</div>
      <div class="fox-metric-value">{{ $totalChannels ?? 0 }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">🎥</div>
      <div class="fox-metric-label">Total Videos</div>
      <div class="fox-metric-value">{{ $totalChannels ?? 0 }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">📺</div>
      <div class="fox-metric-label">Live Streams</div>
      <div class="fox-metric-value">{{ $runningChannels ?? 0 }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">📻</div>
      <div class="fox-metric-label">Radio Live Streams</div>
      <div class="fox-metric-value">{{ $errorChannels ?? 0 }}</div>
    </div>

    <div class="fox-metric-card">
      <div class="fox-metric-icon">⏱️</div>
      <div class="fox-metric-label">Server Uptime</div>
      <div class="fox-metric-value">{{ $uptime ?? 'N/A' }}</div>
    </div>
  </div>

  <!-- CHARTS ROW 1: Traffic Statistics + Network Bandwidth -->
  <div class="fox-charts-row">
    <div class="fox-chart-box">
      <div class="fox-chart-title">Traffic Statistics</div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div style="text-align: center; padding: 12px; background: #f8f8f8; border-radius: 4px;">
          <div style="font-size: 11px; color: #999; margin-bottom: 4px;">INPUT</div>
          <div style="font-size: 18px; font-weight: 700; color: #f59e0b;">{{ number_format($networkStats['input_mbps'] ?? 0, 0) }} Mbps</div>
        </div>
        <div style="text-align: center; padding: 12px; background: #f8f8f8; border-radius: 4px;">
          <div style="font-size: 11px; color: #999; margin-bottom: 4px;">OUTPUT</div>
          <div style="font-size: 18px; font-weight: 700; color: #fbbf24;">{{ number_format($networkStats['output_mbps'] ?? 0, 0) }} Mbps</div>
        </div>
      </div>
      <div style="text-align: center; padding: 12px; background: #f8f8f8; border-radius: 4px; margin-bottom: 12px;">
        <div style="font-size: 11px; color: #999; margin-bottom: 4px;">TOTAL</div>
        <div style="font-size: 18px; font-weight: 700; color: #ea8c55;">{{ number_format($networkStats['total_mbps'] ?? 0, 0) }} Mbps</div>
      </div>
      <div class="fox-chart-placeholder">Pie Chart (Upload / Download)</div>
    </div>

    <div class="fox-chart-box">
      <div class="fox-chart-title">Network Bandwidth</div>
      <div class="fox-chart-placeholder">Line Chart (Network Bandwidth)</div>
    </div>
  </div>

  <!-- CHARTS ROW 2: Harddisk Information + World Statistics -->
  <div class="fox-charts-row">
    <div class="fox-chart-box">
      <div class="fox-chart-title">Harddisk Information</div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div style="text-align: center; padding: 12px; background: #f8f8f8; border-radius: 4px;">
          <div style="font-size: 11px; color: #999; margin-bottom: 4px;">USED</div>
          <div style="font-size: 18px; font-weight: 700; color: #e30613;">{{ $diskStats['used_pct'] ?? 0 }}%</div>
        </div>
        <div style="text-align: center; padding: 12px; background: #f8f8f8; border-radius: 4px;">
          <div style="font-size: 11px; color: #999; margin-bottom: 4px;">FREE</div>
          <div style="font-size: 18px; font-weight: 700; color: #16a34a;">{{ $diskStats['free_pct'] ?? 0 }}%</div>
        </div>
      </div>
      <div style="text-align: center; padding: 12px; background: #f8f8f8; border-radius: 4px; margin-bottom: 12px;">
        <div style="font-size: 11px; color: #999; margin-bottom: 4px;">TOTAL</div>
        <div style="font-size: 18px; font-weight: 700; color: #333;">{{ $diskStats['total_gb'] ?? 0 }} GB</div>
      </div>
      <div class="fox-chart-placeholder">Pie Chart (Disk Usage)</div>
    </div>

    <div class="fox-chart-box">
      <div class="fox-chart-title">World Statistics</div>
      <div class="fox-chart-placeholder">World Map (Viewers by Country)</div>
    </div>
  </div>

  <!-- RECENT CHANNELS SECTION -->
  <div class="fox-recent-section">
    <div class="fox-section-header">
      <h3 class="fox-section-title">Recent Channels</h3>
      <a href="/vod-channels" class="fox-view-all-link">View all</a>
    </div>

    <div class="fox-recent-table">
      <!-- Table Header -->
      <div class="fox-table-header">
        <div style="text-align: center;">Logo</div>
        <div>Channel</div>
        <div>Status</div>
        <div>Profile</div>
        <div>Updated</div>
        <div>Actions</div>
      </div>

      <!-- Table Rows -->
      @forelse($recentChannels ?? [] as $channel)
        <div class="fox-table-row">
          <!-- Logo -->
          <div class="fox-channel-logo">
            @if($channel->logo_path)
              <img src="{{ $channel->logo_path }}" alt="Logo">
            @else
              <span style="color: #ccc; font-size: 20px;">📺</span>
            @endif
          </div>

          <!-- Channel Name -->
          <div>
            <div class="fox-channel-name">{{ $channel->name }}</div>
            <div class="fox-channel-id">ID: {{ $channel->id }}</div>
          </div>

          <!-- Status -->
          <div>
            @php
              $statusClass = 'fox-status-idle';
              $statusText = 'IDLE';
              if ($channel->status === 'running') {
                $statusClass = 'fox-status-running';
                $statusText = 'RUNNING';
              } elseif ($channel->status === 'error') {
                $statusClass = 'fox-status-error';
                $statusText = 'ERROR';
              }
            @endphp
            <span class="fox-status-badge {{ $statusClass }}">{{ $statusText }}</span>
          </div>

          <!-- Profile -->
          <div class="fox-profile-text">
            {{ $channel->resolution ?? 'N/A' }}<br>
            {{ number_format($channel->video_bitrate ?? 0) }} kbps • {{ $channel->fps ?? 0 }} fps
          </div>

          <!-- Updated -->
          <div class="fox-updated-text">
            {{ $channel->updated_at ? $channel->updated_at->diffForHumans() : 'N/A' }}
          </div>

          <!-- Actions -->
          <div class="fox-actions">
            <button class="fox-action-btn" title="Settings">⚙️ Settings</button>
            <button class="fox-action-btn warning" title="Restart">🔄 Restart</button>
            <button class="fox-action-btn danger" title="Toggle">🔌 Toggle</button>
          </div>
        </div>
      @empty
        <div class="fox-table-row" style="text-align: center; color: #999; padding: 32px;">
          No recent channels found
        </div>
      @endforelse
    </div>
  </div>
</div>

<script>
  function selectServer(serverId) {
    console.log('Server selected:', serverId);
    document.querySelectorAll('.fox-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    // TODO: Load server data
  }

  function restartServer() {
    if (confirm('Restart server?')) {
      console.log('Restarting server...');
      // TODO: API call
    }
  }
</script>
@endsection

