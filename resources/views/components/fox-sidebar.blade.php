@php
    $isActiveRoute = fn (...$names) => request()->routeIs(...$names);
    $isToolsOpen = $isActiveRoute('fox.series.rename-vod', 'fox.series.rename-vod.sub');
    $isSettingsOpen = $isActiveRoute('settings.tmdb');
@endphp

<aside class="fox-sidebar">
    <div class="fox-brand">
        <div class="fox-brand-left">
            <img src="{{ asset('assets/logo/cal-maro-logo.svg') }}" alt="CAL MARO" style="width:26px;height:26px;object-fit:contain;" />
            <div>
                <div class="fox-brand-text">CAL MARO</div>
                <div style="font-size:11px;color:var(--fox-muted);font-weight:600;letter-spacing:.04em;">IPTV PANEL</div>
            </div>
        </div>
    </div>

    <nav class="fox-nav">
        <a class="fox-item {{ $isActiveRoute('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
            <span class="fox-ico ico-home" aria-hidden="true"></span>
            <span class="fox-label">Dashboard</span>
        </a>

        <a class="fox-item {{ $isActiveRoute('vod-channels.*') ? 'is-active' : '' }}" href="{{ route('vod-channels.index') }}">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">VOD Channels</span>
        </a>

        <a class="fox-item {{ $isActiveRoute('encoding-jobs.*') ? 'is-active' : '' }}" href="{{ route('encoding-jobs.index') }}">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">Encoding Jobs</span>
        </a>

        <a class="fox-item {{ $isActiveRoute('videos.*') ? 'is-active' : '' }}" href="{{ route('videos.index') }}">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">Videos</span>
        </a>

        <a class="fox-item {{ $isActiveRoute('video-categories.*', 'admin.video_categories.*') ? 'is-active' : '' }}" href="{{ route('video-categories.index') }}">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">Video Categories</span>
        </a>

        <a class="fox-item {{ $isActiveRoute('media.import*') ? 'is-active' : '' }}" href="{{ route('media.import') }}">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">Import Media</span>
        </a>

        <button type="button" class="fox-item fox-parent {{ $isToolsOpen ? 'is-open' : '' }}" data-fox="submenu">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">Tools</span>
            <span class="fox-caret" aria-hidden="true"></span>
        </button>
        <div class="fox-sub {{ $isToolsOpen ? 'is-open' : '' }}">
            <a class="fox-subitem {{ $isActiveRoute('fox.series.rename-vod') ? 'is-active' : '' }}" href="{{ route('fox.series.rename-vod') }}">
                <span class="fox-dot" aria-hidden="true"></span>
                <span class="fox-sublabel">Rename VOD</span>
            </a>
            <a class="fox-subitem {{ $isActiveRoute('fox.series.rename-vod.sub') ? 'is-active' : '' }}" href="{{ route('fox.series.rename-vod.sub') }}">
                <span class="fox-dot" aria-hidden="true"></span>
                <span class="fox-sublabel">VOD Sub</span>
            </a>
        </div>

        <button type="button" class="fox-item fox-parent {{ $isSettingsOpen ? 'is-open' : '' }}" data-fox="submenu">
            <span class="fox-ico ico-folder" aria-hidden="true"></span>
            <span class="fox-label">Settings</span>
            <span class="fox-caret" aria-hidden="true"></span>
        </button>
        <div class="fox-sub {{ $isSettingsOpen ? 'is-open' : '' }}">
            <a class="fox-subitem {{ $isActiveRoute('settings.tmdb') ? 'is-active' : '' }}" href="{{ route('settings.tmdb') }}">
                <span class="fox-dot" aria-hidden="true"></span>
                <span class="fox-sublabel">TMDb</span>
            </a>
        </div>
    </nav>
</aside>
