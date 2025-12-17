@extends('layouts.panel')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-100">🎬 Import & Encode VOD</h1>
    <p class="text-slate-400">{{ $channel->name }}</p>
</div>

<div class="grid grid-cols-3 gap-6 pb-32">
    <!-- LEFT: ENCODING QUEUE & SETTINGS -->
    <div class="col-span-1 space-y-4">
        
        <!-- LOGO SETUP -->
        <div class="rounded-lg border border-slate-500/20 bg-slate-900/40 p-4">
            <h3 class="font-bold text-slate-100 mb-3">📍 Logo</h3>
            
            <div class="space-y-2">
                <input type="file" id="logoFile" accept="image/png,image/svg+xml" class="w-full text-xs" onchange="previewLogo(event)">
                
                <div id="logoPreview" class="hidden h-16 bg-slate-950/50 rounded border border-slate-600/40 flex items-center justify-center">
                    <img id="logoPic" style="max-height: 100%; max-width: 100%; object-contain;">
                </div>

                <select id="logoPos" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded text-slate-200">
                    <option value="TL">Top Left</option>
                    <option value="TR">Top Right</option>
                    <option value="BL">Bottom Left</option>
                    <option value="BR">Bottom Right</option>
                </select>
            </div>
        </div>

        <!-- ENCODING QUEUE -->
        <div class="rounded-lg border border-slate-500/20 bg-slate-900/40 p-4">
            <h3 class="font-bold text-slate-100 mb-3">📺 Queue (<span id="queueCount">0</span>)</h3>
            
            <div id="encodeQueue" class="space-y-2 max-h-48 overflow-y-auto">
                <p class="text-xs text-slate-500 text-center py-4">Select videos from right →</p>
            </div>
        </div>

        <!-- ENCODING SETTINGS -->
        <div class="rounded-lg border border-slate-500/20 bg-slate-900/40 p-4">
            <h3 class="font-bold text-slate-100 mb-3">⚙️ Settings</h3>
            
            <div class="space-y-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Resolution</label>
                    <input type="text" id="resolution" value="1280x720" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded text-slate-200">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Video Bitrate (kbps)</label>
                    <input type="number" id="videoBitrate" value="1500" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded text-slate-200">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Audio Bitrate (kbps)</label>
                    <input type="number" id="audioBitrate" value="128" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded text-slate-200">
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="space-y-2">
            <button onclick="testEncoding({{ $channel->id }})" class="w-full px-4 py-2 bg-yellow-600/20 text-yellow-300 rounded hover:bg-yellow-600/30 transition text-sm font-medium">
                🧪 Test (10-60s)
            </button>

            <button onclick="encodeAll({{ $channel->id }})" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition">
                🚀 Encode All
            </button>
        </div>
    </div>

    <!-- RIGHT: VIDEO IMPORT -->
    <div class="col-span-2">
        <div class="rounded-lg border border-slate-500/20 bg-slate-900/40 p-4">
            <h3 class="font-bold text-slate-100 mb-4">📂 Import VOD</h3>

            <!-- CATEGORY SELECTOR -->
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-300 mb-2">Category</label>
                <select id="categorySelect" onchange="loadVideos()" class="w-full px-3 py-2 text-sm bg-slate-950/50 border border-slate-600/40 rounded text-slate-200">
                    <option value="">-- Select Category --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- VIDEOS TABLE -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-500/20">
                            <th class="text-left py-2 px-2"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th class="text-left py-2 px-2 text-slate-400">Title</th>
                            <th class="text-left py-2 px-2 text-slate-400">Duration</th>
                            <th class="text-left py-2 px-2 text-slate-400">Size</th>
                        </tr>
                    </thead>
                    <tbody id="videosTable">
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500 text-xs">Select category first</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ADD BUTTON -->
            <button onclick="addToQueue({{ $channel->id }})" class="mt-4 w-full px-4 py-2 bg-blue-600/20 text-blue-300 rounded hover:bg-blue-600/30 transition text-sm font-medium">
                ➕ Add Selected to Queue
            </button>
        </div>
    </div>
</div>

<script>
let selectedVideos = [];
let encodeQueue = [];

function previewLogo(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById('logoPic').src = event.target.result;
            document.getElementById('logoPreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

async function loadVideos() {
    const catId = document.getElementById('categorySelect').value;
    if (!catId) {
        document.getElementById('videosTable').innerHTML = '<tr><td colspan="4" class="py-6 text-center text-slate-500">Select category</td></tr>';
        return;
    }

    try {
        const response = await fetch(`/vod-channels/{{ $channel->id }}/videos?category=${catId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        const videos = await response.json();
        let html = '';

        videos.forEach(v => {
            html += `
                <tr class="border-b border-slate-500/10 hover:bg-slate-800/20">
                    <td class="py-2 px-2"><input type="checkbox" value="${v.id}" class="videoCheck" onchange="updateSelected()"></td>
                    <td class="py-2 px-2 text-slate-100">${v.title}</td>
                    <td class="py-2 px-2 text-slate-400">${v.duration || '--:--'}</td>
                    <td class="py-2 px-2 text-slate-400">${(v.file_size / 1024 / 1024).toFixed(0)} MB</td>
                </tr>
            `;
        });

        document.getElementById('videosTable').innerHTML = html;
    } catch (error) {
        console.error('Error loading videos:', error);
    }
}

function updateSelected() {
    selectedVideos = Array.from(document.querySelectorAll('.videoCheck:checked')).map(el => el.value);
}

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.videoCheck').forEach(el => el.checked = checkbox.checked);
    updateSelected();
}

function addToQueue(channelId) {
    const checks = document.querySelectorAll('.videoCheck:checked');
    
    if (checks.length === 0) {
        alert('Select videos first!');
        return;
    }

    if (encodeQueue.length + checks.length > 5) {
        alert('Maximum 5 videos per session!');
        return;
    }

    checks.forEach(check => {
        const row = check.closest('tr');
        const videoId = check.value;
        const title = row.cells[1].textContent;
        
        if (!encodeQueue.find(v => v.id == videoId)) {
            encodeQueue.push({ id: videoId, title });
        }
    });

    updateQueueDisplay();
}

function updateQueueDisplay() {
    const queueDiv = document.getElementById('encodeQueue');
    document.getElementById('queueCount').textContent = encodeQueue.length;

    if (encodeQueue.length === 0) {
        queueDiv.innerHTML = '<p class="text-xs text-slate-500 text-center py-4">Select videos</p>';
        return;
    }

    let html = '';
    encodeQueue.forEach((v, i) => {
        html += `
            <div class="flex justify-between items-center p-2 bg-slate-950/50 rounded border border-slate-600/40">
                <span class="text-xs text-slate-300">${v.title.substring(0, 20)}</span>
                <button type="button" onclick="removeFromQueue(${i})" class="text-red-400 text-xs">✕</button>
            </div>
        `;
    });
    queueDiv.innerHTML = html;
}

function removeFromQueue(index) {
    encodeQueue.splice(index, 1);
    updateQueueDisplay();
}

async function testEncoding(channelId) {
    if (encodeQueue.length === 0) {
        alert('Add videos to queue first!');
        return;
    }

    const videoId = encodeQueue[0].id;
    alert('Testing first video for 10-60 seconds...');

    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/test-preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ video_id: videoId })
        });

        if (response.ok) {
            alert('✅ Test complete! Check preview.');
        } else {
            alert('❌ Test failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function encodeAll(channelId) {
    if (encodeQueue.length === 0) {
        alert('Add videos to queue first!');
        return;
    }

    if (!confirm(`Encode ${encodeQueue.length} videos?`)) return;

    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                video_ids: encodeQueue.map(v => v.id),
                resolution: document.getElementById('resolution').value,
                video_bitrate: document.getElementById('videoBitrate').value,
                audio_bitrate: document.getElementById('audioBitrate').value
            })
        });

        if (response.ok) {
            alert('✅ Encoding started!');
            encodeQueue = [];
            updateQueueDisplay();
            setTimeout(() => location.reload(), 2000);
        } else {
            alert('❌ Error starting encoding');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>
@endsection
