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
                                <td class="px-4 py-3">
                                    {{-- SELECT → adaugă în playlist --}}
                                    <form method="POST"
                                          action="{{ route('vod-channels.playlist.add', $channel) }}"
                                          class="inline">
                                        @csrf
                                        <input type="hidden" name="video_id" value="{{ $video->id }}">
                                        <button type="submit" class="inline-flex items-center rounded-lg bg-green-500/15 px-3 py-1.5 text-xs font-medium text-green-300 ring-1 ring-inset ring-green-400/25 hover:bg-green-500/20 transition">
                                            Select
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
@endsection

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
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
});
</script>