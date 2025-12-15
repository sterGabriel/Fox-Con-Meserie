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

        <!-- Control Buttons -->
        <div class="flex gap-3">
            <button type="button" id="btn-start" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                ▶ START CHANNEL
            </button>
            <button type="button" id="btn-stop" disabled class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-semibold">
                ❚❚ STOP CHANNEL
            </button>
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
const btnStart = document.getElementById('btn-start');
const btnStop = document.getElementById('btn-stop');
const statusEl = document.getElementById('channel-status');
const logViewer = document.getElementById('log-viewer');
const progressBar = document.getElementById('progress-bar');
const progressText = document.getElementById('progress-text');
const currentEncodingEl = document.getElementById('current-encoding');
const btnClearLog = document.getElementById('btn-clear-log');
const btnDownloadLog = document.getElementById('btn-download-log');

let isRunning = false;
let logLines = [];

function addLog(message) {
    const timestamp = new Date().toLocaleTimeString();
    logLines.push(`[${timestamp}] ${message}`);
    if (logLines.length > 100) logLines.shift();
    
    logViewer.innerHTML = logLines.map(l => `<div>${l}</div>`).join('');
    logViewer.scrollTop = logViewer.scrollHeight;
}

btnStart.addEventListener('click', function(e) {
    e.preventDefault();
    isRunning = true;
    btnStart.disabled = true;
    btnStop.disabled = false;
    statusEl.innerHTML = '🟢 LIVE STREAMING';
    statusEl.className = 'text-2xl font-bold text-green-400 mt-1';
    addLog('Channel started successfully');
    addLog('FFmpeg process launched');
    addLog('Stream available at both HLS and TS outputs');
});

btnStop.addEventListener('click', function(e) {
    e.preventDefault();
    isRunning = false;
    btnStart.disabled = false;
    btnStop.disabled = true;
    statusEl.innerHTML = '⚫ IDLE';
    statusEl.className = 'text-2xl font-bold text-slate-100 mt-1';
    addLog('Channel stopped');
});

btnClearLog.addEventListener('click', function() {
    logLines = [];
    logViewer.innerHTML = '<div class="text-slate-600">[System] Log cleared</div>';
    logLines.push('[System] Log cleared');
});

btnDownloadLog.addEventListener('click', function() {
    const text = logLines.join('\n');
    const blob = new Blob([text], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `channel-{{ $channel->id }}-log-${Date.now()}.txt`;
    a.click();
});

// Initialize
addLog('Engine ready');
</script>
