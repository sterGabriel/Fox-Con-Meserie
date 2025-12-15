@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-100">Encode Profiles</h1>
            <a href="{{ route('encode-profiles.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                + Create Profile
            </a>
        </div>

        <!-- Success/Error Messages -->
        @if ($message = Session::get('success'))
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
                {{ $message }}
            </div>
        @endif

        @if ($message = Session::get('error'))
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
                {{ $message }}
            </div>
        @endif

        <!-- Profiles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($profiles as $profile)
                <div class="rounded-lg border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm hover:border-slate-400/30 transition">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-100">{{ $profile->name }}</h3>
                            <p class="text-xs text-slate-400 mt-1">
                                <span class="inline-block px-2 py-1 bg-blue-500/20 text-blue-300 rounded">
                                    {{ $profile->container === 'mpegts' ? 'LIVE' : 'VOD' }}
                                </span>
                            </p>
                        </div>
                        @if (!$profile->is_system)
                            <button class="text-slate-400 hover:text-slate-200 transition" onclick="document.getElementById('delete-{{ $profile->id }}').click()">
                                ⋮
                            </button>
                        @endif
                    </div>

                    <!-- Profile Details -->
                    <div class="space-y-2 text-sm text-slate-300 mb-4">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Resolution:</span>
                            <span>{{ $profile->width }}×{{ $profile->height }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">FPS:</span>
                            <span>{{ $profile->fps }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Video Codec:</span>
                            <span>{{ $profile->video_codec ?? 'libx264' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Preset:</span>
                            <span>{{ $profile->preset }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Video Bitrate:</span>
                            <span>{{ $profile->video_bitrate_k }}k</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Audio Codec:</span>
                            <span>{{ $profile->audio_codec }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Audio Bitrate:</span>
                            <span>{{ $profile->audio_bitrate_k }}k</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Channels:</span>
                            <span>{{ $profile->audio_channels === 2 ? 'Stereo' : 'Mono' }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 pt-4 border-t border-slate-500/20">
                        <a href="{{ route('encode-profiles.edit', $profile) }}" class="flex-1 text-center px-3 py-2 text-sm bg-slate-800/50 text-slate-300 rounded hover:bg-slate-700/50 transition">
                            Edit
                        </a>
                        @if (!$profile->is_system)
                            <form action="{{ route('encode-profiles.duplicate', $profile) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="flex-1 px-3 py-2 text-sm bg-slate-800/50 text-slate-300 rounded hover:bg-slate-700/50 transition">
                                    Dup
                                </button>
                            </form>
                            <form action="{{ route('encode-profiles.destroy', $profile) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 text-sm bg-red-500/10 text-red-400 rounded hover:bg-red-500/20 transition" onclick="return confirm('Delete profile?')">
                                    Del
                                </button>
                                <button id="delete-{{ $profile->id }}" style="display: none;"></button>
                            </form>
                        @else
                            <span class="flex-1 px-3 py-2 text-sm bg-slate-800/50 text-slate-500 rounded text-center">System</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-slate-400">No profiles yet. <a href="{{ route('encode-profiles.create') }}" class="text-blue-400 hover:text-blue-300">Create one</a></p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $profiles->links() }}
        </div>
    </div>
</div>
@endsection
