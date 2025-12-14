@extends('layouts.panel')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Add Live Channel</h1>
            <p class="text-sm text-slate-400">Adaugă un nou canal live.</p>
        </div>
        <a href="{{ route('live-channels.index') }}" class="text-sm text-blue-600">
            ← Back to list
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 text-sm text-red-500">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('live-channels.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm mb-1">Channel Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="border rounded px-3 py-2 w-full">
        </div>

        <div>
            <label class="block text-sm mb-1">Input URL (stream)</label>
            <input type="text" name="input_url" value="{{ old('input_url') }}"
                   class="border rounded px-3 py-2 w-full">
        </div>

        <div>
            <label class="block text-sm mb-1">Encoder profile</label>
            <select name="encoder_profile" class="border rounded px-3 py-2 w-full">
                <option value="h264_1500k" {{ old('encoder_profile') == 'h264_1500k' ? 'selected' : '' }}>H.264 – 1500kbps</option>
                <option value="h264_800k" {{ old('encoder_profile') == 'h264_800k' ? 'selected' : '' }}>H.264 – 800kbps</option>
            </select>
        </div>

        <div class="flex items-center space-x-2">
            <input type="checkbox" name="enabled" id="enabled" {{ old('enabled', true) ? 'checked' : '' }}>
            <label for="enabled" class="text-sm">Enabled</label>
        </div>

        <button type="submit" class="px-4 py-2 rounded bg-black text-white text-sm">
            Save Channel
        </button>
    </form>
@endsection
