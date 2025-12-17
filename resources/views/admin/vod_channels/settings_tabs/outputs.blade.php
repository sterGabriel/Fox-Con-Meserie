<!-- OUTPUTS TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">📤 Stream Output URLs</h2>

    <!-- Status Loading -->
    <div id="outputs-loading" class="p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg mb-6">
        <p class="text-sm text-slate-300">Loading stream information...</p>
    </div>

    <!-- Outputs Container -->
    <div id="outputs-container"></div>

    <!-- Status Badge -->
    <div id="outputs-status" class="p-4 rounded-lg mb-6" style="display: none;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium" id="status-text"></p>
                <p class="text-xs mt-1" id="status-info"></p>
            </div>
            <div class="text-3xl">🎬</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-3">
        <button type="button" class="copy-all-btn px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
            📋 Copy All URLs
        </button>
        <a href="https://www.vlc.org/" target="_blank" class="px-4 py-2 bg-orange-600/20 text-orange-300 text-sm rounded-lg hover:bg-orange-600/30 transition">
            🎬 Test with VLC
        </a>
    </div>

    <!-- Info Box -->
    <div class="mt-6 p-4 bg-blue-500/10 border border-blue-400/30 rounded-lg">
        <p class="text-sm text-blue-300">
            💡 <strong>Both URLs work in VLC and Xtream Codes.</strong> Use these to add the channel to your IPTV player.<br>
            <span class="text-xs text-blue-400 block mt-2">• HLS: Better for adaptive bitrate (segments)<br>
            • TS: Single stream, lower latency (for Xtream Codes)</span>
        </p>
    </div>
</div>

<script>

// Load output streams on tab load
function loadOutputStreams() {
    const channelId = '{{ $channel->id }}';
    fetch(`/vod-channels/${channelId}/engine/outputs`, {
        method: 'GET',
        credentials: 'same-origin'
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                displayStreams(data);
            } else {
                document.getElementById('outputs-loading').innerHTML = 
                    '<p class="text-sm text-red-300">Error: ' + data.message + '</p>';
            }
        })
        .catch(err => {
            document.getElementById('outputs-loading').innerHTML = 
                '<p class="text-sm text-red-300">Error loading streams: ' + err.message + '</p>';
        });
}

function displayStreams(data) {
    const container = document.getElementById('outputs-container');
    container.innerHTML = '';

    // Display each stream
    data.streams.forEach((stream, idx) => {
        const statusColor = stream.file_exists ? 'green' : 'amber';
        const statusText = stream.file_exists ? '🟢 Ready' : '🟡 Waiting for channel';
        const statusBg = stream.file_exists ? 'bg-slate-800/30' : 'bg-slate-800/20';

        const streamHTML = `
            <div class="mb-6 p-4 ${statusBg} border border-slate-600/30 rounded-lg">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <p class="text-xs text-slate-400 uppercase tracking-wide">${stream.type}</p>
                        <code class="text-sm text-green-300 font-mono break-all mt-2 block">${stream.url}</code>
                        <p class="text-xs text-slate-400 mt-2">
                            <strong>Use:</strong> ${stream.use_case}<br>
                            <strong>Format:</strong> ${stream.format}<br>
                            <strong>curl:</strong> <code class="text-xs bg-slate-900/50 px-2 py-1 rounded">${stream.curl_command}</code>
                        </p>
                    </div>
                    <button type="button" class="copy-btn px-3 py-2 bg-blue-500/20 text-blue-300 text-sm rounded hover:bg-blue-500/30 transition flex-shrink-0" data-url="${stream.url}">
                        📋 Copy
                    </button>
                </div>
                <p class="text-xs text-${statusColor}-400 mt-2">${statusText}</p>
            </div>
        `;

        container.innerHTML += streamHTML;
    });

    // Update status badge
    const statusDiv = document.getElementById('outputs-status');
    const statusText = document.getElementById('status-text');
    const statusInfo = document.getElementById('status-info');

    if (data.is_running) {
        statusDiv.className = 'p-4 bg-green-500/10 border border-green-400/30 rounded-lg mb-6';
        statusText.textContent = '✅ BOTH OUTPUTS ACTIVE';
        statusText.className = 'text-sm font-medium text-green-300';
        statusInfo.textContent = 'Bandwidth: ~3000 kbps (HLS + TS)';
        statusInfo.className = 'text-xs text-green-400';
    } else {
        statusDiv.className = 'p-4 bg-amber-500/10 border border-amber-400/30 rounded-lg mb-6';
        statusText.textContent = '⏸️ CHANNEL OFFLINE';
        statusText.className = 'text-sm font-medium text-amber-300';
        statusInfo.textContent = 'Start the channel to stream';
        statusInfo.className = 'text-xs text-amber-400';
    }
    statusDiv.style.display = 'block';

    // Re-attach copy handlers
    attachCopyHandlers();
}

function attachCopyHandlers() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(() => {
                const oldText = this.textContent;
                this.textContent = '✅ Copied!';
                setTimeout(() => {
                    this.textContent = oldText;
                }, 2000);
            });
        });
    });

    document.querySelector('.copy-all-btn').addEventListener('click', function() {
        let urls = [];
        document.querySelectorAll('.copy-btn').forEach(btn => {
            const url = btn.getAttribute('data-url');
            const type = btn.closest('.rounded-lg').querySelector('.uppercase').textContent.trim();
            urls.push(type + ': ' + url);
        });
        const text = urls.join('\n');
        navigator.clipboard.writeText(text).then(() => {
            const oldText = this.textContent;
            this.textContent = '✅ Copied!';
            setTimeout(() => {
                this.textContent = oldText;
            }, 2000);
        });
    });
}

// Load on page load
loadOutputStreams();

// Refresh streams when this tab becomes active
document.addEventListener('click', function(e) {
    if (e.target.textContent.includes('Outputs') || e.target.closest('[data-tab="outputs"]')) {
        loadOutputStreams();
    }
});

// Also reload every 30 seconds to check status
setInterval(loadOutputStreams, 30000);
</script>

