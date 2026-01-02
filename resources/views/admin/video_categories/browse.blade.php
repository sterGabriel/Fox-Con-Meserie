@extends('layouts.panel')

@section('content')
@php
    /** @var array<int, array<string, mixed>> $importedVideos */
    $importedVideos = $importedVideos ?? [];

    $isMuzicaCategory = \Illuminate\Support\Str::slug((string) ($category->name ?? '')) === 'muzica';
@endphp
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

<div class="fox-table-container" style="padding:0;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid var(--border-light);">
        <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">✅ Videoclipuri deja importate</div>
        <div style="font-size:12px;color:var(--text-secondary);font-weight:700;">{{ count($importedVideos) }} items</div>
    </div>

    @if (empty($importedVideos))
        <div style="padding:14px 16px;color:var(--text-secondary);font-size:13px;">
            Nu există încă videoclipuri importate în această categorie.
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="fox-table">
                <thead>
                    <tr>
                        <th style="width:80px;">Poster</th>
                        <th>Titlu</th>
                        <th>Fișier</th>
                        <th style="width:240px;">Cale</th>
                        <th style="width:140px;text-align:center;">TMDB</th>
                        <th style="width:140px;text-align:center;">Deschide</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($importedVideos as $v)
                    <tr style="{{ !($v['exists'] ?? false) ? 'opacity:0.65;' : '' }}">
                        <td style="text-align:center;">
                            @if (!empty($v['tmdb_poster_path']))
                                <img
                                    src="https://image.tmdb.org/t/p/w92{{ $v['tmdb_poster_path'] }}"
                                    alt="Poster"
                                    style="width:46px;height:auto;border-radius:6px;border:1px solid var(--border-color);display:inline-block;"
                                    loading="lazy"
                                >
                            @else
                                <span id="tmdb-poster-{{ (int) ($v['id'] ?? 0) }}" data-video-id="{{ (int) ($v['id'] ?? 0) }}" style="color:var(--text-muted);font-size:12px;font-weight:700;">—</span>
                            @endif
                        </td>
                        <td style="font-weight:700;color:var(--text-primary);">
                            {{ $v['title'] ?? '—' }}
                            @if (!($v['exists'] ?? false))
                                <span class="fox-badge red" style="margin-left:6px;">MISSING</span>
                            @endif
                        </td>
                        <td style="color:var(--text-secondary);font-size:12px;">
                            {{ $v['filename'] ?? '—' }}
                        </td>
                        <td style="color:var(--text-muted);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:1px;">
                            {{ $v['path'] ?? '—' }}
                        </td>
                        <td style="text-align:center;white-space:nowrap;">
                            <button
                                type="button"
                                class="fox-action-btn start"
                                data-video-id="{{ (int) ($v['id'] ?? 0) }}"
                                data-title='@json((string)($v['title'] ?? ""))'
                                onclick="openTmdbInfo(this)"
                                title="TMDB Info"
                            >ℹ️</button>
                        </td>
                        <td style="text-align:center;white-space:nowrap;">
                            @if (!empty($v['dir']))
                                <a class="fox-action-btn edit" href="?path={{ urlencode($v['dir']) }}" title="Deschide folder">📁</a>
                            @else
                                <span style="color:var(--text-muted);font-size:12px;font-weight:700;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

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
            @if (!empty($dirs) && !$isMuzicaCategory)
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

<!-- Modal for TMDB Info -->
<div id="tmdb-modal" class="fox-modal-backdrop" aria-hidden="true">
    <div class="fox-modal" role="dialog" aria-modal="true" aria-labelledby="tmdb-title">
        <div class="fox-modal-header">
            <h3 class="fox-modal-title" id="tmdb-title">TMDB Info</h3>
            <button type="button" onclick="closeTmdb()" class="fox-modal-close" aria-label="Close">×</button>
        </div>
        <div class="fox-modal-body">
            <div id="tmdb-body" style="color:var(--text-secondary);font-size:13px;">⏳ Loading...</div>
        </div>
        <div class="fox-modal-footer">
            <button type="button" onclick="closeTmdb()" class="fox-modal-secondary">Close</button>
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

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openTmdbInfo(btnEl) {
    const videoId = parseInt(btnEl?.getAttribute('data-video-id') || '', 10);
    const title = btnEl?.getAttribute('data-title') || '';
    if (!Number.isFinite(videoId) || videoId <= 0) return;

    document.getElementById('tmdb-title').textContent = 'TMDB Info: ' + (title || ('Video #' + videoId));
    const body = document.getElementById('tmdb-body');
    body.textContent = '⏳ Loading...';
    document.getElementById('tmdb-modal').classList.add('is-open');

    fetch(`/api/videos/${videoId}/tmdb-details`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        cache: 'no-store'
    })
        .then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'TMDB request failed');
            return data;
        })
        .then((data) => {
            if (!data.ok) throw new Error(data.message || 'TMDB lookup failed');

            const d = data.details || {};
            const name = d.title || d.name || '';
            const overview = d.overview || '';
            const date = d.release_date || d.first_air_date || '';
            const runtime = d.runtime ? `${d.runtime} min` : '';
            const genres = Array.isArray(d.genres) ? d.genres.join(', ') : '';
            const rating = (d.vote_average != null) ? `${d.vote_average} (${d.vote_count || 0})` : '';

            const posterUrl = data.poster_url || '';
            const backdropUrl = data.backdrop_url || '';

            body.innerHTML = `
                <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="width:140px;flex:0 0 auto;">
                        ${posterUrl ? `<img src="${escapeHtml(posterUrl)}" alt="Poster" style="width:140px;border-radius:6px;border:1px solid var(--border-color);" loading="lazy">` : `<div style="color:var(--text-muted);font-size:12px;font-weight:700;">No poster</div>`}
                    </div>
                    <div style="min-width:240px;flex:1 1 320px;">
                        <div style="font-size:16px;font-weight:900;color:var(--text-primary);margin-bottom:6px;">${escapeHtml(name || title || '')}</div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:12px;color:var(--text-secondary);font-weight:700;margin-bottom:10px;">
                            ${date ? `<span class="fox-badge blue">${escapeHtml(date)}</span>` : ''}
                            ${runtime ? `<span class="fox-badge yellow">${escapeHtml(runtime)}</span>` : ''}
                            ${rating ? `<span class="fox-badge green">⭐ ${escapeHtml(rating)}</span>` : ''}
                            ${genres ? `<span class="fox-badge">${escapeHtml(genres)}</span>` : ''}
                        </div>
                        ${overview ? `<div style="color:var(--text-secondary);font-size:13px;line-height:1.45;">${escapeHtml(overview)}</div>` : `<div style="color:var(--text-muted);font-size:12px;font-weight:700;">No overview</div>`}
                    </div>
                </div>
                ${backdropUrl ? `<div style="margin-top:14px;"><img src="${escapeHtml(backdropUrl)}" alt="Backdrop" style="width:100%;max-height:240px;object-fit:cover;border-radius:6px;border:1px solid var(--border-color);" loading="lazy"></div>` : ''}
            `;
        })
        .catch((e) => {
            body.textContent = '❌ ' + (e.message || 'Failed to load TMDB info');
        });
}

function closeTmdb() {
    document.getElementById('tmdb-modal').classList.remove('is-open');
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

    // Auto-fetch missing TMDB posters for imported videos (non-blocking).
    // Uses existing /api/videos/tmdb-scan (max 10 ids per request).
    (function tmdbPrefetchPosters() {
        const nodes = Array.from(document.querySelectorAll('[id^="tmdb-poster-"][data-video-id]'));
        const ids = nodes
            .map(n => parseInt(n.getAttribute('data-video-id') || '', 10))
            .filter(v => Number.isFinite(v) && v > 0);

        if (ids.length === 0) return;

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const chunks = [];
        for (let i = 0; i < ids.length; i += 10) {
            chunks.push(ids.slice(i, i + 10));
        }

        const applyPoster = (videoId, posterPath) => {
            const el = document.getElementById('tmdb-poster-' + videoId);
            if (!el) return;
            if (!posterPath) return;

            const img = document.createElement('img');
            img.src = 'https://image.tmdb.org/t/p/w92' + posterPath;
            img.alt = 'Poster';
            img.loading = 'lazy';
            img.style.width = '46px';
            img.style.height = 'auto';
            img.style.borderRadius = '6px';
            img.style.border = '1px solid var(--border-color)';
            img.style.display = 'inline-block';

            el.replaceWith(img);
        };

        const run = async () => {
            for (const chunk of chunks) {
                try {
                    const r = await fetch('/api/videos/tmdb-scan', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ video_ids: chunk })
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.ok) {
                        continue;
                    }

                    const results = Array.isArray(data.results) ? data.results : [];
                    for (const row of results) {
                        const vid = parseInt(row.id, 10);
                        if (!Number.isFinite(vid) || vid <= 0) continue;
                        applyPoster(vid, row.tmdb_poster_path || null);
                    }
                } catch (_) {
                    // ignore and continue
                }
            }
        };

        run();
    })();
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
