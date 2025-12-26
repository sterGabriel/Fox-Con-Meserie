@extends('layouts.panel')

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;font-weight:800;">Import Videos</h1>
        <div style="margin-top:4px;font-size:13px;color:var(--text-secondary);">Category: <span style="font-weight:800;color:var(--text-primary);">{{ $category->name }}</span></div>
    </div>
    <a href="{{ route('video-categories.index') }}" style="font-size:13px;font-weight:700;">← Back</a>
</div>

@if ($message = session('success'))
    <div class="fox-table-container" style="padding:12px 16px;margin-bottom:16px;">
        <span class="fox-status-led green"></span>
        <span style="font-size:13px;font-weight:700;color:var(--text-primary);">{{ $message }}</span>
    </div>
@endif

@if ($message = session('error'))
    <div class="fox-table-container" style="padding:12px 16px;margin-bottom:16px;">
        <span class="fox-status-led red"></span>
        <span style="font-size:13px;font-weight:700;color:var(--text-primary);">{{ $message }}</span>
    </div>
@endif

<div class="fox-table-container" style="padding:16px;margin-bottom:16px;">
    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">How to import</div>
    <ol style="margin:0;padding-left:18px;color:var(--text-secondary);font-size:13px;">
        <li>Browse folders and find video files</li>
        <li>Select files (checkboxes)</li>
        <li>Click “Import Selected”</li>
        <li>Page reloads with success message</li>
    </ol>
</div>

<div class="fox-table-container" style="padding:12px 16px;margin-bottom:16px;">
    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Path</div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @foreach ($breadcrumb as $crumb)
            @if ($loop->last)
                <span style="color:var(--text-primary);font-weight:800;">{{ $crumb['name'] }}</span>
            @else
                <a href="?path={{ urlencode($crumb['path']) }}" style="font-weight:700;">{{ $crumb['name'] }}</a>
                <span style="color:var(--text-muted);">/</span>
            @endif
        @endforeach
    </div>
</div>

    <!-- Navigation Buttons -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        @if ($parentPath !== $basePath || $currentPath !== $basePath)
            <a href="?path={{ urlencode($parentPath) }}" style="background:var(--btn-msg);color:var(--card-bg);padding:10px 14px;border-radius:4px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
                <span>⬆</span>
                <span>Up</span>
            </a>
        @endif
        <button type="button" onclick="selectAllFiles()" style="background:var(--btn-start);color:var(--card-bg);padding:10px 14px;border-radius:4px;font-size:13px;font-weight:700;">✓ Select All</button>
        <button type="button" onclick="deselectAllFiles()" style="background:var(--btn-restart);color:var(--card-bg);padding:10px 14px;border-radius:4px;font-size:13px;font-weight:700;">✗ Deselect All</button>
    </div>

    <!-- File Browser Form -->
    <form id="browser-form" method="POST" action="{{ route('admin.video_categories.import', $category) }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr;gap:16px;">
            <!-- Folders Section -->
            @if (!empty($dirs))
            <div class="fox-table-container" style="padding:16px;">
                <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">📁 Folders</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:10px;">
                    @foreach ($dirs as $dir)
                    <a href="?path={{ urlencode($dir['path']) }}" style="border:1px solid var(--border-color);border-radius:6px;padding:12px 14px;background:var(--card-bg);">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:18px;">📂</span>
                            <div style="min-width:0;">
                                <div style="font-weight:800;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $dir['name'] }}</div>
                                <div style="font-size:12px;color:var(--text-muted);">Open folder</div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Videos Section -->
            @if (!empty($files))
            <div class="fox-table-container" style="padding:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--border-light);">
                    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">🎬 Video Files</div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button type="submit" id="import-btn" style="background:var(--btn-msg);color:var(--card-bg);padding:10px 14px;border-radius:4px;font-size:13px;font-weight:800;">📥 Import Selected</button>
                        <span id="selected-count" style="font-size:12px;color:var(--text-secondary);font-weight:700;">(0 selected)</span>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="fox-table">
                        <thead>
                            <tr>
                                <th style="width:80px;">Select</th>
                                <th>Name</th>
                                <th style="width:240px;">Info</th>
                                <th style="width:140px;text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($files as $file)
                            <tr style="{{ $file['imported'] ? 'opacity:0.6;' : '' }}">
                                <td>
                                    <input type="checkbox" class="file-checkbox" name="files[]" value="{{ $file['path'] }}" {{ $file['imported'] ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:var(--text-primary);">{{ $file['name'] }}</div>
                                    @if ($file['imported'])
                                        <span class="fox-badge green">IMPORTED</span>
                                    @endif
                                </td>
                                <td style="font-size:12px;color:var(--text-secondary);">
                                    <div>📏 {{ $file['size_formatted'] }} · ⏱️ {{ $file['duration'] }}</div>
                                    @if ($file['metadata'] && isset($file['metadata']['video']))
                                        <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">
                                            <span class="fox-badge blue">{{ $file['metadata']['video']['width'] }}x{{ $file['metadata']['video']['height'] }}</span>
                                            @if ($file['metadata']['audio'])
                                                <span class="fox-badge yellow">{{ $file['metadata']['audio']['channels'] }} ch</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align:center;white-space:nowrap;">
                                    @if (!$file['imported'])
                                        <button type="button" class="fox-action-btn edit" onclick="previewVideo('{{ $file['path'] }}', '{{ $file['name'] }}')" title="Preview">🎥</button>
                                        <button type="button" class="fox-action-btn start" onclick="importSingle('{{ $file['path'] }}')" title="Import">📥</button>
                                    @else
                                        <span style="color:var(--text-muted);font-size:12px;font-weight:700;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if (empty($dirs) && empty($files))
            <div class="fox-table-container" style="padding:16px;">
                <div style="font-size:13px;color:var(--text-secondary);">📭 No video files found in this folder</div>
            </div>
            @endif
        </div>
    </form>
</div>

<form id="single-import-form" method="POST" action="{{ route('admin.video_categories.import', $category) }}" style="display:none;">
    @csrf
    <input type="hidden" name="files[]" id="single-import-path" value="">
</form>

<!-- Modal for Previews -->
<div id="preview-modal" class="fox-modal-backdrop" aria-hidden="true">
    <div class="fox-modal" role="dialog" aria-modal="true" aria-labelledby="preview-title">
        <div class="fox-modal-header">
            <h3 class="fox-modal-title" id="preview-title">Preview</h3>
            <button type="button" onclick="closePreview()" class="fox-modal-close" aria-label="Close">×</button>
        </div>
        <div class="fox-modal-body">
            <video id="preview-video" controls class="fox-modal-video">
                <source id="preview-source" src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        <div class="fox-modal-footer">
            <button type="button" onclick="closePreview()" class="fox-modal-secondary">Close</button>
        </div>
    </div>
</div>

<script>
// Select/Deselect functions
function selectAllFiles() {
    document.querySelectorAll('.file-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
    updateSelectedCount();
}

function deselectAllFiles() {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.file-checkbox:checked:not(:disabled)').length;
    const span = document.getElementById('selected-count');
    if (span) span.textContent = '(' + selected + ' selected)';
}

// Preview function
function previewVideo(path, name) {
    document.getElementById('preview-title').textContent = '⏳ Loading...';
    document.getElementById('preview-modal').classList.add('is-open');
    
    fetch('{{ route("video-categories.preview") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ path: path }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('preview-title').textContent = 'Preview: ' + name;
            document.getElementById('preview-source').src = data.url + '?t=' + Date.now();
            document.getElementById('preview-video').load();
        } else {
            alert('Preview failed');
            closePreview();
        }
    })
    .catch(e => {
        alert('Error: ' + e.message);
        closePreview();
    });
}

function closePreview() {
    document.getElementById('preview-modal').classList.remove('is-open');
    document.getElementById('preview-video').pause();
}

function importSingle(path) {
    const input = document.getElementById('single-import-path');
    const form = document.getElementById('single-import-form');
    if (!input || !form) return;
    input.value = path;
    form.submit();
}

// Setup on page load
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox change listeners
    document.querySelectorAll('.file-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    
    // Form submission
    const form = document.getElementById('browser-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.file-checkbox:checked:not(:disabled)').length;
            if (selected === 0) {
                e.preventDefault();
                alert('Please select at least one file');
                return false;
            }
            return true;
        });
    }
    
    updateSelectedCount();
});
</script>

<style>
.fox-modal-backdrop {
    position: fixed;
    inset: 0;
    padding: 16px;
    background: rgba(0, 0, 0, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.fox-modal-backdrop.is-open {
    display: flex;
}

.fox-modal {
    width: 100%;
    max-width: 900px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.fox-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-light);
}

.fox-modal-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--text-primary);
}

.fox-modal-close {
    background: transparent;
    color: var(--text-secondary);
    font-size: 22px;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 4px;
}

.fox-modal-close:hover {
    background: rgba(0, 0, 0, 0.06);
}

.fox-modal-body {
    padding: 16px;
}

.fox-modal-video {
    width: 100%;
    max-height: 420px;
    border-radius: 6px;
    background: rgba(0, 0, 0, 0.9);
}

.fox-modal-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--border-light);
    display: flex;
    justify-content: flex-end;
}

.fox-modal-secondary {
    background: var(--btn-restart);
    color: var(--card-bg);
    padding: 10px 14px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 700;
}

#import-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
@endsection
