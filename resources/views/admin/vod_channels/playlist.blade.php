@extends('layouts.app')

@section('content')
    <h1 class="mb-3">
        Playlist [{{ $channel->name }}]
    </h1>

    {{-- Mesaje succes / eroare --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-7 gap-4">
        {{-- STÂNGA: PLAYLIST EXISTENT --}}
        <div class="col-span-5">
            <div class="rounded-lg border border-slate-700/50 bg-slate-800/30 overflow-hidden">
                <div class="border-b border-slate-700/50 bg-slate-700/20 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-200">Current Playlist</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b border-slate-700/30 bg-slate-800/50">
                            <th class="px-4 py-2 text-left font-medium text-slate-400 w-12">#</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-400">Name</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-400 w-20">Order</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-400 w-40">Actions</th>
                        </tr>
                        <tbody id="playlist-body">
                        @forelse($playlistItems as $index => $item)
                            <tr data-id="{{ $item->id }}" class="border-b border-slate-700/20 hover:bg-slate-700/20 transition cursor-move">
                                <td class="px-4 py-3 text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-slate-200">{{ optional($item->video)->title ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $item->sort_order }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        {{-- INFO --}}
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded px-2 py-1 text-xs font-medium text-blue-300 hover:bg-blue-500/20 transition"
                                            onclick="showVideoInfo({{ optional($item->video)->id ?? 0 }})"
                                        >ℹ️</button>

                                        {{-- UP --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.move-up', [$channel, $item]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded px-2 py-1 text-xs font-medium text-slate-300 hover:bg-slate-700/50 transition">↑</button>
                                        </form>

                                        {{-- DOWN --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.move-down', [$channel, $item]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded px-2 py-1 text-xs font-medium text-slate-300 hover:bg-slate-700/50 transition">↓</button>
                                        </form>

                                        {{-- DELETE --}}
                                        <form method="POST"
                                              action="{{ route('vod-channels.playlist.remove', [$channel, $item]) }}"
                                              onsubmit="return confirm('Remove this item from playlist?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded px-2 py-1 text-xs font-medium text-red-300 hover:bg-red-500/20 transition">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                    No items in this playlist yet.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <button id="save-order"
                    class="mt-3 inline-flex items-center rounded-lg bg-emerald-500/10 px-3 py-2 text-sm font-medium text-emerald-200 ring-1 ring-inset ring-emerald-400/20 hover:bg-emerald-500/15 transition">
                    💾 Save order
                </button>
            </div>
        </div>

        {{-- DREAPTA: INFO CANAL + LISTĂ VIDEOS CU "SELECT" --}}
        <div class="col-span-2">
            <div class="rounded-lg border border-slate-700/50 bg-slate-800/30 overflow-hidden mb-4">
                <div class="border-b border-slate-700/50 bg-slate-700/20 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-200">Channel Info</span>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Channel</p>
                        <p class="text-sm font-medium text-slate-200">{{ $channel->name }}</p>
                    </div>

                    @php
                        $rawCatId = $channel->video_category ?? null;
                        $category = $rawCatId ? \App\Models\VideoCategory::find($rawCatId) : null;
                    @endphp

                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Video Category</p>
                        <p class="text-sm font-medium text-slate-200">{{ $category?->name ?? '— no category —' }}</p>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('vod-channels.settings', $channel) }}"
                           class="inline-flex items-center rounded-lg bg-blue-500/15 px-3 py-2 text-sm font-medium text-blue-200 ring-1 ring-inset ring-blue-400/25 hover:bg-blue-500/20 transition">
                            Open Settings
                        </a>

                        <form method="POST" action="{{ route('encoding-jobs.queue-channel', $channel) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-lg bg-amber-500/10 px-3 py-2 text-sm font-medium text-amber-200 ring-1 ring-inset ring-amber-400/20 hover:bg-amber-500/15 transition">
                                Queue encoding for this playlist
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-700/50 bg-slate-800/30 overflow-hidden">
                <div class="border-b border-slate-700/50 bg-slate-700/20 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-200">Available Videos</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b border-slate-700/30 bg-slate-800/50">
                            <th class="px-4 py-2 text-left font-medium text-slate-400 w-12">
                                <input type="checkbox" id="select-all" class="rounded">
                            </th>
                            <th class="px-4 py-2 text-left font-medium text-slate-400">#</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-400">Video Name</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-400 w-32">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($videos as $video)
                            <tr class="border-b border-slate-700/20 hover:bg-slate-700/20 transition">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="video-pick rounded" value="{{ $video->id }}">
                                </td>
                                <td class="px-4 py-3 text-slate-400">{{ $video->id }}</td>
                                <td class="px-4 py-3 text-slate-200">{{ $video->title }}</td>
                                <td class="px-4 py-3 space-x-1">
                                    {{-- STREAM INFO / FFPROBE --}}
                                    <button type="button" 
                                            class="video-probe-btn inline-flex items-center rounded-lg bg-purple-500/15 px-2 py-1.5 text-xs font-medium text-purple-300 ring-1 ring-inset ring-purple-400/25 hover:bg-purple-500/20 transition"
                                            data-video-id="{{ $video->id }}"
                                            data-video-title="{{ $video->title }}">
                                        📊 Info
                                    </button>

                                    {{-- SELECT → adaugă în playlist --}}
                                    <form method="POST"
                                          action="{{ route('vod-channels.playlist.add', $channel) }}"
                                          class="inline">
                                        @csrf
                                        <input type="hidden" name="video_id" value="{{ $video->id }}">
                                        <button type="submit" class="inline-flex items-center rounded-lg bg-green-500/15 px-2 py-1.5 text-xs font-medium text-green-300 ring-1 ring-inset ring-green-400/25 hover:bg-green-500/20 transition">
                                            ➕ Select
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                    No videos found.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('vod-channels.playlist.add-bulk', $channel) }}" class="p-4 border-t border-slate-700/30">
                    @csrf
                    <input type="hidden" name="video_ids" id="video_ids">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-500/15 px-3 py-2 text-sm font-medium text-blue-200 ring-1 ring-inset ring-blue-400/20 hover:bg-blue-500/20 transition">
                        ➕ Add selected videos
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- STREAM INFO MODAL --}}
    <div id="probe-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-slate-900 rounded-2xl border border-slate-500/20 p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-slate-100" id="probe-title">Stream Info</h2>
                <button type="button" id="probe-close" class="text-slate-400 hover:text-slate-200 transition">✕</button>
            </div>
            <div id="probe-content" class="space-y-4 text-sm text-slate-300">
                <p class="text-slate-400">Loading...</p>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // STREAM INFO / FFPROBE
  const modal = document.getElementById('probe-modal');
  const closeBtn = document.getElementById('probe-close');
  const content = document.getElementById('probe-content');
  const titleEl = document.getElementById('probe-title');

  document.querySelectorAll('.video-probe-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const videoId = btn.getAttribute('data-video-id');
      const videoTitle = btn.getAttribute('data-video-title');
      
      titleEl.textContent = '📊 Stream Info: ' + videoTitle;
      content.innerHTML = '<p class="text-slate-400">⏳ Probing video...</p>';
      modal.classList.remove('hidden');

      try {
        const response = await fetch(`/videos/${videoId}/probe`);
        const data = await response.json();

        if (data.error) {
          content.innerHTML = `<p class="text-red-400">❌ ${data.error}</p>`;
          return;
        }

        let html = '<div class="space-y-3">';
        
        if (data.duration) {
          const mins = Math.floor(data.duration / 60);
          const secs = Math.floor(data.duration % 60);
          html += `<div><span class="text-slate-400">⏱️ Duration:</span> <span class="font-mono text-blue-300">${mins}m ${secs}s</span></div>`;
        }
        
        if (data.bit_rate) {
          html += `<div><span class="text-slate-400">📊 Bitrate:</span> <span class="font-mono text-blue-300">${data.bit_rate}</span></div>`;
        }

        if (data.video) {
          html += '<div class="border-t border-slate-600 pt-3 mt-3"><p class="font-semibold text-slate-200 mb-2">📹 Video Stream</p>';
          html += `<div><span class="text-slate-400">Codec:</span> <span class="font-mono">${data.video.codec}</span></div>`;
          if (data.video.width && data.video.height) {
            html += `<div><span class="text-slate-400">Resolution:</span> <span class="font-mono text-green-300">${data.video.width}x${data.video.height}</span></div>`;
          }
          if (data.video.fps) {
            html += `<div><span class="text-slate-400">FPS:</span> <span class="font-mono text-green-300">${data.video.fps}</span></div>`;
          }
          if (data.video.bitrate) {
            html += `<div><span class="text-slate-400">Bitrate:</span> <span class="font-mono text-green-300">${data.video.bitrate}</span></div>`;
          }
          html += '</div>';
        }

        if (data.audio) {
          html += '<div class="border-t border-slate-600 pt-3 mt-3"><p class="font-semibold text-slate-200 mb-2">🎵 Audio Stream</p>';
          html += `<div><span class="text-slate-400">Codec:</span> <span class="font-mono">${data.audio.codec}</span></div>`;
          if (data.audio.channels) {
            html += `<div><span class="text-slate-400">Channels:</span> <span class="font-mono text-amber-300">${data.audio.channels}</span></div>`;
          }
          if (data.audio.sample_rate) {
            html += `<div><span class="text-slate-400">Sample Rate:</span> <span class="font-mono text-amber-300">${data.audio.sample_rate}</span></div>`;
          }
          if (data.audio.bitrate) {
            html += `<div><span class="text-slate-400">Bitrate:</span> <span class="font-mono text-amber-300">${data.audio.bitrate}</span></div>`;
          }
          html += '</div>';
        }

        html += '</div>';
        content.innerHTML = html;
      } catch (err) {
        content.innerHTML = `<p class="text-red-400">❌ Request failed: ${err.message}</p>`;
      }
    });
  });

  closeBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.add('hidden');
    }
  });

  // DRAG & DROP REORDERING
  const tbody = document.getElementById('playlist-body');
  const btn = document.getElementById('save-order');

  if (tbody) {
    new Sortable(tbody, {
      animation: 150,
      ghostClass: 'bg-slate-700/50',
    });

    btn?.addEventListener('click', async () => {
      const ids = [...tbody.querySelectorAll('tr[data-id]')].map(tr => tr.dataset.id);

      const res = await fetch("{{ route('vod-channels.playlist.reorder', $channel) }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}",
          "Accept": "application/json",
        },
        body: JSON.stringify({ ids })
      });

      if (res.ok) {
        alert('Order saved successfully!');
        location.reload();
      } else {
        alert('Save failed');
      }
    });
  }

  // BULK SELECT
  const selectAll = document.getElementById('select-all');
  const videoPicks = document.querySelectorAll('.video-pick');
  const form = document.querySelector('form[action*="add-bulk"]');
  const hidden = document.getElementById('video_ids');

  selectAll?.addEventListener('change', () => {
    videoPicks.forEach(cb => cb.checked = selectAll.checked);
  });

  form?.addEventListener('submit', (e) => {
    const ids = [...document.querySelectorAll('.video-pick:checked')].map(x => x.value);
    if (!ids.length) {
      e.preventDefault();
      alert('Select at least 1 video');
      return;
    }
    hidden.value = ids.join(',');
  });

  // ═══════════════════════════════════════════════════════════
  // VIDEO INFO MODAL
  // ═══════════════════════════════════════════════════════════
  window.showVideoInfo = async function(videoId) {
    if (!videoId) {
      alert('Invalid video ID');
      return;
    }

    try {
      const response = await fetch(`/videos/${videoId}/info`);
      const data = await response.json();

      if (!data.success) {
        alert('Error loading video info: ' + data.error);
        return;
      }

      const video = data.video;
      const modal = document.getElementById('videoInfoModal');
      const content = document.getElementById('videoInfoContent');

      let videoHtml = '';
      let audioHtml = '';

      if (video.metadata && video.metadata.video) {
        const v = video.metadata.video;
        videoHtml = `
          <div class="mt-4">
            <h4 class="font-semibold text-slate-300 mb-2">🎬 Video Stream</h4>
            <dl class="grid grid-cols-2 gap-2 text-sm">
              <dt class="text-slate-400">Codec:</dt>
              <dd class="text-white font-mono">${v.codec || 'unknown'}</dd>
              <dt class="text-slate-400">Resolution:</dt>
              <dd class="text-white font-mono">${v.width || 0}×${v.height || 0}</dd>
              <dt class="text-slate-400">FPS:</dt>
              <dd class="text-white font-mono">${v.fps || 0}</dd>
              <dt class="text-slate-400">Bitrate:</dt>
              <dd class="text-white font-mono">${v.bitrate ? Math.round(v.bitrate/1000) + ' kbps' : 'N/A'}</dd>
            </dl>
          </div>
        `;
      }

      if (video.metadata && video.metadata.audio) {
        const a = video.metadata.audio;
        audioHtml = `
          <div class="mt-4">
            <h4 class="font-semibold text-slate-300 mb-2">🔊 Audio Stream</h4>
            <dl class="grid grid-cols-2 gap-2 text-sm">
              <dt class="text-slate-400">Codec:</dt>
              <dd class="text-white font-mono">${a.codec || 'unknown'}</dd>
              <dt class="text-slate-400">Channels:</dt>
              <dd class="text-white font-mono">${a.channels || 0}</dd>
              <dt class="text-slate-400">Sample Rate:</dt>
              <dd class="text-white font-mono">${a.sample_rate || 'unknown'}</dd>
              <dt class="text-slate-400">Bitrate:</dt>
              <dd class="text-white font-mono">${a.bitrate || 'N/A'}</dd>
            </dl>
          </div>
        `;
      }

      content.innerHTML = `
        <div class="space-y-4">
          <div>
            <h3 class="text-lg font-bold text-white mb-2">${video.title}</h3>
            <p class="text-xs text-slate-500 mb-2">ID: ${video.id}</p>
            <p class="text-sm text-slate-400">Duration: <span class="text-white font-mono">${video.duration || '--:--:--'}</span></p>
            <p class="text-sm text-slate-400">Category: <span class="text-white font-mono">${video.category}</span></p>
          </div>
          <div class="border-t border-slate-700/50 pt-4">
            <p class="text-xs text-slate-500 mb-2">File path:</p>
            <p class="text-xs text-slate-300 font-mono bg-slate-900/50 p-2 rounded overflow-x-auto">${video.file_path}</p>
          </div>
          ${videoHtml}
          ${audioHtml}
        </div>
      `;

      modal.classList.remove('hidden');
    } catch (error) {
      alert('Error fetching video info: ' + error.message);
    }
  };

  document.getElementById('videoInfoModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
      e.currentTarget.classList.add('hidden');
    }
  });

  document.getElementById('closeVideoInfoBtn')?.addEventListener('click', () => {
    document.getElementById('videoInfoModal').classList.add('hidden');
  });

  // ═══════════════════════════════════════════════════════════
  // ENCODE ALL VIDEOS TO TS
  // ═══════════════════════════════════════════════════════════
  window.startEncodingAll = async function(channelId) {
    const btn = document.getElementById('encodeAllBtn');
    const progress = document.getElementById('encodingProgress');
    const progressBar = document.getElementById('encodeProgressBar');
    const status = document.getElementById('encodeStatus');
    const message = document.getElementById('encodeMessage');

    btn.disabled = true;
    progress.classList.remove('hidden');
    message.textContent = 'Starting encode jobs for all videos in playlist...';

    try {
      const response = await fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({}),
      });

      const data = await response.json();

      if (data.status === 'success') {
        message.textContent = `✅ ${data.message}. Encoding in background...`;
        status.textContent = `${data.total_jobs} jobs queued`;
        
        // Poll for progress
        let completed = 0;
        const pollInterval = setInterval(async () => {
          try {
            const statusResp = await fetch(`/vod-channels/${channelId}/engine/encoding-jobs`);
            const statusData = await statusResp.json();
            
            if (statusData.jobs) {
              const total = statusData.jobs.length;
              completed = statusData.jobs.filter(j => j.status === 'completed').length;
              const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
              
              progressBar.style.width = percent + '%';
              status.textContent = `${completed}/${total} complete`;
              
              if (completed === total && total > 0) {
                clearInterval(pollInterval);
                message.textContent = `✅ All videos encoded successfully!`;
                setTimeout(() => {
                  progress.classList.add('hidden');
                  btn.disabled = false;
                  location.reload();
                }, 2000);
              }
            }
          } catch (err) {
            console.error('Poll error:', err);
          }
        }, 2000);

        // Stop polling after 5 minutes
        setTimeout(() => clearInterval(pollInterval), 300000);
      } else {
        message.textContent = '❌ ' + (data.message || 'Failed to start encoding');
        btn.disabled = false;
      }
    } catch (error) {
      message.textContent = '❌ Error: ' + error.message;
      btn.disabled = false;
    }
  };
});
</script>

<!-- Video Info Modal -->
<div id="videoInfoModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-slate-800 border border-slate-700 rounded-lg max-w-2xl w-full my-6">
    <div class="sticky top-0 bg-slate-800 border-b border-slate-700 px-6 py-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-white">📊 Video Information</h3>
      <button 
        id="closeVideoInfoBtn"
        type="button"
        class="text-slate-400 hover:text-white text-2xl transition"
      >×</button>
    </div>
    <div id="videoInfoContent" class="p-6 max-h-96 overflow-y-auto">
      <!-- Content loaded by JS -->
    </div>
  </div>
</div>
