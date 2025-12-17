@php
  $currentRoute = Route::currentRouteName() ?? '';
@endphp

<nav class="fox-topnav">
  <div class="fox-topnav-left">
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
      Dashboard
    </a>
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'category') ? 'is-active' : '' }}" href="#">
      Category
    </a>
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'videos') ? 'is-active' : '' }}" href="#">
      Movies - Musics
    </a>
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'logs') ? 'is-active' : '' }}" href="#">
      Log Management
    </a>
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'fonts') ? 'is-active' : '' }}" href="#">
      Fonts
    </a>
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'advertisements') ? 'is-active' : '' }}" href="#">
      Advertisements
    </a>
    <a class="fox-topnav-item {{ str_starts_with($currentRoute, 'broadcast') ? 'is-active' : '' }}" href="#">
      Broadcast
    </a>
  </div>

  <div class="fox-topnav-right">
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
        <a href="#">Settings</a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
          @csrf
          <button type="submit" style="width:100%; text-align:left; background:none; border:none; padding:10px 16px; color:#666; cursor:pointer;">
            Logout
          </button>
        </form>
      </div>
    </div>
  </div>
</nav>

<script>
function toggleUserDropdown() {
  const menu = document.getElementById('userMenu');
  if (menu) {
    menu.classList.toggle('is-open');
  }
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.fox-user-dropdown')) {
    const menu = document.getElementById('userMenu');
    if (menu) menu.classList.remove('is-open');
  }
});
</script>
