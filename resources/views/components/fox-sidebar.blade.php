@php
  // Helper pentru "active" pe linkuri
  $isActive = fn($pattern) => request()->is($pattern) ? 'bg-[#b31217]' : '';
@endphp

<style>
  .sidebar-link { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin: 0 8px; border-radius: 10px; color: #cbd5e1; text-decoration: none; }
  .sidebar-link:hover { background: rgba(255,255,255,.06); color: #fff; }
  .sidebar-icon { width: 22px; display: inline-flex; justify-content: center; }
</style>

<aside class="w-[260px] min-w-[260px] h-screen sticky top-0 bg-[#111821] text-white flex flex-col">
  {{-- Logo + hamburger --}}
  <div class="h-[70px] flex items-center justify-between px-5 border-b border-white/10">
    <div class="flex items-center gap-3">
      <div class="text-[#ff7a00] font-extrabold tracking-wide text-xl">FOX CODEC</div>
    </div>
    <button type="button" class="text-white/80 hover:text-white">
      {{-- hamburger icon --}}
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </div>

  {{-- Menu --}}
  <nav class="flex-1 overflow-y-auto py-3">
    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}"
       class="sidebar-link {{ $isActive('dashboard') }}">
      <span class="sidebar-icon">📊</span>
      <span>Dashboard</span>
    </a>

    {{-- VOD Section Header --}}
    <div class="mt-4 px-5 text-xs font-semibold uppercase tracking-wider text-slate-400">VOD</div>

    {{-- VOD Channels --}}
    <a href="{{ route('vod-channels.index') }}"
       class="sidebar-link {{ $isActive('vod-channels*') }}">
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

    <hr class="my-4 border-slate-700/60">

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
  <div class="w-[3px] bg-[#b31217] self-end h-full -mt-[calc(100vh-70px)]"></div>
</aside>
