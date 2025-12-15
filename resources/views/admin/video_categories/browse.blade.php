@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Import Videos into: <span class="text-blue-600">{{ $category->name }}</span></h1>
        <a href="{{ route('admin.video_categories.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Back</a>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="mb-4 p-3 bg-gray-100 rounded-lg flex items-center gap-2 overflow-x-auto">
        @foreach ($breadcrumb as $crumb)
            @if ($loop->last)
                <span class="text-gray-700 font-semibold">{{ $crumb['name'] }}</span>
            @else
                <a href="?path={{ urlencode($crumb['path']) }}" class="text-blue-600 hover:underline">{{ $crumb['name'] }}</a>
                <span class="text-gray-400">/</span>
            @endif
        @endforeach
    </div>

    <!-- Navigation Buttons -->
    <div class="mb-4 flex gap-2">
        @if ($parentPath !== $basePath || $currentPath !== $basePath)
            <a href="?path={{ urlencode($parentPath) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded inline-flex items-center gap-2">
                <span>⬆</span> Up
            </a>
        @endif
        <button onclick="selectAllFiles()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">✓ Select All</button>
        <button onclick="deselectAllFiles()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">✗ Deselect All</button>
        <button onclick="importSelected()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold" id="import-btn">📥 Import Selected</button>
    </div>

    <!-- File Browser -->
    <form id="browser-form">
        <div class="grid grid-cols-1 gap-4">
            <!-- Folders Section -->
            @if (!empty($dirs))
            <div>
                <h2 class="text-lg font-semibold mb-2">📁 Folders</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($dirs as $dir)
                    <a href="?path={{ urlencode($dir['path']) }}" class="border border-gray-300 rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📂</span>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-700">{{ $dir['name'] }}</div>
                                <div class="text-xs text-gray-500">Open folder</div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Videos Section -->
            @if (!empty($files))
            <div>
                <h2 class="text-lg font-semibold mb-2">🎬 Video Files</h2>
                <div class="space-y-3">
                    @foreach ($files as $file)
                    <div class="border border-gray-300 rounded-lg p-4 hover:bg-gray-50 transition {{ $file['imported'] ? 'bg-green-50 opacity-60' : '' }}">
                        <div class="flex items-start gap-3">
                            <!-- Checkbox -->
                            <input 
                                type="checkbox" 
                                class="mt-1 file-checkbox" 
                                value="{{ $file['path'] }}"
                                {{ $file['imported'] ? 'disabled' : '' }}
                            >

                            <!-- File Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-gray-700 truncate">{{ $file['name'] }}</span>
                                    @if ($file['imported'])
                                    <span class="bg-green-600 text-white text-xs px-2 py-1 rounded">✓ Imported</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 space-y-1">
                                    <div>📏 {{ $file['size_formatted'] }} | ⏱️ {{ $file['duration'] }}</div>
                                    @if ($file['metadata'] && isset($file['metadata']['video']))
                                    <div>
                                        <span class="bg-gray-200 px-2 py-0.5 rounded text-xs">
                                            {{ $file['metadata']['video']['width'] }}x{{ $file['metadata']['video']['height'] }}
                                        </span>
                                        @if ($file['metadata']['audio'])
                                        <span class="bg-gray-200 px-2 py-0.5 rounded text-xs">
                                            {{ $file['metadata']['audio']['channels'] }} ch
                                        </span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-1 flex-shrink-0">
                                @if (!$file['imported'])
                                <button type="button" onclick="previewVideo('{{ $file['path'] }}', '{{ $file['name'] }}')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">🎥 Preview</button>
                                <button type="button" onclick="importSingleFile('{{ $file['path'] }}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">📥 Import</button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if (empty($dirs) && empty($files))
            <div class="text-center py-8 text-gray-500">
                <p class="text-lg">📭 No video files found in this folder</p>
            </div>
            @endif
        </div>
    </form>
</div>

<!-- Modal for Previews -->
<div id="preview-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-2xl max-h-96 flex flex-col">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold" id="preview-title">Preview</h3>
            <button type="button" onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
        </div>
        <div class="flex-1 overflow-auto p-4">
            <video id="preview-video" controls class="w-full rounded" style="max-height: 300px;">
                <source id="preview-source" src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        <div class="p-4 border-t flex justify-end gap-2">
            <button type="button" onclick="closePreview()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Close</button>
        </div>
    </div>
</div>

<script>
function selectAllFiles() {
    document.querySelectorAll('.file-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
    updateSelectedCount();
}

function deselectAllFiles() {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.file-checkbox:checked').length;
    const btn = document.getElementById('import-btn');
    if (selected > 0) {
        btn.textContent = `📥 Import Selected (${selected})`;
        btn.disabled = false;
    } else {
        btn.textContent = '📥 Import Selected';
        btn.disabled = true;
    }
}

function importSelected() {
    const selected = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Please select at least one file');
        return;
    }

    const btn = document.getElementById('import-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Importing...';

    fetch('{{ route("admin.video_categories.import", $category) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ files: selected }),
    })
    .then(r => r.json())
    .then(data => {
        alert(`✓ ${data.message}`);
        setTimeout(() => location.reload(), 1000);
    })
    .catch(e => {
        alert('❌ Import failed: ' + e.message);
        btn.disabled = false;
        btn.textContent = '📥 Import Selected';
    });
}

function importSingleFile(path) {
    if (confirm('Import this file?')) {
        const form = new FormData();
        form.append('files', JSON.stringify([path]));
        
        fetch('{{ route("admin.video_categories.import", $category) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ files: [path] }),
        })
        .then(r => r.json())
        .then(data => {
            alert('✓ File imported');
            location.reload();
        })
        .catch(e => alert('❌ Error: ' + e.message));
    }
}

function previewVideo(path, name) {
    document.getElementById('preview-title').textContent = 'Preview: ' + name;
    document.getElementById('preview-source').src = `/storage/video?path=${encodeURIComponent(path)}`;
    document.getElementById('preview-video').load();
    document.getElementById('preview-modal').classList.remove('hidden');
}

function closePreview() {
    document.getElementById('preview-modal').classList.add('hidden');
    document.getElementById('preview-video').pause();
}

// Update count on checkbox change
document.querySelectorAll('.file-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

updateSelectedCount();
</script>

<style>
#import-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
@endsection
