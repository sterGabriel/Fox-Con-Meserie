@php
  // Get current route name
  $currentRoute = Route::currentRouteName();
  
  // Helper to check if route is active
  function isActive($routeName, $prefix = null) {
    $current = Route::currentRouteName();
    if ($prefix) {
      return str_starts_with($current, $prefix);
    }
    return $current === $routeName;
  }
@endphp

<aside class="fox-sidebar">
  <div class="fox-brand">
    <div class="fox-brand-left">
      <span class="fox-logo"></span>
      <span class="fox-brand-text">FOX CODEC</span>
    </div>
    <button class="fox-burger" type="button" aria-label="Toggle sidebar" data-fox="sidebar-toggle">
      ☰
    </button>
  </div>

  <nav class="fox-nav">
    <!-- DASHBOARD -->
    <a class="fox-item {{ isActive('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
      <span class="fox-ico ico-home"></span>
      <span class="fox-label">Dashboard</span>
    </a>

    <!-- USERS (commented out - routes not yet implemented) -->
    {{-- <button class="fox-item fox-parent {{ str_starts_with($currentRoute, 'users') ? 'is-open' : '' }}" type="button" data-fox="submenu">
      <span class="fox-ico ico-user"></span>
      <span class="fox-label">Users</span>
      <span class="fox-caret"></span>
    </button>
    <div class="fox-sub {{ str_starts_with($currentRoute, 'users') ? 'is-open' : '' }}">
      <a class="fox-subitem {{ isActive('users.create') ? 'is-active' : '' }}" href="{{ route('users.create') }}">
        <span class="fox-dot"></span><span class="fox-sublabel">User Add</span>
      </a>
      <a class="fox-subitem {{ isActive('users.index') ? 'is-active' : '' }}" href="{{ route('users.index') }}">
        <span class="fox-dot"></span><span class="fox-sublabel">User List</span>
      </a>
    </div> --}}

    <!-- VOD CHANNELS -->
    <button class="fox-item fox-parent {{ str_starts_with($currentRoute, 'vod-channels') ? 'is-open' : '' }}" type="button" data-fox="submenu">
      <span class="fox-ico ico-folder"></span>
      <span class="fox-label">Vod Channels</span>
      <span class="fox-caret"></span>
    </button>
    <div class="fox-sub {{ str_starts_with($currentRoute, 'vod-channels') ? 'is-open' : '' }}">
      <a class="fox-subitem {{ isActive('vod-channels.create-new') ? 'is-active' : '' }}" href="{{ route('vod-channels.create-new') }}">
        <span class="fox-dot"></span><span class="fox-sublabel">Create Vod Channel</span>
      </a>
      <a class="fox-subitem {{ isActive('vod-channels.index') ? 'is-active' : '' }}" href="{{ route('vod-channels.index') }}">
        <span class="fox-dot"></span><span class="fox-sublabel">Vod Channels</span>
      </a>
    </div>
  </nav>
</aside>
