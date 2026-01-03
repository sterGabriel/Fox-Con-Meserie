{{-- 
  ==============================================
  UNIFIED NAVBAR COMPONENT
  Combines: sidebar + top-navigation + sub-header
  ==============================================
--}}

@php
  // Helper functions
  $isActive = fn($pattern) => request()->is($pattern) ? '#b31217' : '';
  $currentRoute = Route::currentRouteName() ?? '';
  
  // Get system uptime
  $uptimeRaw = '—';
  try {
    $uptimeRaw = trim(shell_exec('cat /proc/uptime 2>/dev/null | awk \'{print $1}\'') ?? '');
    if ($uptimeRaw && is_numeric($uptimeRaw)) {
      $seconds = (int) floor((float) $uptimeRaw);
      $days = intdiv($seconds, 86400);
      $seconds %= 86400;
      $hours = intdiv($seconds, 3600);
      $seconds %= 3600;
      $minutes = intdiv($seconds, 60);
      $uptimeRaw = sprintf('%dd %02dh %02dm', $days, $hours, $minutes);
    } else {
      $uptimeRaw = '—';
    }
  } catch (\Throwable $e) {
    $uptimeRaw = '—';
  }
  
  // Server selector data
  $serverId = request('server_id') ?? 'MQ';
  $servers = [
    'MQ' => 'Server 1',
    'Mg' => 'Server 2',
    'Mw' => 'Server 3',
  ];
@endphp

{{-- ENTERPRISE THEME CSS - Loaded here to ensure it's always present --}}
<link rel="stylesheet" href="{{ asset('assets/css/enterprise-theme.css') }}?v={{ time() }}">

<style>
  html, body { margin: 0; padding: 0; }
  .sidebar-link { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin: 0 8px; border-radius: 10px; color: #cbd5e1; text-decoration: none; }
  .sidebar-link:hover { background: rgba(255,255,255,.06); color: #fff; }
  .sidebar-icon { width: 22px; display: inline-flex; justify-content: center; }
</style>

{{-- WRAPPER pentru sidebar + main content area --}}
<div style="display: flex; min-height: 100vh; margin: 0; padding: 0;">
  
  {{-- =============== SIDEBAR (STÂNGA) =============== --}}
  <aside style="width: 260px; min-width: 260px; height: 100vh; position: sticky; top: 0; background: #111821; color: white; display: flex; flex-direction: column;">
    
    {{-- Logo Header --}}
    <div style="height: 70px; display: flex; align-items: center; gap: 12px; padding: 0 20px; border-bottom: 1px solid rgba(255,255,255,.1);">
      <img src="{{ asset('assets/logo/cal-maro-logo.svg') }}" alt="CAL MARO" style="width: 36px; height: 36px; object-fit: contain;">
      <div style="line-height: 1.2;">
        <div style="color: white; font-weight: 900; font-size: 18px; letter-spacing: 0.05em;">CAL MARO</div>
        <div style="font-size: 12px; color: rgba(255,255,255,.6);">IPTV PANEL</div>
      </div>
    </div>

    {{-- Sidebar Menu --}}
    <nav style="flex: 1; overflow-y: auto; padding: 12px 0;">
      {{-- Dashboard --}}
      <a href="{{ route('dashboard') }}" class="sidebar-link" style="background: {{ $isActive('dashboard') ? '#b31217' : 'transparent' }};">
        <span class="sidebar-icon">📊</span>
        <span>Dashboard</span>
      </a>

      {{-- Users --}}
      <a href="{{ route('fox.users') }}" class="sidebar-link" style="background: {{ $isActive('users*') ? '#b31217' : 'transparent' }};">
        <span class="sidebar-icon">👥</span>
        <span>Users</span>
      </a>

      {{-- VOD Section --}}
      <div style="margin-top: 16px; padding: 0 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; color: #64748b;">VOD</div>

      <a href="{{ route('vod-channels.index') }}" class="sidebar-link" style="background: {{ $isActive('vod-channels*') ? '#b31217' : 'transparent' }};">
        <span class="sidebar-icon">📺</span>
        <span>VOD Channels</span>
      </a>

      <hr style="margin: 16px 8px; border: 0; height: 1px; background: rgba(255,255,255,.1);">

      <a href="{{ route('fox.vod-radio-channels') }}" class="sidebar-link">
        <span class="sidebar-icon">📻</span>
        <span>Vod Radio Channels</span>
      </a>

      <a href="{{ route('fox.live-radio-channels') }}" class="sidebar-link">
        <span class="sidebar-icon">📡</span>
        <span>Live Radio Channels</span>
      </a>

      <a href="{{ route('fox.live-channels') }}" class="sidebar-link">
        <span class="sidebar-icon">📺</span>
        <span>Live Channels</span>
      </a>

      <a href="{{ route('fox.youtube-video-channels') }}" class="sidebar-link">
        <span class="sidebar-icon">▶️</span>
        <span>Youtube Video Channels</span>
      </a>

      <a href="{{ route('fox.youtube-live-channels') }}" class="sidebar-link">
        <span class="sidebar-icon">🔴</span>
        <span>Youtube Live Channels</span>
      </a>

      <a href="{{ route('fox.codec-channels') }}" class="sidebar-link">
        <span class="sidebar-icon">🧩</span>
        <span>Codec Channels</span>
      </a>

      <a href="{{ route('fox.vod-movies') }}" class="sidebar-link">
        <span class="sidebar-icon">🎬</span>
        <span>Vod Movies</span>
      </a>

      <a href="{{ route('fox.series') }}" class="sidebar-link">
        <span class="sidebar-icon">📺</span>
        <span>Series</span>
      </a>

      {{-- Tools Section --}}
      <div style="margin-top: 16px; padding: 0 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; color: #64748b;">TOOLS</div>

      <a href="{{ route('fox.series.rename-muzica') }}" class="sidebar-link" style="background: {{ $isActive('series/rename-muzica') ? '#b31217' : 'transparent' }};">
        <span class="sidebar-icon">✏️</span>
        <span>Rename MUZICA</span>
      </a>

      <a href="{{ route('fox.series.rename-vod') }}" class="sidebar-link" style="background: {{ $isActive('series/rename-vod') ? '#b31217' : 'transparent' }};">
        <span class="sidebar-icon">✏️</span>
        <span>Rename VOD</span>
      </a>

      <a href="{{ route('fox.series.rename-vod.sub') }}" class="sidebar-link" style="background: {{ $isActive('series/rename-vod/sub') ? '#b31217' : 'transparent' }};">
        <span class="sidebar-icon">📝</span>
        <span>VOD Sub</span>
      </a>
    </nav>

    {{-- Red highlight bar --}}
    <div style="width: 3px; background: #b31217; align-self: flex-end; height: 100%; margin-top: calc(-100vh + 70px);"></div>
  </aside>

  {{-- =============== MAIN CONTENT AREA (DREAPTA) =============== --}}
  <main style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
    
    {{-- =============== TOP NAVIGATION =============== --}}
    <nav class="fox-topnav">
      <div class="fox-topnav-left">
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'settings.tmdb') ? 'is-active' : '' }}" href="{{ route('settings.tmdb') }}">Settings</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'video-categories') ? 'is-active' : '' }}" href="{{ route('video-categories.index') }}">Category</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'videos') ? 'is-active' : '' }}" href="{{ route('videos.index') }}">Movies - Musics</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'encoding-jobs') ? 'is-active' : '' }}" href="{{ route('encoding-jobs.index') }}">Log Management</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'fox.fonts') ? 'is-active' : '' }}" href="{{ route('fox.fonts') }}">Fonts</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'fox.advertisements') ? 'is-active' : '' }}" href="{{ route('fox.advertisements') }}">Advertisements</a>
        <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'fox.broadcast') ? 'is-active' : '' }}" href="{{ route('fox.broadcast') }}">Broadcast</a>
      </div>

      <div class="fox-topnav-right">
        <div class="fox-license-badge" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border: none; padding: 8px 16px;">
          <span class="fox-license-label" style="color: rgba(31,41,55,0.8); font-size: 11px;">⏱ Uptime:</span>
          <span class="fox-license-value" style="color: #111827; font-weight: 700;">{{ $uptimeRaw }}</span>
        </div>
        <div class="fox-license-badge">
          <span class="fox-license-label">License Date:</span>
          <span class="fox-license-value">2025-12-31</span>
        </div>
        <div class="fox-user-dropdown">
          <button class="fox-user-btn" onclick="toggleUserDropdown()">
            <span class="fox-user-avatar">👤</span>
            <span class="fox-user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
            <span class="fox-dropdown-caret">▼</span>
          </button>
          <div class="fox-user-menu" id="userMenu">
            <a href="#">Profile</a>
            <a href="{{ route('settings.tmdb') }}">Settings</a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
              @csrf
              <button type="submit" style="width:100%; text-align:left; background:none; border:none; padding:10px 16px; color:#666; cursor:pointer;">Logout</button>
            </form>
          </div>
        </div>
      </div>
    </nav>

    {{-- =============== SUB HEADER (Server Selector) =============== --}}
    <div class="fox-subheader">
      <div class="fox-subheader-left">
        <span class="fox-subheader-label">>> Server</span>
        <select class="fox-server-select" onchange="onServerChange(this.value)">
          @foreach($servers as $value => $label)
            <option value="{{ $value }}" {{ $serverId === $value ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="fox-subheader-right">
        <button class="fox-restart-btn" onclick="onRestartServer()">🔄 Restart</button>
      </div>
    </div>

    {{-- =============== PAGE CONTENT AREA =============== --}}
    <section style="flex: 1; overflow-y: auto; background: #f5f5f5; padding: 5px; padding-top: 96px; padding-bottom: 64px;">
        @yield('content')
    </section>

  </main>

</div>

{{-- =============== FOOTER (FIXED TO BOTTOM) =============== --}}
<footer style="position: fixed; bottom: 0; left: 0; right: 0; background: #111821; padding: 14px 32px; display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #b31217; box-shadow: 0 -4px 12px rgba(0,0,0,0.3); z-index: 100;">
  <div style="color: #fff; font-size: 14px; font-weight: 700;">
    🎬 <strong style="color: #fff;">CAL MARO IPTV Panel</strong>
  </div>
  <div style="color: rgba(255,255,255,0.7); font-size: 13px; font-weight: 600;">
    Version <strong style="color: #fff;">1.0.0</strong> &nbsp;•&nbsp; © {{ date('Y') }}
  </div>
</footer>

{{-- =============== JAVASCRIPT =============== --}}
<script>
// User dropdown toggle
function toggleUserDropdown() {
  const menu = document.getElementById('userMenu');
  if (menu) menu.classList.toggle('is-open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.fox-user-dropdown')) {
    const menu = document.getElementById('userMenu');
    if (menu) menu.classList.remove('is-open');
  }
});

// Server selector change
function onServerChange(serverId) {
  const url = new URL(window.location.href);
  url.searchParams.set('server_id', serverId);
  window.location.href = url.toString();
}

// Restart server
function onRestartServer() {
  if (confirm('Restart server?')) {
    console.log('Restarting server...');
    // TODO: Add API call to restart server
  }
}
</script>
