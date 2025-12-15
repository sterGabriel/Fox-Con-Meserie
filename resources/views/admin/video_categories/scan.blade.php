@extends('layouts.app')

@section('title', 'Import Videos - ' . $category->name)

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-950 to-slate-900 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('video-categories.index') }}" class="text-blue-400 hover:text-blue-300 text-sm mb-4 inline-block">
                ← Back to Categories
            </a>
            <h1 class="text-4xl font-bold text-white mb-2">
                Import Videos to <span class="text-blue-400">{{ $category->name }}</span>
            </h1>
            <p class="text-slate-400">Scan server folder for videos and import them to your library</p>
        </div>

        <!-- Main Container -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">📁 Scan Settings</h3>
                    
                    <!-- Path Input -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Server Folder Path
                        </label>
                        <input 
                            type="text" 
                            id="sourcePath"
                            placeholder="/mnt/media/MUZICA"
                            class="w-full px-4 py-2 bg-slate-700/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                            value="{{ $category->source_path ?? '' }}"
                        />
                        <p class="text-xs text-slate-500 mt-2">Full path to folder on server (e.g. /mnt/media/...)</p>
                    </div>

                    <!-- Scan Button -->
                    <button 
                        id="scanBtn"
                        class="w-full px-4 py-2 bg-blue-600/80 text-white rounded-lg hover:bg-blue-600 transition font-medium text-sm flex items-center justify-center gap-2"
                    >
                        <span>🔍 Scan Folder</span>
                    </button>

                    <hr class="border-slate-700/50 my-6">

                    <!-- Stats -->
                    <div class="space-y-3">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white" id="foundCount">0</p>
                            <p class="text-xs text-slate-400">Videos found</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-400" id="selectedCount">0</p>
                            <p class="text-xs text-slate-400">Selected for import</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-slate-400" id="totalSize">0 MB</p>
                            <p class="text-xs text-slate-400">Total size</p>
                        </div>
                    </div>

                    <hr class="border-slate-700/50 my-6">

                    <!-- Import Button -->
                    <button 
                        id="importBtn"
                        disabled
                        class="w-full px-4 py-2 bg-green-600/80 text-white rounded-lg hover:bg-green-600 transition font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <span>⬆️ Import Selected</span>
                    </button>

                    <!-- Select All Toggle -->
                    <label class="flex items-center gap-2 mt-4 text-sm text-slate-300 cursor-pointer">
                        <input 
                            type="checkbox" 
                            id="selectAll"
                            disabled
                            class="w-4 h-4 rounded border-slate-500 text-blue-600 cursor-pointer"
                        />
                        Select All
                    </label>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Search Bar -->
                <div class="mb-6">
                    <input 
                        type="text" 
                        id="searchInput"
                        placeholder="Search videos by filename..."
                        class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Video List -->
                <div class="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden">
                    <div id="videoList" class="divide-y divide-slate-700/50">
                        <!-- Placeholder -->
                        <div class="p-8 text-center text-slate-400">
                            <p>👇 Enter folder path and click "Scan Folder" to start</p>
                        </div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div id="loading" class="hidden p-8 text-center">
                    <div class="inline-block">
                        <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <p class="text-slate-400 mt-2">Scanning folder...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Modal -->
<div id="infoModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto">
        <div class="sticky top-0 bg-slate-800 border-b border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">📊 Video Information</h3>
            <button onclick="document.getElementById('infoModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-2xl">×</button>
        </div>
        <div id="infoContent" class="p-6 space-y-4">
            <!-- Content loaded by JS -->
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div id="toastContainer" class="fixed bottom-6 right-6 space-y-3 z-50"></div>

<style>
    .toast {
        @apply bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-sm text-white shadow-lg animate-pulse;
    }
    .toast.success {
        @apply bg-green-900/30 border-green-700 text-green-300;
    }
    .toast.error {
        @apply bg-red-900/30 border-red-700 text-red-300;
    }
    .toast.info {
        @apply bg-blue-900/30 border-blue-700 text-blue-300;
    }
</style>

<script>
    const categoryId = {{ $category->id }};
    const csrfToken = '{{ csrf_token() }}';
    let videos = [];

    // Scan folder
    document.getElementById('scanBtn').addEventListener('click', async () => {
        const sourcePath = document.getElementById('sourcePath').value.trim();
        if (!sourcePath) {
            toast('Please enter a folder path', 'error');
            return;
        }

        const btn = document.getElementById('scanBtn');
        btn.disabled = true;
        document.getElementById('loading').classList.remove('hidden');

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
            } else {
                toast(data.message, 'error');
            }
        } catch (error) {
            toast('Error scanning folder: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            document.getElementById('loading').classList.add('hidden');
        }
    });

    // Render video list
    function renderVideos(videosToRender) {
        const list = document.getElementById('videoList');
        
        if (videosToRender.length === 0) {
            list.innerHTML = '<div class="p-8 text-center text-slate-400">No videos found</div>';
            return;
        }

        list.innerHTML = videosToRender.map((video, idx) => `
            <div class="flex items-center gap-4 p-4 hover:bg-slate-700/20 transition ${video.imported ? 'opacity-50' : ''}">
                <input 
                    type="checkbox" 
                    class="video-checkbox w-5 h-5 rounded border-slate-600 text-blue-600 cursor-pointer"
                    data-index="${idx}"
                    ${video.imported ? 'disabled' : ''}
                />
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-white truncate">${escapeHtml(video.filename)}</div>
                    <div class="text-xs text-slate-400 space-y-1">
                        <div>📏 ${video.size_formatted} • ⏱️ ${video.duration}</div>
                        <div>${video.modified_formatted}</div>
                        ${video.imported ? '<div class="text-green-400">✓ Already imported</div>' : ''}
                    </div>
                </div>
                <div class="flex gap-2">
                    <button 
                        class="px-3 py-1 text-xs bg-blue-600/20 text-blue-300 rounded hover:bg-blue-600/30 transition"
                        onclick="showInfo(${idx})"
                    >
                        ℹ️ Info
                    </button>
                    ${!video.imported ? `
                        <button 
                            class="px-3 py-1 text-xs bg-red-600/20 text-red-300 rounded hover:bg-red-600/30 transition"
                            onclick="deleteFile(${idx})"
                        >
                            🗑️ Delete
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');

        // Add checkboxes event listeners
        document.querySelectorAll('.video-checkbox').forEach(cb => {
            cb.addEventListener('change', updateImportBtn);
        });
    }

    // Update import button state
    function updateImportBtn() {
        const selected = document.querySelectorAll('.video-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = selected;
        
        const size = videos.reduce((sum, v, i) => {
            if (document.querySelector(`.video-checkbox[data-index="${i}"]:checked`)) {
                return sum + v.size;
            }
            return sum;
        }, 0);
        document.getElementById('totalSize').textContent = (size / 1024 / 1024).toFixed(2) + ' MB';
        
        document.getElementById('importBtn').disabled = selected === 0;
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
    });

    // Import selected
    document.getElementById('importBtn').addEventListener('click', async () => {
        const selected = Array.from(document.querySelectorAll('.video-checkbox:checked'))
            .map(cb => {
                const idx = cb.dataset.index;
                return videos[idx].file_path;
            });

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
                    <p class="font-semibold text-slate-300">🎬 Video</p>
                    <ul class="text-sm text-slate-400 space-y-1 pl-4">
                        <li>Codec: <span class="text-white">${v.codec}</span></li>
                        <li>Resolution: <span class="text-white">${v.width}x${v.height}</span></li>
                        <li>FPS: <span class="text-white">${v.fps}</span></li>
                        <li>Bitrate: <span class="text-white">${(v.bitrate / 1000).toFixed(0)} kbps</span></li>
                    </ul>
                </div>
            `;
        }

        if (video.metadata.audio) {
            const a = video.metadata.audio;
            audioInfo = `
                <div>
                    <p class="font-semibold text-slate-300">🔊 Audio</p>
                    <ul class="text-sm text-slate-400 space-y-1 pl-4">
                        <li>Codec: <span class="text-white">${a.codec}</span></li>
                        <li>Channels: <span class="text-white">${a.channels}</span></li>
                        <li>Sample Rate: <span class="text-white">${(a.sample_rate / 1000).toFixed(0)} kHz</span></li>
                        <li>Bitrate: <span class="text-white">${(a.bitrate / 1000).toFixed(0)} kbps</span></li>
                    </ul>
                </div>
            `;
        }

        content.innerHTML = `
            <div class="text-sm space-y-3">
                <div>
                    <p class="font-semibold text-white">📄 File</p>
                    <p class="text-slate-400 truncate">${escapeHtml(video.filename)}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">📏 Size: ${video.size_formatted} | ⏱️ Duration: ${video.duration}</p>
                </div>
                ${videoInfo}
                ${audioInfo}
            </div>
        `;

        modal.classList.remove('hidden');
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
