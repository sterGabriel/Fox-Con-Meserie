@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-100 mb-2">📁 Import Videos from Server</h1>
        <p class="text-slate-400">Browse server directory: <code class="bg-slate-800 px-2 py-1 rounded">{{ $mediaRoot }}</code></p>
    </div>

    <!-- Messages -->
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-lg">
            <p class="text-red-400 font-semibold mb-2">❌ Errors occurred:</p>
            <ul class="list-disc list-inside space-y-1 text-red-300 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-lg">
            <p class="text-green-400 font-semibold">✅ {{ session('success') }}</p>
            @if(session('imported'))
                <p class="text-green-300 text-sm mt-2">Imported: {{ implode(', ', session('imported')) }}</p>
            @endif
            @if(session('skipped'))
                <p class="text-yellow-300 text-sm">Skipped: {{ implode(', ', session('skipped')) }}</p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('media.import.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-4 gap-6">
            <!-- Left: File Browser -->
            <div class="col-span-3">
                <div class="bg-slate-800/30 border border-slate-600/30 rounded-xl overflow-hidden">
                    <!-- Header with breadcrumb -->
                    <div class="bg-slate-700/20 border-b border-slate-600/30 px-6 py-4">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-lg font-semibold text-slate-100">🎬 Available Videos</h2>
                            <a href="{{ route('media.import') }}" class="text-xs text-blue-400 hover:text-blue-300">⚡ Root</a>
                        </div>

                        <!-- Breadcrumb -->
                        <div class="text-xs text-slate-400 font-mono mb-3">
                            / @if($path) 
                                @php
                                    $parts = explode('/', $path);
                                    $current = '';
                                @endphp
                                @foreach($parts as $part)
                                    @php $current .= ($current ? '/' : '') . $part; @endphp
                                    <a href="{{ route('media.import', ['path' => $current]) }}" class="text-blue-400 hover:text-blue-300">{{ $part }}</a> /
                                @endforeach
                            @endif
                        </div>

                        <!-- Search -->
                        <input type="text" name="search" placeholder="🔍 Search files..." value="{{ $search }}" 
                            class="w-full px-3 py-2 text-sm bg-slate-900/50 border border-slate-600/30 rounded-lg text-slate-200 placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <!-- File List -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-600/30 bg-slate-900/20">
                                    <th class="px-4 py-3 text-left text-slate-400 w-8">
                                        <input type="checkbox" id="select-all-files" class="rounded">
                                    </th>
                                    <th class="px-4 py-3 text-left text-slate-400">Name</th>
                                    <th class="px-4 py-3 text-right text-slate-400 w-24">Size</th>
                                    <th class="px-4 py-3 text-left text-slate-400 w-32">Modified</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Parent Directory -->
                                @if($parent !== null)
                                    <tr class="border-b border-slate-700/20 hover:bg-slate-700/10 transition">
                                        <td colspan="4" class="px-4 py-3">
                                            <a href="{{ route('media.import', ['path' => $parent]) }}" class="text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                                📁 .. (Parent Directory)
                                            </a>
                                        </td>
                                    </tr>
                                @endif

                                <!-- Folders -->
                                @forelse($items->where('type', 'folder') as $item)
                                    <tr class="border-b border-slate-700/20 hover:bg-slate-700/10 transition">
                                        <td class="px-4 py-3"></td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('media.import', ['path' => $item['path']]) }}" class="text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                                📁 {{ $item['name'] }} <span class="text-xs text-slate-500">({{ $item['children_count'] }} items)</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-400">-</td>
                                        <td class="px-4 py-3 text-sm text-slate-500">{{ date('M d, Y H:i', $item['modified']) }}</td>
                                    </tr>
                                @empty
                                @endforelse

                                <!-- Video Files -->
                                @forelse($items->where('type', 'file') as $item)
                                    <tr class="border-b border-slate-700/20 hover:bg-slate-700/10 transition">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="files[]" value="{{ $item['path'] }}" class="file-checkbox rounded"
                                                @if(in_array($item['full_path'], $existingVideos)) disabled title="Already imported" @endif>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span>🎬</span>
                                                <div>
                                                    <div class="text-slate-200">{{ $item['name'] }}</div>
                                                    @if(in_array($item['full_path'], $existingVideos))
                                                        <div class="text-xs text-yellow-400">✓ Already imported</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-400 text-sm">{{ formatBytes($item['size']) }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-500">{{ date('M d, Y H:i', $item['modified']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                            @if($search)
                                                No videos matching "{{ $search }}"
                                            @else
                                                No video files in this folder
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Import Settings -->
            <div class="col-span-1">
                <div class="space-y-6 sticky top-6">
                    <!-- Category Select -->
                    <div class="bg-slate-800/30 border border-slate-600/30 rounded-xl p-6">
                        <label class="block text-sm font-semibold text-slate-200 mb-3">📂 Category</label>
                        <select name="category_id" class="w-full px-3 py-2 text-sm bg-slate-900/50 border border-slate-600/30 rounded-lg text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="">-- No Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Stats -->
                    <div class="bg-slate-800/30 border border-slate-600/30 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-slate-200 mb-3">📊 Selection</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Selected:</span>
                                <span id="selected-count" class="text-slate-200 font-semibold">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Size:</span>
                                <span id="selected-size" class="text-slate-200 font-semibold">0 B</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submit-btn" disabled class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-semibold text-center">
                        ✅ Import Selected
                    </button>

                    <!-- Select All -->
                    <button type="button" id="toggle-all" class="w-full px-4 py-2 bg-slate-700/50 text-slate-200 rounded-lg hover:bg-slate-700 transition text-sm">
                        ☑️ Select All Files
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-files');
    const fileCheckboxes = document.querySelectorAll('.file-checkbox:not(:disabled)');
    const submitBtn = document.getElementById('submit-btn');
    const toggleAllBtn = document.getElementById('toggle-all');
    const selectedCountEl = document.getElementById('selected-count');
    const selectedSizeEl = document.getElementById('selected-size');

    function updateStats() {
        const checked = document.querySelectorAll('.file-checkbox:checked').length;
        const totalSize = Array.from(document.querySelectorAll('.file-checkbox:checked')).reduce((sum, cb) => {
            const row = cb.closest('tr');
            const sizeText = row.querySelector('td:nth-child(4)')?.textContent.trim() || '0 B';
            return sum + parseSize(sizeText);
        }, 0);

        selectedCountEl.textContent = checked;
        selectedSizeEl.textContent = formatBytes(totalSize);
        submitBtn.disabled = checked === 0;

        // Update select-all checkbox state
        const visibleCount = fileCheckboxes.length;
        selectAllCheckbox.checked = checked > 0 && checked === visibleCount;
        selectAllCheckbox.indeterminate = checked > 0 && checked < visibleCount;
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function parseSize(sizeText) {
        const match = sizeText.match(/([0-9.]+)\s*([KMGT]?B)/i);
        if (!match) return 0;
        
        const value = parseFloat(match[1]);
        const unit = match[2].toUpperCase();
        const multipliers = { 'B': 1, 'KB': 1024, 'MB': 1024**2, 'GB': 1024**3, 'TB': 1024**4 };
        
        return value * (multipliers[unit] || 1);
    }

    // Select all files
    selectAllCheckbox.addEventListener('change', function() {
        fileCheckboxes.forEach(cb => cb.checked = this.checked);
        updateStats();
    });

    // Toggle all files button
    toggleAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        fileCheckboxes.forEach(cb => cb.checked = !cb.checked);
        updateStats();
    });

    // Individual checkbox changes
    fileCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateStats);
    });

    // Handle search form submission (enter key)
    document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            window.location.href = '{{ route('media.import') }}' + '?path={{ $path }}&search=' + this.value;
        }
    });

    updateStats();
});
</script>

@php
// Helper function to format bytes
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
@endphp
@endsection
