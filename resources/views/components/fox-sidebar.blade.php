@php
  // Helper pentru "active" pe linkuri
  $isActive = fn($pattern) => request()->is($pattern) ? 'bg-[#b31217]' : '';
@endphp

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
       class="flex items-center gap-3 px-5 py-3 text-white/90 hover:bg-white/5 {{ $isActive('dashboard') }}">
      <span class="w-2 h-2 rounded-full bg-white"></span>
      <span>Dashboard</span>
    </a>

    {{-- VOD Channels group --}}
    <div class="mt-2">
      <button type="button"
              class="w-full flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
        <div class="flex items-center gap-3">
          <span class="w-2 h-2 rounded-full bg-white/70"></span>
          <span>Vod Channels</span>
        </div>
        <span class="text-white/40">▾</span>
      </button>

      <div class="pl-8 pb-2">
        <a href="{{ route('vod-channels.create-new') }}"
           class="flex items-center gap-3 px-5 py-2 text-white/80 hover:text-white">
          <span class="w-1.5 h-1.5 rounded-full bg-white/70"></span>
          <span>Create Vod Channel</span>
        </a>
        <a href="{{ route('vod-channels.index') }}"
           class="flex items-center gap-3 px-5 py-2 text-white/80 hover:text-white {{ $isActive('vod-channels') }}">
          <span class="w-1.5 h-1.5 rounded-full bg-white/70"></span>
          <span>Vod Channels</span>
        </a>
      </div>
    </div>

    {{-- Video Categories --}}
    <a href="{{ route('video-categories.index') }}"
       class="flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
      <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-white/70"></span>
        <span>Video Categories</span>
      </div>
      <span class="text-white/40">›</span>
    </a>

    {{-- Encoding Jobs --}}
    <a href="{{ route('encoding-jobs.index') }}"
       class="flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
      <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-white/70"></span>
        <span>Encoding Jobs</span>
      </div>
      <span class="text-white/40">›</span>
    </a>

    {{-- Encode Profiles --}}
    <a href="{{ route('encode-profiles.index') }}"
       class="flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
      <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-white/70"></span>
        <span>Encode Profiles</span>
      </div>
      <span class="text-white/40">›</span>
    </a>

    {{-- Videos --}}
    <a href="{{ route('videos.index') }}"
       class="flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
      <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-white/70"></span>
        <span>Videos</span>
      </div>
      <span class="text-white/40">›</span>
    </a>

    {{-- Media Import --}}
    <a href="{{ route('media.import') }}"
       class="flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
      <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-white/70"></span>
        <span>Media Import</span>
      </div>
      <span class="text-white/40">›</span>
    </a>

    {{-- File Browser --}}
    <a href="{{ route('file-browser.index') }}"
       class="flex items-center justify-between px-5 py-3 text-white/90 hover:bg-white/5">
      <div class="flex items-center gap-3">
        <span class="w-2 h-2 rounded-full bg-white/70"></span>
        <span>File Browser</span>
      </div>
      <span class="text-white/40">›</span>
    </a>
  </nav>

  {{-- highlight bar roșu în dreapta (ca în poză) --}}
  <div class="w-[3px] bg-[#b31217] self-end h-full -mt-[calc(100vh-70px)]"></div>
</aside>
