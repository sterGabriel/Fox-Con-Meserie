<!-- OUTPUTS TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">📤 Stream Export URLs</h2>

    <!-- HLS URL -->
    <div class="mb-6 p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg">
        <div class="flex justify-between items-start mb-2">
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">HLS Stream (m3u8)</p>
                <code class="text-sm text-green-300 font-mono break-all mt-2">
                    http://46.4.20.56:2082/streams/{{ $channel->id }}/index.m3u8
                </code>
            </div>
            <button type="button" class="copy-btn px-3 py-2 bg-blue-500/20 text-blue-300 text-sm rounded hover:bg-blue-500/30 transition" data-url="http://46.4.20.56:2082/streams/{{ $channel->id }}/index.m3u8">
                📋 Copy
            </button>
        </div>
        <p class="text-xs text-green-400 mt-2">🟢 Ready (if channel running)</p>
    </div>

    <!-- TS URL -->
    <div class="mb-6 p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg">
        <div class="flex justify-between items-start mb-2">
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">TS Stream (Single)</p>
                <code class="text-sm text-green-300 font-mono break-all mt-2">
                    http://46.4.20.56:2082/streams/{{ $channel->id }}.ts
                </code>
            </div>
            <button type="button" class="copy-btn px-3 py-2 bg-blue-500/20 text-blue-300 text-sm rounded hover:bg-blue-500/30 transition" data-url="http://46.4.20.56:2082/streams/{{ $channel->id }}.ts">
                📋 Copy
            </button>
        </div>
        <p class="text-xs text-green-400 mt-2">🟢 Ready (if channel running)</p>
    </div>

    <!-- Status Badge -->
    <div class="p-4 bg-green-500/10 border border-green-400/30 rounded-lg mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-green-300">✅ BOTH OUTPUTS ACTIVE</p>
                <p class="text-xs text-green-400 mt-1">Bandwidth: ~3000 kbps (HLS + TS)</p>
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
            • TS: Single stream, lower latency</span>
        </p>
    </div>
</div>

<script>
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
    const hls = 'http://46.4.20.56:2082/streams/{{ $channel->id }}/index.m3u8';
    const ts = 'http://46.4.20.56:2082/streams/{{ $channel->id }}.ts';
    const text = 'HLS: ' + hls + '\nTS: ' + ts;
    navigator.clipboard.writeText(text).then(() => {
        const oldText = this.textContent;
        this.textContent = '✅ Copied!';
        setTimeout(() => {
            this.textContent = oldText;
        }, 2000);
    });
});
</script>
