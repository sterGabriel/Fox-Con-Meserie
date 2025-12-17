<!-- ENGINE TAB (placed in settings view under Outputs) -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">🎬 Channel Engine Control</h2>

    <div class="space-y-6">
        <!-- Status Display -->
        <div class="p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Status</p>
                    <p id="channel-status" class="text-2xl font-bold text-slate-100 mt-1">⚫ IDLE</p>
                </div>
                <div class="text-4xl">📡</div>
            </div>
        </div>

        <!-- Encoding Jobs Section -->
        <div class="p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-200">📼 Encoding Jobs</h3>
                <button type="button" id="btn-encode-now" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-semibold">
                    ⚙️ ENCODE NOW
                </button>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">Progress</span>
                    <span id="progress-files" class="text-slate-200 font-semibold">0/0 files</span>
                </div>
                <div class="w-full bg-slate-900/50 rounded-full h-3">
                    <div id="progress-bar-encode" class="bg-orange-500 h-3 rounded-full" style="width: 0%"></div>
                </div>
            </div>
            <div id="jobs-list" class="mt-4 space-y-2 max-h-32 overflow-y-auto">
                <div class="text-xs text-slate-500 text-center py-2">No encoding jobs yet. Click "ENCODE NOW" to start.</div>
            </div>
        </div>

        <!-- Control Buttons -->
        <div class="flex gap-3 flex-wrap">
            <button type="button" id="btn-encode-now-alt" class="flex-1 min-w-[150px] px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-semibold">
                ⚙️ ENCODE NOW
            </button>
            <button type="button" id="btn-start" class="flex-1 min-w-[150px] px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                ▶ START CHANNEL
            </button>
            <button type="button" id="btn-start-looping" class="flex-1 min-w-[150px] px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold" title="Start with 24/7 looping">
                🔄 START 24/7 LOOP
            </button>
            <button type="button" id="btn-stop" disabled class="flex-1 min-w-[150px] px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-semibold">
                ❚❚ STOP CHANNEL
            </button>
            <button type="button" id="btn-test-preview" class="flex-1 min-w-[150px] px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold">
                🎥 TEST OVERLAY (10s)
            </button>
        </div>

        <!-- Preview Video Player -->
        <div id="preview-container" class="hidden p-4 bg-slate-800/20 border border-slate-600/20 rounded-lg">
            <p class="text-sm text-slate-400 mb-3">Preview (10 seconds with overlay)</p>
            <video id="preview-video" width="100%" controls class="rounded-lg bg-slate-900">
                <source src="" type="video/mp4">
                Your browser does not support video playback.
            </video>
            <p class="text-xs text-slate-500 mt-2">This 10-second preview shows how your overlay will look on the stream.</p>
        </div>

        <!-- Encoding Progress -->
        <div class="p-4 bg-slate-800/20 border border-slate-600/20 rounded-lg">
            <p class="text-sm text-slate-400 mb-2">Encoding Progress</p>
            <div class="flex items-center gap-3">
                <div class="flex-1 bg-slate-900/50 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                </div>
                <span id="progress-text" class="text-sm font-medium text-slate-300">0/0 files</span>
            </div>
            <p id="current-encoding" class="text-xs text-slate-500 mt-2">Idle</p>
        </div>

        <!-- Streaming Outputs -->
        <div class="p-4 bg-slate-800/20 border border-slate-600/20 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-200">📡 Streaming Outputs</h3>
                <span id="output-status" class="text-xs px-2 py-1 bg-slate-700/50 text-slate-400 rounded">⚫ Channel Offline</span>
            </div>

            <div class="space-y-3">
                <!-- TS Stream URL -->
                <div class="bg-slate-900/30 rounded p-3 border border-slate-700/30">
                    <p class="text-xs text-slate-400 mb-2">📊 TS Stream (Raw MPEG-TS)</p>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="ts-url" value="" readonly class="flex-1 bg-slate-800 border border-slate-600 rounded px-3 py-2 text-xs text-slate-300 font-mono" placeholder="ts://...">
                        <button type="button" onclick="copyToClipboard('ts-url')" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold">📋 Copy</button>
                        <button type="button" id="test-ts-btn" onclick="testVLC('ts')" class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-semibold" disabled>🎬 Test VLC</button>
                    </div>
                </div>

                <!-- HLS Playlist URL -->
                <div class="bg-slate-900/30 rounded p-3 border border-slate-700/30">
                    <p class="text-xs text-slate-400 mb-2">📹 HLS Playlist (HTTP Live Streaming)</p>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="hls-url" value="" readonly class="flex-1 bg-slate-800 border border-slate-600 rounded px-3 py-2 text-xs text-slate-300 font-mono" placeholder="http://...">
                        <button type="button" onclick="copyToClipboard('hls-url')" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold">📋 Copy</button>
                        <button type="button" id="test-hls-btn" onclick="testVLC('hls')" class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-semibold" disabled>🎬 Test VLC</button>
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-2">💡 URLs are active only when channel is running. Both are available in VLC: Media → Open Network Stream</p>
            </div>
        </div>

        <!-- Log Viewer -->
        <div class="p-4 bg-slate-950/50 border border-slate-600/20 rounded-lg">
            <div class="flex justify-between items-center mb-2">
                <p class="text-sm font-medium text-slate-300">📋 Live Log (Last 100 lines)</p>
                <button type="button" id="btn-clear-log" class="text-xs text-slate-500 hover:text-slate-300">Clear</button>
            </div>
            <div id="log-viewer" class="font-mono text-xs text-slate-400 h-48 overflow-y-auto p-3 bg-slate-900/80 rounded border border-slate-700/30">
                <div class="text-slate-600">[System] Ready to start channel</div>
            </div>
        </div>

        <!-- Download Log -->
        <button type="button" id="btn-download-log" class="w-full px-4 py-2 bg-slate-800/50 text-slate-300 rounded-lg hover:bg-slate-700/50 transition text-sm">
            📥 Download Full Log
        </button>
    </div>
</div>

<script>
// Global channel identifier
window.CHANNEL_ID = {{ $channel->id }};

// CSRF helper
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

const btnEncodeNow = document.getElementById('btn-encode-now');
const btnEncodeNowAlt = document.getElementById('btn-encode-now-alt');
const btnStart = document.getElementById('btn-start');
const btnStartLooping = document.getElementById('btn-start-looping');
const btnStop = document.getElementById('btn-stop');
const btnTestPreview = document.getElementById('btn-test-preview');
const statusEl = document.getElementById('channel-status');
const logViewer = document.getElementById('log-viewer');
const progressBar = document.getElementById('progress-bar');
const progressText = document.getElementById('progress-text');
const progressBarEncode = document.getElementById('progress-bar-encode');
const progressFiles = document.getElementById('progress-files');
const jobsList = document.getElementById('jobs-list');
const currentEncodingEl = document.getElementById('current-encoding');
const btnClearLog = document.getElementById('btn-clear-log');
const btnDownloadLog = document.getElementById('btn-download-log');
const previewContainer = document.getElementById('preview-container');
const previewVideo = document.getElementById('preview-video');

const channelId = window.CHANNEL_ID;
let statusCheckInterval = null;
let encodeCheckInterval = null;

function addLog(message) {
    const timestamp = new Date().toLocaleTimeString();
    const line = `[${timestamp}] ${message}`;
    const newDiv = document.createElement('div');
    newDiv.textContent = line;
    logViewer.appendChild(newDiv);
    
    // Keep only last 100 lines
    const lines = logViewer.querySelectorAll('div');
    if (lines.length > 100) {
        lines[0].remove();
    }
    
    logViewer.scrollTop = logViewer.scrollHeight;
}

function updateStatus() {
    fetch(`/vod-channels/${channelId}/engine/status`, { method: 'GET' })
        .then(r => r.json())
        .then(data => {
            const status = data.status;
            
            if (status.is_running) {
                statusEl.innerHTML = '🟢 LIVE STREAMING';
                statusEl.className = 'text-2xl font-bold text-green-400 mt-1';
                btnStart.disabled = true;
                btnStop.disabled = false;
            } else {
                statusEl.innerHTML = '⚫ IDLE';
                statusEl.className = 'text-2xl font-bold text-slate-100 mt-1';
                btnStart.disabled = false;
                btnStop.disabled = true;
            }
            
            // Update logs
            if (data.logs) {
                logViewer.innerHTML = '';
                data.logs.split('\n').forEach(line => {
                    if (line.trim()) {
                        const div = document.createElement('div');
                        div.textContent = line;
                        logViewer.appendChild(div);
                    }
                });
                logViewer.scrollTop = logViewer.scrollHeight;
            }
        })
        .catch(err => {
            console.error('Status check failed:', err);
        });
}

btnStart.addEventListener('click', function(e) {
    e.preventDefault();
    btnStart.disabled = true;
    btnStartLooping.disabled = true;
    
    fetch(`/vod-channels/${channelId}/engine/start`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({})
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                addLog('✅ Channel started successfully (PID: ' + data.pid + ')');
                addLog('FFmpeg process is now encoding');
                statusEl.innerHTML = '🟢 LIVE STREAMING';
                statusEl.className = 'text-2xl font-bold text-green-400 mt-1';
                btnStop.disabled = false;
                
                // Start status updates
                if (statusCheckInterval) clearInterval(statusCheckInterval);
                statusCheckInterval = setInterval(updateStatus, 2000);
            } else {
                addLog('❌ Failed to start: ' + data.message);
                btnStart.disabled = false;
                btnStartLooping.disabled = false;
            }
        })
        .catch(err => {
            addLog('❌ Error: ' + err.message);
            btnStart.disabled = false;
            btnStartLooping.disabled = false;
        });
});

btnStartLooping.addEventListener('click', function(e) {
    e.preventDefault();
    btnStartLooping.disabled = true;
    addLog('🔄 Starting 24/7 looping mode...');
    addLog('📝 Generating concat playlist from channel videos');
    
    fetch(`/vod-channels/${channelId}/engine/start-looping`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({})
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                addLog('✅ Channel started with 24/7 looping (PID: ' + data.pid + ')');
                addLog('🎬 All videos will loop seamlessly');
                addLog('📊 Mode: ' + data.mode);
                statusEl.innerHTML = '🔄 24/7 LOOPING';
                statusEl.className = 'text-2xl font-bold text-blue-400 mt-1';
                btnStop.disabled = false;
                
                // Start status updates
                if (statusCheckInterval) clearInterval(statusCheckInterval);
                statusCheckInterval = setInterval(updateStatus, 2000);
            } else {
                addLog('❌ Failed to start looping: ' + data.message);
                btnStartLooping.disabled = false;
            }
        })
        .catch(err => {
            addLog('❌ Error: ' + err.message);
            btnStartLooping.disabled = false;
        });
});

btnStop.addEventListener('click', function(e) {
    e.preventDefault();
    btnStop.disabled = true;
    
    fetch(`/vod-channels/${channelId}/engine/stop`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({})
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                addLog('✅ Channel stopped gracefully');
                statusEl.innerHTML = '⚫ IDLE';
                statusEl.className = 'text-2xl font-bold text-slate-100 mt-1';
                btnStart.disabled = false;
                btnStartLooping.disabled = false;
                
                // Stop status updates
                if (statusCheckInterval) clearInterval(statusCheckInterval);
            } else {
                addLog('❌ Failed to stop: ' + data.message);
                btnStop.disabled = false;
            }
        })
        .catch(err => {
            addLog('❌ Error: ' + err.message);
            btnStop.disabled = false;
        });
});

btnClearLog.addEventListener('click', function() {
    logViewer.innerHTML = '<div class="text-slate-600">[System] Log cleared</div>';
    addLog('Log cleared by user');
});

btnDownloadLog.addEventListener('click', function() {
    const text = Array.from(logViewer.querySelectorAll('div'))
        .map(el => el.textContent)
        .join('\n');
    const blob = new Blob([text], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `channel-${channelId}-log-${Date.now()}.txt`;
    a.click();
    window.URL.revokeObjectURL(url);
});

btnTestPreview.addEventListener('click', function(e) {
    e.preventDefault();
    btnTestPreview.disabled = true;
    addLog('🎥 Generating 10-second preview with overlay...');
    
    fetch(`/vod-channels/${channelId}/engine/test-preview`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({})
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                addLog('✅ Preview generated successfully');
                previewVideo.src = data.preview_url;
                previewContainer.classList.remove('hidden');
            } else {
                addLog('❌ Preview failed: ' + data.message);
            }
            btnTestPreview.disabled = false;
        })
        .catch(err => {
            addLog('❌ Error generating preview: ' + err.message);
            btnTestPreview.disabled = false;
        });
});

// Encode Now Handler
function startEncoding() {
    btnEncodeNow.disabled = true;
    btnEncodeNowAlt.disabled = true;
    addLog('⚙️ Starting offline encoding of playlist...');
    addLog('📝 Creating encoding jobs for each video');
    
    fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({})
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                addLog('✅ Encoding jobs created');
                addLog(`📊 Total jobs: ${data.total_jobs}`);
                jobsList.innerHTML = '';
                
                // Start polling for job updates
                if (encodeCheckInterval) clearInterval(encodeCheckInterval);
                encodeCheckInterval = setInterval(updateEncodingProgress, 2000);
                updateEncodingProgress();
            } else {
                addLog('❌ Failed to start encoding: ' + data.message);
                btnEncodeNow.disabled = false;
                btnEncodeNowAlt.disabled = false;
            }
        })
        .catch(err => {
            addLog('❌ Error: ' + err.message);
            btnEncodeNow.disabled = false;
            btnEncodeNowAlt.disabled = false;
        });
}

function updateEncodingProgress() {
    fetch(`/vod-channels/${channelId}/engine/encoding-jobs`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const total = data.total_jobs;
                const completed = data.completed_jobs;
                const running = data.running_jobs;
                
                progressFiles.textContent = `${completed}/${total} files encoded`;
                const percent = total > 0 ? (completed / total) * 100 : 0;
                progressBarEncode.style.width = percent + '%';
                
                // Display job status
                if (data.jobs && data.jobs.length > 0) {
                    jobsList.innerHTML = data.jobs.map(job => `
                        <div class="text-xs p-2 bg-slate-900/50 rounded border border-slate-700/30">
                            <div class="flex justify-between items-start">
                                <span class="text-slate-300">${job.video_title}</span>
                                <span class="text-${job.status === 'done' ? 'green' : job.status === 'running' ? 'orange' : 'slate'}-400 font-semibold text-xs">
                                    ${job.status === 'done' ? '✅' : job.status === 'running' ? '⏳' : '⏸️'} ${job.status}
                                </span>
                            </div>
                        </div>
                    `).join('');
                }
                
                // If all done, enable start button
                if (completed === total && total > 0) {
                    btnStart.disabled = false;
                    addLog(`✅ All ${total} files encoded! Ready to start channel.`);
                    if (encodeCheckInterval) clearInterval(encodeCheckInterval);
                }
            }
        })
        .catch(err => console.error('Encoding check error:', err));
}

btnEncodeNow.addEventListener('click', startEncoding);
btnEncodeNowAlt.addEventListener('click', startEncoding);

// Initialize
addLog('🔧 Engine ready - configure and start channel');
updateStatus();

// ═══════════════════════════════════════════════════════════════
// 📡 STREAMING OUTPUTS (TASK 7)
// ═══════════════════════════════════════════════════════════════

const tsUrlField = document.getElementById('ts-url');
const hlsUrlField = document.getElementById('hls-url');
const outputStatus = document.getElementById('output-status');
const testTsBtn = document.getElementById('test-ts-btn');
const testHlsBtn = document.getElementById('test-hls-btn');

// Function to copy URL to clipboard
function copyToClipboard(elementId) {
    const field = document.getElementById(elementId);
    const url = field.value;
    if (!url) {
        alert('URL not available - start the channel first');
        return;
    }
    navigator.clipboard.writeText(url).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.classList.add('bg-green-600');
        btn.classList.remove('bg-blue-600');
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-blue-600');
        }, 2000);
    }).catch(() => {
        alert('Failed to copy - please copy manually');
    });
}

// Function to test in VLC
function testVLC(type) {
    const urlField = type === 'ts' ? tsUrlField : hlsUrlField;
    const url = urlField.value;
    if (!url) {
        alert('URL not available');
        return;
    }
    
    // Open in VLC (vlc:// protocol)
    window.location.href = 'vlc://' + url;
    
    // Also open HTTP stream for fallback
    setTimeout(() => {
        if (url.includes('http')) {
            window.open(url);
        }
    }, 500);
}

// Function to update output URLs based on channel status
function updateOutputURLs() {
    fetch(`/vod-channels/${channelId}/engine/status`)
        .then(r => r.json())
        .then(data => {
            if (data.running) {
                outputStatus.innerHTML = '🟢 Channel Online';
                outputStatus.className = 'text-xs px-2 py-1 bg-green-600/30 text-green-300 rounded';
                
                // Generate URLs
                const protocol = window.location.protocol.replace(':', '');
                const host = window.location.host;
                
                // TS URL
                const tsUrl = `http://${host}/streams/${channelId}/live.ts`;
                tsUrlField.value = tsUrl;
                testTsBtn.disabled = false;
                
                // HLS URL
                const hlsUrl = `http://${host}/streams/${channelId}/index.m3u8`;
                hlsUrlField.value = hlsUrl;
                testHlsBtn.disabled = false;
            } else {
                outputStatus.innerHTML = '⚫ Channel Offline';
                outputStatus.className = 'text-xs px-2 py-1 bg-slate-700/50 text-slate-400 rounded';
                tsUrlField.value = '';
                hlsUrlField.value = '';
                testTsBtn.disabled = true;
                testHlsBtn.disabled = true;
            }
        })
        .catch(err => {
            console.error('Error updating output URLs:', err);
        });
}

// Update URLs every 2 seconds when status check runs
const originalUpdateStatus = updateStatus;
function updateStatusWithOutputs() {
    originalUpdateStatus();
    updateOutputURLs();
}

// Override updateStatus
updateStatus = updateStatusWithOutputs;

// Global startEncodingAll function (for onclick compatibility)
window.startEncodingAll = function(cId) {
    window.CHANNEL_ID = cId;
    startEncoding();
};

</script>
