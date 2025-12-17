@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Import Videos into: <span class="text-blue-600">{{ $category->name }}</span></h1>
        <a href="{{ route('video-categories.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Back</a>
    </div>

    <!-- Instructions -->
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 class="font-semibold text-blue-900 mb-2">📌 How to import:</h3>
        <ol class="text-sm text-blue-800 space-y-1 ml-4 list-decimal">
            <li>Browse the folders and find your video files</li>
            <li>Check the boxes next to the videos you want to import</li>
            <li>Click <strong>"📥 Import Selected"</strong> button</li>
            <li>Wait for the page to reload with a success message</li>
        </ol>
    </div>    <!-- Breadcrumb Navigation -->
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
        <button type="button" onclick="selectAllFiles()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">✓ Select All</button>
        <button type="button" onclick="deselectAllFiles()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">✗ Deselect All</button>
    </div>

    <!-- File Browser Form -->
    <form id="browser-form" method="POST" action="{{ route('admin.video_categories.import', $category) }}" enctype="multipart/form-data">
        @csrf
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
                                name="files[]"
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
                                
                                <!-- Import Form (POST) -->
                                <form method="POST" action="{{ route('admin.video_categories.import', $category) }}" class="inline" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="files[]" value="{{ $file['path'] }}">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">📥 Import</button>
                                </form>
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

        <!-- Import Selected Button -->
        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold" id="import-btn" onclick="document.getElementById('browser-form').submit(); return false;">
                📥 Import Selected
            </button>
            <span class="text-sm text-gray-600 self-center" id="selected-count">(0 selected)</span>
        </div>
    </form>
</div>

<!-- Success Message -->
@if ($message = session('success'))
<div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
    ✅ {{ $message }}
</div>
@endif

@if ($message = session('error'))
<div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
    ❌ {{ $message }}
</div>
@endif

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
// Select/Deselect functions
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
    const span = document.getElementById('selected-count');
    if (span) span.textContent = '(' + selected + ' selected)';
}

// Preview function
function previewVideo(path, name) {
    document.getElementById('preview-title').textContent = '⏳ Loading...';
    document.getElementById('preview-modal').classList.remove('hidden');
    
    fetch('{{ route("video-categories.preview") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ path: path }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('preview-title').textContent = 'Preview: ' + name;
            document.getElementById('preview-source').src = data.url + '?t=' + Date.now();
            document.getElementById('preview-video').load();
        } else {
            alert('Preview failed');
            closePreview();
        }
    })
    .catch(e => {
        alert('Error: ' + e.message);
        closePreview();
    });
}

function closePreview() {
    document.getElementById('preview-modal').classList.add('hidden');
    document.getElementById('preview-video').pause();
}

// Setup on page load
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox change listeners
    document.querySelectorAll('.file-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    
    // Form submission
    const form = document.getElementById('browser-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.file-checkbox:checked').length;
            if (selected === 0) {
                e.preventDefault();
                alert('Please select at least one file');
                return false;
            }
            return true;
        });
    }
    
    updateSelectedCount();
});
</script>

<style>
#import-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
@endsection
