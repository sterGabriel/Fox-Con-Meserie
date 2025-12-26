@extends('layouts.panel')

@section('title', 'Import Videos - ' . $category->name)

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;">
    <div>
        <h1 style="margin:0;font-size:24px;font-weight:800;">Scan & Import</h1>
        <div style="margin-top:4px;font-size:13px;color:var(--text-secondary);">Category: <span style="font-weight:800;color:var(--text-primary);">{{ $category->name }}</span></div>
    </div>
    <a href="{{ route('video-categories.index') }}" style="font-size:13px;font-weight:700;">← Back to Categories</a>
</div>

<div data-scan-grid style="display:grid;grid-template-columns:320px 1fr;gap:16px;align-items:start;">
    <div class="fox-table-container" style="padding:16px;">
        <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">📁 Scan Settings</div>

        <label for="sourcePath" style="display:block;font-size:12px;font-weight:700;color:var(--text-secondary);margin-bottom:6px;">Server Folder Path</label>
        <input
            type="text"
            id="sourcePath"
            placeholder="/mnt/media/MUZICA"
            value="{{ $category->source_path ?? '' }}"
            style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:4px;background:var(--card-bg);color:var(--text-primary);"
        />
        <div style="margin-top:6px;font-size:12px;color:var(--text-muted);">Full path to folder on server (e.g. /mnt/media/...)</div>

        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
            <button id="scanBtn" type="button" style="background:var(--btn-msg);color:var(--card-bg);padding:10px 14px;border-radius:4px;font-size:13px;font-weight:800;flex:1;">🔍 Scan Folder</button>
        </div>

        <div style="height:1px;background:var(--border-light);margin:14px 0;"></div>

        <div style="display:grid;grid-template-columns:1fr;gap:10px;">
            <div class="fox-table-container" style="padding:12px 14px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:700;">Videos found</div>
                <div style="font-size:22px;font-weight:800;color:var(--text-primary);" id="foundCount">0</div>
            </div>
            <div class="fox-table-container" style="padding:12px 14px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:700;">Selected for import</div>
                <div style="font-size:22px;font-weight:800;color:var(--text-primary);" id="selectedCount">0</div>
            </div>
            <div class="fox-table-container" style="padding:12px 14px;">
                <div style="font-size:12px;color:var(--text-muted);font-weight:700;">Total size</div>
                <div style="font-size:22px;font-weight:800;color:var(--text-primary);" id="totalSize">0 MB</div>
            </div>
        </div>

        <div style="height:1px;background:var(--border-light);margin:14px 0;"></div>

        <button id="importBtn" type="button" disabled style="background:var(--btn-start);color:var(--card-bg);padding:10px 14px;border-radius:4px;font-size:13px;font-weight:800;width:100%;">⬆️ Import Selected</button>

        <label style="display:flex;align-items:center;gap:10px;margin-top:12px;font-size:13px;color:var(--text-secondary);font-weight:700;">
            <input type="checkbox" id="selectAll" disabled>
            Select All
        </label>
    </div>

    <div>
        <div class="fox-table-container" style="padding:12px 16px;margin-bottom:16px;">
            <input
                type="text"
                id="searchInput"
                placeholder="Search videos by filename..."
                style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:4px;background:var(--card-bg);color:var(--text-primary);"
            />
        </div>

        <div id="loading" class="fox-loading" aria-hidden="true">
            <div class="fox-loading-box">
                <div class="fox-loading-spinner" aria-hidden="true"></div>
                <div style="margin-top:8px;font-size:13px;color:var(--text-secondary);font-weight:700;">Scanning folder...</div>
            </div>
        </div>

        <div class="fox-table-container" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="fox-table">
                    <thead>
                        <tr>
                            <th style="width:80px;">Select</th>
                            <th>Filename</th>
                            <th style="width:260px;">Info</th>
                            <th style="width:140px;text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="videoList">
                        <tr>
                            <td colspan="4" style="padding:18px 16px;color:var(--text-secondary);">👇 Enter folder path and click “Scan Folder” to start</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Info Modal -->
<div id="infoModal" class="fox-modal-backdrop" aria-hidden="true">
    <div class="fox-modal" role="dialog" aria-modal="true" aria-labelledby="infoModalTitle">
        <div class="fox-modal-header">
            <h3 class="fox-modal-title" id="infoModalTitle">📊 Video Information</h3>
            <button type="button" onclick="closeInfo()" class="fox-modal-close" aria-label="Close">×</button>
        </div>
        <div id="infoContent" class="fox-modal-body">
            <!-- Content loaded by JS -->
        </div>
        <div class="fox-modal-footer">
            <button type="button" onclick="closeInfo()" class="fox-modal-secondary">Close</button>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div id="toastContainer" class="fox-toast-container" aria-live="polite" aria-atomic="true"></div>

<style>
    .fox-loading {
        display: none;
        margin-bottom: 16px;
    }

    .fox-loading.is-open {
        display: block;
    }

    .fox-loading-box {
        padding: 16px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: var(--card-bg);
        box-shadow: var(--shadow-sm);
        display: inline-block;
    }

    .fox-loading-spinner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(37, 99, 235, 0.35);
        border-top-color: var(--fox-blue);
        border-radius: 999px;
        animation: foxSpin 0.9s linear infinite;
        margin: 0 auto;
    }

    @keyframes foxSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .fox-toast-container {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 420px;
    }

    .toast {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
        box-shadow: var(--shadow-md);
    }

    .toast.success {
        border-color: rgba(22, 163, 74, 0.25);
        background: rgba(22, 163, 74, 0.08);
        color: rgba(22, 163, 74, 1);
    }

    .toast.error {
        border-color: rgba(220, 38, 38, 0.25);
        background: rgba(220, 38, 38, 0.08);
        color: rgba(220, 38, 38, 1);
    }

    .toast.info {
        border-color: rgba(37, 99, 235, 0.25);
        background: rgba(37, 99, 235, 0.08);
        color: rgba(37, 99, 235, 1);
    }

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
        max-height: 80vh;
        display: flex;
        flex-direction: column;
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
        overflow: auto;
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
</style>

<script>
    const categoryId = {{ $category->id }};
    const csrfToken = '{{ csrf_token() }}';
    let videos = [];

    function closeInfo() {
        document.getElementById('infoModal').classList.remove('is-open');
    }

    // Scan folder
    document.getElementById('scanBtn').addEventListener('click', async () => {
        const sourcePath = document.getElementById('sourcePath').value.trim();
        if (!sourcePath) {
            toast('Please enter a folder path', 'error');
            return;
        }

        const btn = document.getElementById('scanBtn');
        btn.disabled = true;
        document.getElementById('loading').classList.add('is-open');

        try {
            const response = await fetch(`/video-categories/${categoryId}/scan`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ source_path: sourcePath }),
            });

            const data = await response.json();

            if (data.success) {
                videos = data.videos;
                renderVideos(videos);
                document.getElementById('foundCount').textContent = videos.length;
                toast(data.message, 'success');
                document.getElementById('selectAll').disabled = false;
                document.getElementById('importBtn').disabled = videos.filter(v => !v.imported).length === 0;
                document.getElementById('selectAll').checked = false;
                updateImportBtn();
            } else {
                toast(data.message, 'error');
            }
        } catch (error) {
            toast('Error scanning folder: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            document.getElementById('loading').classList.remove('is-open');
        }
    });

    // Render video list
    function renderVideos(videosToRender) {
        const list = document.getElementById('videoList');
        list.innerHTML = '';

        if (videosToRender.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="4" style="padding:18px 16px;color:var(--text-secondary);">No videos found</td>`;
            list.appendChild(tr);
            return;
        }

        for (const video of videosToRender) {
            const tr = document.createElement('tr');
            if (video.imported) tr.style.opacity = '0.6';

            const checkboxTd = document.createElement('td');
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'video-checkbox';
            cb.dataset.path = video.file_path;
            cb.disabled = !!video.imported;
            checkboxTd.appendChild(cb);

            const nameTd = document.createElement('td');
            const nameDiv = document.createElement('div');
            nameDiv.style.fontWeight = '700';
            nameDiv.textContent = video.filename;
            nameTd.appendChild(nameDiv);
            if (video.imported) {
                const badge = document.createElement('span');
                badge.className = 'fox-badge green';
                badge.style.marginTop = '6px';
                badge.textContent = 'IMPORTED';
                nameTd.appendChild(badge);
            }

            const infoTd = document.createElement('td');
            infoTd.style.fontSize = '12px';
            infoTd.style.color = 'var(--text-secondary)';
            const modified = video.modified_formatted ? ('<div style="margin-top:4px;">' + escapeHtml(video.modified_formatted) + '</div>') : '';
            infoTd.innerHTML = `${escapeHtml('📏 ' + video.size_formatted + ' · ⏱️ ' + video.duration)}${modified}`;

            const actionsTd = document.createElement('td');
            actionsTd.style.textAlign = 'center';
            actionsTd.style.whiteSpace = 'nowrap';

            const infoBtn = document.createElement('button');
            infoBtn.type = 'button';
            infoBtn.className = 'fox-action-btn edit';
            infoBtn.dataset.action = 'info';
            infoBtn.dataset.path = video.file_path;
            infoBtn.title = 'Info';
            infoBtn.textContent = 'ℹ️';
            actionsTd.appendChild(infoBtn);

            if (!video.imported) {
                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'fox-action-btn delete';
                delBtn.dataset.action = 'delete';
                delBtn.dataset.path = video.file_path;
                delBtn.title = 'Delete';
                delBtn.textContent = '🗑️';
                actionsTd.appendChild(delBtn);
            }

            tr.appendChild(checkboxTd);
            tr.appendChild(nameTd);
            tr.appendChild(infoTd);
            tr.appendChild(actionsTd);
            list.appendChild(tr);
        }
    }

    // Update import button state
    function updateImportBtn() {
        const selectedPaths = Array.from(document.querySelectorAll('.video-checkbox:checked:not(:disabled)'))
            .map(cb => cb.dataset.path)
            .filter(Boolean);

        document.getElementById('selectedCount').textContent = selectedPaths.length;

        const size = selectedPaths.reduce((sum, path) => {
            const v = videos.find(x => x.file_path === path);
            return sum + (v?.size || 0);
        }, 0);

        document.getElementById('totalSize').textContent = (size / 1024 / 1024).toFixed(2) + ' MB';
        document.getElementById('importBtn').disabled = selectedPaths.length === 0;
    }

    // Select all
    document.getElementById('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.video-checkbox:not(:disabled)').forEach(cb => {
            cb.checked = e.target.checked;
        });
        updateImportBtn();
    });

    // Search
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = videos.filter(v => v.filename.toLowerCase().includes(query));
        renderVideos(filtered);
        document.getElementById('selectAll').checked = false;
        updateImportBtn();
    });

    // Import selected
    document.getElementById('importBtn').addEventListener('click', async () => {
        const selected = Array.from(document.querySelectorAll('.video-checkbox:checked:not(:disabled)'))
            .map(cb => cb.dataset.path)
            .filter(Boolean);

        if (selected.length === 0) return;

        const btn = document.getElementById('importBtn');
        btn.disabled = true;

        try {
            const response = await fetch(`/video-categories/${categoryId}/scan/import`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ files: selected }),
            });

            const data = await response.json();

            if (data.success) {
                toast(`✅ ${data.imported} imported, ${data.skipped} skipped, ${data.errors} errors`, 'success');
                // Rescan
                document.getElementById('scanBtn').click();
            } else {
                toast(data.message, 'error');
            }
        } catch (error) {
            toast('Error importing: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('videoList').addEventListener('change', (e) => {
        if (e.target && e.target.classList && e.target.classList.contains('video-checkbox')) {
            updateImportBtn();
        }
    });

    document.getElementById('videoList').addEventListener('click', (e) => {
        const btn = e.target?.closest?.('button[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const path = btn.dataset.path;
        if (!path) return;

        const idx = videos.findIndex(v => v.file_path === path);
        if (idx < 0) return;

        if (action === 'info') showInfo(idx);
        if (action === 'delete') deleteFile(idx);
    });

    // Show info modal
    function showInfo(idx) {
        const video = videos[idx];
        const modal = document.getElementById('infoModal');
        const content = document.getElementById('infoContent');

        let videoInfo = '';
        let audioInfo = '';

        if (video.metadata.video) {
            const v = video.metadata.video;
            videoInfo = `
                <div>
                    <div style="font-weight:800;color:var(--text-primary);margin-bottom:6px;">🎬 Video</div>
                    <ul style="margin:0;padding-left:18px;font-size:13px;color:var(--text-secondary);display:grid;gap:4px;">
                        <li>Codec: <span style="font-weight:800;color:var(--text-primary);">${escapeHtml(v.codec || '')}</span></li>
                        <li>Resolution: <span style="font-weight:800;color:var(--text-primary);">${v.width}x${v.height}</span></li>
                        <li>FPS: <span style="font-weight:800;color:var(--text-primary);">${v.fps}</span></li>
                        <li>Bitrate: <span style="font-weight:800;color:var(--text-primary);">${(v.bitrate / 1000).toFixed(0)} kbps</span></li>
                    </ul>
                </div>
            `;
        }

        if (video.metadata.audio) {
            const a = video.metadata.audio;
            audioInfo = `
                <div>
                    <div style="font-weight:800;color:var(--text-primary);margin-bottom:6px;">🔊 Audio</div>
                    <ul style="margin:0;padding-left:18px;font-size:13px;color:var(--text-secondary);display:grid;gap:4px;">
                        <li>Codec: <span style="font-weight:800;color:var(--text-primary);">${escapeHtml(a.codec || '')}</span></li>
                        <li>Channels: <span style="font-weight:800;color:var(--text-primary);">${a.channels}</span></li>
                        <li>Sample Rate: <span style="font-weight:800;color:var(--text-primary);">${(a.sample_rate / 1000).toFixed(0)} kHz</span></li>
                        <li>Bitrate: <span style="font-weight:800;color:var(--text-primary);">${(a.bitrate / 1000).toFixed(0)} kbps</span></li>
                    </ul>
                </div>
            `;
        }

        content.innerHTML = `
            <div style="font-size:13px;display:flex;flex-direction:column;gap:10px;">
                <div>
                    <div style="font-weight:800;color:var(--text-primary);">📄 File</div>
                    <div style="color:var(--text-secondary);word-break:break-word;">${escapeHtml(video.filename)}</div>
                </div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:700;">📏 Size: ${escapeHtml(video.size_formatted)} · ⏱️ Duration: ${escapeHtml(video.duration)}</div>
                <div style="display:grid;grid-template-columns:1fr;gap:10px;">
                    ${videoInfo}
                    ${audioInfo}
                </div>
            </div>
        `;

        modal.classList.add('is-open');
    }

    // Delete file
    async function deleteFile(idx) {
        const video = videos[idx];
        if (!confirm(`Delete file: ${video.filename}?`)) return;

        try {
            const response = await fetch(`/video-categories/${categoryId}/scan/delete-file`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ file_path: video.file_path }),
            });

            const data = await response.json();
            if (data.success) {
                toast('File deleted: ' + video.filename, 'success');
                videos.splice(idx, 1);
                renderVideos(videos);
                document.getElementById('foundCount').textContent = videos.length;
                document.getElementById('selectAll').checked = false;
                updateImportBtn();
            } else {
                toast(data.message, 'error');
            }
        } catch (error) {
            toast('Error deleting file: ' + error.message, 'error');
        }
    }

    // Toast notification
    function toast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.textContent = message;
        container.appendChild(el);

        setTimeout(() => el.remove(), 4000);
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@endsection
