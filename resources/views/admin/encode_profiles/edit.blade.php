@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-slate-100 mb-8">Edit Profile: {{ $profile->name }}</h1>

        @if ($message = Session::get('error'))
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
                {{ $message }}
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('encode-profiles.update', $profile) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')
            @include('admin.encode_profiles._form', ['profile' => $profile])
        </form>
    </div>
</div>
@endsection
