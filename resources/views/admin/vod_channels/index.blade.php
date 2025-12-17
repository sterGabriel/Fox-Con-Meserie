@extends('layouts.panel')

@section('content')

<style>
/* ===== GLOBAL ===== */
* { box-sizing: border-box; }
html, body { width: 100%; overflow-x: hidden; }

/* ===== PAGE WRAPPER ===== */
.page-wrap {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
  background: #ffffff;
}

/* ===== HEADER ===== */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 32px;
  gap: 16px;
}

.header-title {
  flex: 1;
}

.header-title h1 {
  font-size: 32px;
  font-weight: 800;
  color: #1a1a1a;
  margin: 0 0 4px 0;
  line-height: 1.2;
}

.header-title h1 .server-highlight {
  color: #f1c40f;
  font-weight: 900;
}

.header-subtitle {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
  margin: 4px 0 0 0;
}

.header-button {
  align-self: center;
}

.btn-new-channel {
  background: #3b82f6;
  color: #ffffff;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-new-channel:hover {
  background: #2563eb;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* ===== KPI GRID (6 cards) ===== */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.kpi-card {
  background: #ffffff;
  border-radius: 14px;
  position: relative;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
  padding: 20px 18px;
  min-height: 100px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.kpi-card::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 6px;
  border-radius: 14px 0 0 14px;
  background: #1f6fff;
}

.kpi-title {
  font-size: 12px;
  color: #6b7280;
  text-align: center;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 8px;
}

.kpi-value {
  font-size: 28px;
  font-weight: 900;
  text-align: center;
  color: #1a1a1a;
  line-height: 1;
}

@media (max-width: 1200px) {
  .kpi-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-button {
    align-self: stretch;
  }
  
  .btn-new-channel {
    width: 100%;
  }
}

/* ===== SERVER SELECT ===== */
.server-select-bar {
  background: #fbf8e9;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 20px;
  border: 1px solid #f3f0d8;
}

.server-select-bar select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d4cdb3;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  background: #ffffff;
  color: #1a1a1a;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%231a1a1a' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}

/* ===== MAIN CARD (with toolbar, warning, table) ===== */
.main-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* Toolbar */
.toolbar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.btn {
  border: none;
  border-radius: 8px;
  padding: 10px 16px;
  font-weight: 700;
  color: #ffffff;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-stop {
  background: #e53935;
}

.btn-stop:hover {
  background: #d32f2f;
}

.btn-start {
  background: #43a047;
}

.btn-start:hover {
  background: #388e3c;
}

.btn-epg {
  background: #f39c12;
}

.btn-epg:hover {
  background: #e67e22;
}

.btn-fast {
  background: #d32f2f;
}

.btn-fast:hover {
  background: #c62828;
}

.btn-msg {
  background: #3b6ef5;
}

.btn-msg:hover {
  background: #2e5fd8;
}

/* Warning bar */
.warning-bar {
  background: #3f4a58;
  border-radius: 8px;
  padding: 14px 16px;
  position: relative;
  margin-bottom: 20px;
  color: #ffffff;
  font-size: 13px;
  line-height: 1.5;
}

.warning-bar::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 6px;
  border-radius: 8px 0 0 8px;
  background: #f1c40f;
}

.warning-bar .warn-highlight {
  color: #f1c40f;
  font-weight: 900;
}

/* Table controls */
.table-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  gap: 16px;
  flex-wrap: wrap;
}

.records-select {
  display: flex;
  align-items: center;
  gap: 8px;
}

.records-select select {
  width: 70px;
  padding: 8px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  background: #ffffff;
  color: #1a1a1a;
}

.records-select label {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
}

.search-input {
  width: 300px;
}

.search-input input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
}

@media (max-width: 1024px) {
  .search-input {
    width: 100%;
  }
}

/* ===== TABLE ===== */
.table-wrap {
  width: 100%;
  overflow: auto;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
}

thead th {
  background: #f2f4f7;
  color: #374151;
  font-weight: 700;
  text-align: left;
  padding: 14px 16px;
  border-bottom: 2px solid #e5e7eb;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
  color: #1a1a1a;
}

tbody tr {
  background: #ffffff;
  transition: all 0.15s ease;
}

tbody tr:nth-child(even) {
  background: #f9f9f9;
}

tbody tr:hover {
  background: #f5f5f5;
}

/* Table columns - specific widths */
td:nth-child(1) { width: 200px; } /* Name */
td:nth-child(2) { width: 120px; } /* Transcoding */
td:nth-child(3) { width: 140px; } /* Playing */
td:nth-child(4) { width: 100px; } /* Bitrate */
td:nth-child(5) { width: 140px; } /* Uptime */
td:nth-child(6) { width: 80px; } /* Status */
td:nth-child(7) { width: 80px; } /* Epg */
td:nth-child(8) { width: 100px; } /* Size */
td:nth-child(9) { width: 120px; } /* Total Time */
td:nth-child(10) { width: 150px; } /* Events */
td:nth-child(11) { width: 130px; text-align: right; } /* Actions */

/* ===== BADGES & PILLS ===== */
.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-pink {
  background: #fbecf8;
  color: #be185d;
}

.badge-yellow {
  background: #fef3c7;
  color: #b45309;
}

.badge-gray {
  background: #f3f4f6;
  color: #4b5563;
}

.status-dot {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #10b981;
}

.status-dot.error {
  background: #ef4444;
}

/* ===== BUTTONS IN COLUMNS ===== */
.actions-cell {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.icon-btn {
  width: 36px;
  height: 34px;
  border-radius: 6px;
  border: none;
  color: #ffffff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s ease;
  font-weight: 600;
}

.icon-btn.stop {
  background: #d32f2f;
}

.icon-btn.stop:hover {
  background: #b71c1c;
}

.icon-btn.edit {
  background: #f39c12;
}

.icon-btn.edit:hover {
  background: #d68910;
}

.icon-btn.delete {
  background: #d32f2f;
}

.icon-btn.delete:hover {
  background: #b71c1c;
}

/* ===== DROPDOWN MENU ===== */
.dropdown-wrapper {
  position: relative;
  display: inline-block;
  width: 100%;
}

.dropdown-btn {
  width: 100%;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #1a1a1a;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  transition: all 0.2s ease;
}

.dropdown-btn:hover {
  background: #e5e7eb;
  border-color: #d1d5db;
}

.dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 100%;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  min-width: 220px;
  z-index: 100;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
}

.dropdown-menu.active {
  display: block;
}

.dropdown-menu a {
  display: block;
  padding: 10px 16px;
  color: #1a1a1a;
  text-decoration: none;
  font-size: 13px;
  border-bottom: 1px solid #f3f4f6;
  transition: all 0.15s ease;
  cursor: pointer;
}

.dropdown-menu a:last-child {
  border-bottom: none;
  border-radius: 0 0 8px 8px;
}

.dropdown-menu a:first-child {
  border-radius: 8px 8px 0 0;
}

.dropdown-menu a:hover {
  background: #f3f4f6;
  color: #3b82f6;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
  .page-wrap {
    padding: 16px;
  }

  table {
    font-size: 12px;
  }

  thead th {
    padding: 12px 12px;
  }

  tbody td {
    padding: 12px 12px;
  }
}

@media (max-width: 768px) {
  .page-wrap {
    padding: 12px;
  }

  .page-header {
    margin-bottom: 24px;
  }

  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .toolbar {
    flex-direction: column;
  }

  .btn {
    flex: 1;
  }

  .table-controls {
    flex-direction: column;
  }

  .search-input {
    width: 100%;
  }

  .records-select {
    width: 100%;
  }

  .table-wrap {
    overflow-x: auto;
  }

  .icon-btn {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }
}
</style>

<div class="page-wrap">

  <!-- HEADER -->
  <div class="page-header">
    <div class="header-title">
      <h1>Vod Channels <span class="server-highlight">[Server 1]</span></h1>
      <p class="header-subtitle">Manage all your streaming channels</p>
    </div>
    <div class="header-button">
      <button class="btn-new-channel" onclick="location.href='/vod-channels/create'">+ New Channel</button>
    </div>
  </div>

  <!-- KPI CARDS (6) -->
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
      <div class="kpi-value">{{ $totalChannels ?? 0 }}</div>
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

  <!-- SERVER SELECT -->
  <div class="server-select-bar">
    <select onchange="changeServer(this.value)">
      <option value="1">Server 1</option>
      <option value="2">Server 2</option>
      <option value="3">Server 3</option>
    </select>
  </div>

  <!-- MAIN CARD -->
  <div class="main-card">

    <!-- TOOLBAR BUTTONS -->
    <div class="toolbar">
      <button class="btn btn-stop" onclick="stopAllChannels()">Stop</button>
      <button class="btn btn-start" onclick="startAllChannels()">Start</button>
      <button class="btn btn-epg" onclick="channelsEpg()">Channels Epg</button>
      <button class="btn btn-fast" onclick="fastChannel()">Fast Channel</button>
      <button class="btn btn-msg" onclick="sendMessage()">Send Message</button>
    </div>

    <!-- WARNING BAR -->
    <div class="warning-bar">
      Important warning !!! You would create more than <span class="warn-highlight">[50]</span> channels. There may be slowdowns in the panel, your server's hard drive may crash, please consider these. There is no channel creation limit.
    </div>

    <!-- TABLE CONTROLS -->
    <div class="table-controls">
      <div class="records-select">
        <select>
          <option>60</option>
          <option>30</option>
          <option>100</option>
        </select>
        <label>records per page</label>
      </div>
      <div class="search-input">
        <input type="text" placeholder="Search...">
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
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
            <tr>
              <!-- Name -->
              <td>
                <strong>{{ $channel->name ?? 'N/A' }}</strong>
              </td>

              <!-- Transcoding (3 badges) -->
              <td>
                <span class="badge badge-blue">{{ rand(0, 20) }}</span>
                <span class="badge badge-blue">{{ rand(0, 5) }}</span>
                <span class="badge badge-pink">{{ rand(0, 2) }}</span>
              </td>

              <!-- Playing (yellow badge) -->
              <td>
                <span class="badge badge-yellow">{{ substr($channel->name ?? 'Unknown', 0, 10) }}...</span>
              </td>

              <!-- Bitrate -->
              <td>
                <span class="badge badge-gray">{{ rand(1000, 5000) }}k</span>
              </td>

              <!-- Uptime -->
              <td>
                <span class="badge badge-gray">{{ rand(1, 30) }}d {{ rand(0, 23) }}h {{ rand(0, 59) }}m {{ rand(0, 59) }}s</span>
              </td>

              <!-- Status (dot) -->
              <td>
                <span class="status-dot {{ rand(0, 1) ? '' : 'error' }}"></span>
              </td>

              <!-- Epg -->
              <td>
                📄 EPG
              </td>

              <!-- Size -->
              <td>
                {{ rand(100, 2000) }}M
              </td>

              <!-- Total Time -->
              <td>
                {{ rand(10, 500) }}h {{ rand(0, 59) }}m
              </td>

              <!-- Events (dropdown menu) -->
              <td>
                <div class="dropdown-wrapper">
                  <button class="dropdown-btn" onclick="toggleDropdown(event)">Actions ▼</button>
                  <div class="dropdown-menu">
                    <a onclick="createVideo({{ $channel->id ?? 0 }})">Create Video</a>
                    <a onclick="editPlaylist({{ $channel->id ?? 0 }})">Edit Playlist (2)</a>
                    <a onclick="editVideoEpg({{ $channel->id ?? 0 }})">Edit Video Epg</a>
                    <a onclick="channelEpgLink({{ $channel->id ?? 0 }})">Channel Epg Link</a>
                    <a onclick="convertedVideos({{ $channel->id ?? 0 }})">Converted Videos (5)</a>
                    <a onclick="sendChannelMessage({{ $channel->id ?? 0 }})">Send Message</a>
                    <a onclick="errorVideos({{ $channel->id ?? 0 }})">Error Videos (0)</a>
                  </div>
                </div>
              </td>

              <!-- Actions (3 buttons) -->
              <td>
                <div class="actions-cell">
                  <button class="icon-btn stop" title="Stop" onclick="stopChannel({{ $channel->id ?? 0 }})">⏹</button>
                  <button class="icon-btn edit" title="Edit" onclick="editChannel({{ $channel->id ?? 0 }})">✏️</button>
                  <button class="icon-btn delete" title="Delete" onclick="deleteChannel({{ $channel->id ?? 0 }})">🗑️</button>
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

</div>

<script>
  // Dropdown toggle
  function toggleDropdown(event) {
    event.stopPropagation();
    const wrapper = event.target.parentElement;
    const menu = wrapper.querySelector('.dropdown-menu');
    
    document.querySelectorAll('.dropdown-menu').forEach(m => {
      if (m !== menu) m.classList.remove('active');
    });
    
    menu.classList.toggle('active');
  }

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
  });

  // Server change
  function changeServer(serverId) {
    console.log('Server changed to:', serverId);
    // TODO: reload KPI + table
  }

  // Toolbar actions
  function stopAllChannels() {
    if (confirm('Stop all channels on this server?')) {
      console.log('Stopping all channels');
      // TODO: API call POST /api/vod/server/:id/stop-all
    }
  }

  function startAllChannels() {
    if (confirm('Start all channels on this server?')) {
      console.log('Starting all channels');
      // TODO: API call POST /api/vod/server/:id/start-all
    }
  }

  function channelsEpg() {
    console.log('Opening Channels Epg');
    // TODO: window.location = '/vod-channels/epg'
  }

  function fastChannel() {
    console.log('Fast Channel action');
    // TODO: define action
  }

  function sendMessage() {
    console.log('Send Message modal');
    // TODO: open modal
  }

  // Row Events dropdown actions
  function createVideo(channelId) {
    console.log('Create Video for channel:', channelId);
    // window.location = '/vod-channels/' + channelId + '/create-video';
  }

  function editPlaylist(channelId) {
    console.log('Edit Playlist for channel:', channelId);
    // window.location = '/vod-channels/' + channelId + '/playlist';
  }

  function editVideoEpg(channelId) {
    console.log('Edit Video Epg for channel:', channelId);
    // window.location = '/vod-channels/' + channelId + '/epg';
  }

  function channelEpgLink(channelId) {
    console.log('Channel Epg Link for channel:', channelId);
    // TODO: show link + copy button
  }

  function convertedVideos(channelId) {
    console.log('Converted Videos for channel:', channelId);
    // window.location = '/vod-channels/' + channelId + '/converted-videos';
  }

  function sendChannelMessage(channelId) {
    console.log('Send Message for channel:', channelId);
    // TODO: open modal
  }

  function errorVideos(channelId) {
    console.log('Error Videos for channel:', channelId);
    // window.location = '/vod-channels/' + channelId + '/error-videos';
  }

  // Row Actions
  function stopChannel(channelId) {
    if (confirm('Stop this channel?')) {
      console.log('Stopping channel:', channelId);
      // TODO: API call POST /api/vod/channels/:id/stop
    }
  }

  function editChannel(channelId) {
    console.log('Edit channel:', channelId);
    // window.location = '/vod-channels/' + channelId + '/edit';
  }

  function deleteChannel(channelId) {
    if (confirm('Delete this channel? This action cannot be undone.')) {
      console.log('Deleting channel:', channelId);
      // TODO: API call DELETE /api/vod/channels/:id
    }
  }
</script>

@endsection
