@extends('layouts.panel')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Create Vod Channel</h1>
            <p class="text-sm text-slate-400">Pasul 1: alege doar numele canalului.</p>
        </div>
        <a href="{{ route('vod-channels.index') }}" class="text-sm text-blue-600">
            ← Back to Vod Channels
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

    <form method="POST" action="{{ route('vod-channels.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm mb-1">Channel Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="border rounded px-3 py-2 w-full">
        </div>

        <button type="submit" class="px-4 py-2 rounded bg-black text-white text-sm">
            Create Channel
        </button>
    </form>
@endsection
