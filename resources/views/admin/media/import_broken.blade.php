@extends('layouts.panel')

@section('content')
<!-- Page Header -->
<div class="g-flex g-items-center g-justify-between g-mb-xl">
    <div>
        <h1 class="g-page-title">Import Videos from Server</h1>
        <p class="g-page-subtitle">Browse and import media files: <code style="background: var(--g-panel-bg); padding: 4px 8px; border-radius: 4px; font-size: 12px;">{{ $mediaRoot }}</code></p>
    </div>
    <a href="{{ route('media.import') }}" class="g-btn g-btn-secondary g-btn-sm">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
        </svg>
        Root Directory
    </a>
</div>

<!-- Alerts -->
@if($errors->any())
    <div class="g-alert error" style="margin-bottom: var(--spacing-4);">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <div>
            <div class="g-alert-title">Errors occurred</div>
            <ul style="margin: 8px 0 0 20px; list-style: disc;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="g-alert success" style="margin-bottom: var(--spacing-4);">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <div>
            <div class="g-alert-title">Success</div>
            <div class="g-alert-message">{{ session('success') }}</div>
            @if(session('imported'))
                <div style="margin-top: 8px; font-size: 13px;">Imported: {{ implode(', ', session('imported')) }}</div>
            @endif
            @if(session('skipped'))
                <div style="margin-top: 4px; font-size: 13px; color: var(--g-warning-text);">Skipped: {{ implode(', ', session('skipped')) }}</div>
            @endif
        </div>
    </div>
@endif

<form method="POST" action="{{ route('media.import.store') }}">
    @csrf
    
    <div class="g-grid" style="grid-template-columns: 1fr 350px; gap: var(--spacing-5);">
        <!-- File Browser Panel -->
        <div class="g-panel">
            <div class="g-panel-header">
                <h3 class="g-panel-title">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    </svg>
                    Available Videos
                </h3>
            </div>

            <!-- Breadcrumb -->
            <div style="padding: var(--spacing-3) var(--spacing-4); background: var(--g-panel-header); border-bottom: 1px solid var(--g-panel-border); font-family: 'Courier New', monospace; font-size: 12px; color: var(--text-secondary);">
                / @if($path) 
                    @php
                        $parts = explode('/', $path);
                        $current = '';
                    @endphp
                    @foreach($parts as $part)
                        @php $current .= ($current ? '/' : '') . $part; @endphp
                        <a href="{{ route('media.import', ['path' => $current]) }}" style="color: var(--g-brand-primary); text-decoration: none;">{{ $part }}</a> /
                    @endforeach
                @endif
            </div>

            <!-- Search Bar -->
            <div style="padding: var(--spacing-4); border-bottom: 1px solid var(--g-panel-border);">
                <div class="g-input-group">
                    <input type="text" 
                           name="search" 
                           placeholder="Search files..." 
                           value="{{ $search }}" 
                           class="g-input"
                           id="search-input">
                </div>
            </div>

            <!-- File Table -->
            <div class="g-table-wrapper">
                <table class="g-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="select-all-files" class="g-checkbox">
                            </th>
                            <th>Name</th>
                            <th style="width: 120px; text-align: right;">Size</th>
                            <th style="width: 180px;">Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($parent !== null)
                            <tr>
                                <td></td>
                                <td colspan="3">
                                    <a href="{{ route('media.import', ['path' => $parent]) }}" class="g-link" style="display: flex; align-items: center; gap: 8px;">
                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        .. (Parent Directory)
                                    </a>
                                </td>
                            </tr>
                        @endif

                        <!-- Folders -->
                        @forelse(collect($items)->where('type', 'folder') as $item)
                            <tr>
                                <td></td>
                                <td>
                                    <a href="{{ route('media.import', ['path' => $item['path']]) }}" class="g-link" style="display: flex; align-items: center; gap: 8px;">
                                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" style="color: var(--g-warning-text);">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                        </svg>
                                        {{ $item['name'] }}
                                        <span class="g-badge default" style="font-size: 10px;">{{ $item['children_count'] }} items</span>
                                    </a>
                                </td>
                                <td style="text-align: right; color: var(--text-secondary);">—</td>
                                <td style="color: var(--text-secondary); font-size: 12px;">{{ date('M d, Y H:i', $item['modified']) }}</td>
                            </tr>
                        @empty
                        @endforelse

                        <!-- Video Files -->
                        @forelse(collect($items)->where('type', 'file') as $item)
                            @php
                                $isImported = in_array($item['full_path'], $existingVideos);
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" 
                                           name="files[]" 
                                           value="{{ $item['path'] }}" 
                                           class="file-checkbox g-checkbox"
                                           data-size="{{ $item['size'] }}"
                                           {{ $isImported ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" style="color: var(--g-info-text);">
                                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                        </svg>
                                        <div>
                                            <div style="font-weight: 500;">{{ $item['name'] }}</div>
                                            @if($isImported)
                                                <div class="g-badge success" style="font-size: 10px; margin-top: 4px;">Already imported</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: right; font-family: 'Courier New', monospace; font-size: 12px; color: var(--text-secondary);">
                                    @php
                                        $bytes = $item['size'];
                                        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                                        $i = floor(log($bytes > 0 ? $bytes : 1, 1024));
                                        echo round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
                                    @endphp
                                </td>
                                <td style="color: var(--text-secondary); font-size: 12px;">{{ date('M d, Y H:i', $item['modified']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="g-empty-state">
                                    <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 8px;">
                                        <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                    </svg>
                                    <div style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                        @if($search)
                                            No videos matching "{{ $search }}"
                                        @else
                                            No video files in this folder
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar Settings -->
        <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
            <!-- Category Selection -->
            <div class="g-panel">
                <div class="g-panel-header">
                    <h3 class="g-panel-title" style="font-size: 14px;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        Category
                    </h3>
                </div>
                <div class="g-panel-body">
                    <select name="category_id" class="g-select" style="width: 100%;">
                        <option value="">-- No Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Selection Stats -->
            <div class="g-panel">
                <div class="g-panel-header">
                    <h3 class="g-panel-title" style="font-size: 14px;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        Selection
                    </h3>
                </div>
                <div class="g-panel-body">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-secondary); font-size: 13px;">Selected:</span>
                            <span id="selected-count" style="font-weight: 600; font-size: 18px; color: var(--g-brand-primary);">0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-secondary); font-size: 13px;">Total Size:</span>
                            <span id="selected-size" style="font-weight: 600; font-size: 14px; font-family: 'Courier New', monospace;">0 B</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <button type="submit" id="submit-btn" disabled class="g-btn g-btn-success" style="width: 100%; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Import Selected
            </button>

            <button type="button" id="toggle-all" class="g-btn g-btn-secondary" style="width: 100%; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
                Select All Files
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-files');
    const fileCheckboxes = document.querySelectorAll('.file-checkbox:not(:disabled)');
    const submitBtn = document.getElementById('submit-btn');
    const toggleAllBtn = document.getElementById('toggle-all');
    const selectedCountEl = document.getElementById('selected-count');
    const selectedSizeEl = document.getElementById('selected-size');
    const searchInput = document.getElementById('search-input');

    function updateStats() {
        const checked = document.querySelectorAll('.file-checkbox:checked').length;
        const totalSize = Array.from(document.querySelectorAll('.file-checkbox:checked')).reduce((sum, cb) => {
            return sum + parseFloat(cb.dataset.size || 0);
        }, 0);

        selectedCountEl.textContent = checked;
        selectedSizeEl.textContent = formatBytes(totalSize);
        submitBtn.disabled = checked === 0;

        const visibleCount = fileCheckboxes.length;
        selectAllCheckbox.checked = checked > 0 && checked === visibleCount;
        selectAllCheckbox.indeterminate = checked > 0 && checked < visibleCount;
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    selectAllCheckbox.addEventListener('change', function() {
        fileCheckboxes.forEach(cb => cb.checked = this.checked);
        updateStats();
    });

    toggleAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const allChecked = Array.from(fileCheckboxes).every(cb => cb.checked);
        fileCheckboxes.forEach(cb => cb.checked = !allChecked);
        updateStats();
    });

    fileCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateStats);
    });

    searchInput?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            window.location.href = '{{ route('media.import') }}' + '?path={{ $path }}&search=' + encodeURIComponent(this.value);
        }
    });

    updateStats();
});
</script>
@endsection
