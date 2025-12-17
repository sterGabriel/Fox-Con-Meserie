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
  {{-- Logo + hamburger --}}
  <div style="height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; border-bottom: 1px solid rgba(255,255,255,.1);">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="color: #ff7a00; font-weight: 900; letter-spacing: 0.05em; font-size: 20px;">FOX CODEC</div>
    </div>
    <button type="button" style="color: rgba(255,255,255,.8); background: none; border: none; cursor: pointer;">
      {{-- hamburger icon --}}
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </div>

  {{-- Menu --}}
  <nav style="flex: 1; overflow-y: auto; padding: 12px 0;">
    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}"
       class="sidebar-link" style="background: {{ $isActive('dashboard') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">📊</span>
      <span>Dashboard</span>
    </a>

    {{-- VOD Section Header --}}
    <div style="margin-top: 16px; padding: 0 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; color: #64748b;">VOD</div>

    {{-- VOD Channels --}}
    <a href="{{ route('vod-channels.index') }}"
       class="sidebar-link" style="background: {{ $isActive('vod-channels*') ? '#b31217' : 'transparent' }};">
      <span class="sidebar-icon">📺</span>
      <span>VOD Channels</span>
    </a>

    {{-- Categories --}}
    <a href="{{ route('video-categories.index') }}"
       class="sidebar-link">
      <span class="sidebar-icon">🏷️</span>
      <span>Categories</span>
    </a>

    {{-- Videos --}}
    <a href="{{ route('videos.index') }}"
       class="sidebar-link">
      <span class="sidebar-icon">🎞️</span>
      <span>Videos</span>
    </a>

    {{-- Encoding Jobs --}}
    <a href="{{ route('encoding-jobs.index') }}"
       class="sidebar-link">
      <span class="sidebar-icon">⚙️</span>
      <span>Encoding Jobs</span>
    </a>

    <hr style="margin: 16px 8px; border: 0; height: 1px; background: rgba(255,255,255,.1);">

    {{-- VOD Radio Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">📻</span>
      <span>Vod Radio Channels</span>
    </a>

    {{-- Live Radio Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">📡</span>
      <span>Live Radio Channels</span>
    </a>

    {{-- Live Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">📺</span>
      <span>Live Channels</span>
    </a>

    {{-- Youtube Video Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">▶️</span>
      <span>Youtube Video Channels</span>
    </a>

    {{-- Youtube Live Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">🔴</span>
      <span>Youtube Live Channels</span>
    </a>

    {{-- Codec Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">🧩</span>
      <span>Codec Channels</span>
    </a>

    {{-- VOD Movies --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">🎬</span>
      <span>Vod Movies</span>
    </a>

    {{-- Series --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">📺</span>
      <span>Series</span>
    </a>

    {{-- Trailer Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">🎞️</span>
      <span>Trailer Channels</span>
    </a>

    {{-- IMDB Trailer Channels --}}
    <a href="#"
       class="sidebar-link">
      <span class="sidebar-icon">⭐</span>
      <span>Imdb Trailer Channels</span>
    </a>
  </nav>

  {{-- highlight bar roșu în dreapta (ca în poză) --}}
  <div style="width: 3px; background: #b31217; align-self: flex-end; height: 100%; margin-top: calc(-100vh + 70px);"></div>
</aside>
