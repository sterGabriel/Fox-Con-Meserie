@php
  // Helper pentru "active" pe linkuri
  $isActive = fn($pattern) => request()->is($pattern) ? '#b31217' : '';
@endphp

<style>
  .sidebar-link { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin: 0 8px; border-radius: 10px; color: #cbd5e1; text-decoration: none; }
  .sidebar-link:hover { background: rgba(255,255,255,.06); color: #fff; }
  .sidebar-icon { width: 22px; display: inline-flex; justify-content: center; }
</style>

<aside style="width: 260px; min-width: 260px; height: 100vh; position: sticky; top: 0; background: #111821; color: white; display: flex; flex-direction: column;">
  {{-- HEADER SIDEBAR --}}
  <div style="height: 70px; display: flex; align-items: center; gap: 12px; padding: 0 20px; border-bottom: 1px solid rgba(255,255,255,.1);">
    <img
        src="{{ asset('assets/logo/cal-maro-logo.svg') }}"
        alt="CAL MARO"
        style="width: 36px; height: 36px; object-fit: contain;"
    >

    <div style="line-height: 1.2;">
        <div style="color: white; font-weight: 900; font-size: 18px; letter-spacing: 0.05em;">
            CAL MARO
        </div>
        <div style="font-size: 12px; color: rgba(255,255,255,.6);">
            IPTV PANEL
        </div>
    </div>
  </div>

  {{-- Menu --}}
  <nav style="flex: 1; overflow-y: auto; padding: 12px 0;">
    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}"
       class="sidebar-link" style="background: {{ $isActive('dashboard') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">📊</span>
      <span>Dashboard</span>
    </a>

    {{-- Users --}}
    <a href="{{ route('fox.users') }}"
       class="sidebar-link" style="background: {{ $isActive('users*') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">👥</span>
      <span>Users</span>
    </a>

    {{-- VOD Section Header --}}
    <div style="margin-top: 16px; padding: 0 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; color: #64748b;">VOD</div>

    {{-- VOD Channels --}}
    <a href="{{ route('vod-channels.index') }}"
       class="sidebar-link" style="background: {{ $isActive('vod-channels*') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">📺</span>
      <span>VOD Channels</span>
    </a>

    <hr style="margin: 16px 8px; border: 0; height: 1px; background: rgba(255,255,255,.1);">

    {{-- VOD Radio Channels --}}
    <a href="{{ route('fox.vod-radio-channels') }}"
       class="sidebar-link">
      <span class="sidebar-icon">📻</span>
      <span>Vod Radio Channels</span>
    </a>

    {{-- Live Radio Channels --}}
    <a href="{{ route('fox.live-radio-channels') }}"
       class="sidebar-link">
      <span class="sidebar-icon">📡</span>
      <span>Live Radio Channels</span>
    </a>

    {{-- Live Channels --}}
    <a href="{{ route('fox.live-channels') }}"
       class="sidebar-link">
      <span class="sidebar-icon">📺</span>
      <span>Live Channels</span>
    </a>

    {{-- Youtube Video Channels --}}
    <a href="{{ route('fox.youtube-video-channels') }}"
       class="sidebar-link">
      <span class="sidebar-icon">▶️</span>
      <span>Youtube Video Channels</span>
    </a>

    {{-- Youtube Live Channels --}}
    <a href="{{ route('fox.youtube-live-channels') }}"
       class="sidebar-link">
      <span class="sidebar-icon">🔴</span>
      <span>Youtube Live Channels</span>
    </a>

    {{-- Codec Channels --}}
    <a href="{{ route('fox.codec-channels') }}"
       class="sidebar-link">
      <span class="sidebar-icon">🧩</span>
      <span>Codec Channels</span>
    </a>

    {{-- VOD Movies --}}
    <a href="{{ route('fox.vod-movies') }}"
       class="sidebar-link">
      <span class="sidebar-icon">🎬</span>
      <span>Vod Movies</span>
    </a>

    {{-- Series --}}
    <a href="{{ route('fox.series') }}"
       class="sidebar-link">
      <span class="sidebar-icon">📺</span>
      <span>Series</span>
    </a>

    {{-- Tools Section Header --}}
    <div style="margin-top: 16px; padding: 0 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; color: #64748b;">TOOLS</div>

    {{-- Series: Rename MUZICA --}}
    <a href="{{ route('fox.series.rename-muzica') }}"
       class="sidebar-link" style="background: {{ $isActive('series/rename-muzica') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">✏️</span>
      <span>Rename MUZICA</span>
    </a>

    {{-- Series: Rename VOD (Movies + TV) --}}
    <a href="{{ route('fox.series.rename-vod') }}"
       class="sidebar-link" style="background: {{ $isActive('series/rename-vod') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">✏️</span>
      <span>Rename VOD</span>
    </a>

    {{-- Rename VOD: Subtitles --}}
    <a href="{{ route('fox.series.rename-vod.sub') }}"
       class="sidebar-link" style="background: {{ $isActive('series/rename-vod/sub') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">📝</span>
      <span>VOD Sub</span>
    </a>
  </nav>

  {{-- highlight bar roșu în dreapta (ca în poză) --}}
  <div style="width: 3px; background: #b31217; align-self: flex-end; height: 100%; margin-top: calc(-100vh + 70px);"></div>
</aside>
