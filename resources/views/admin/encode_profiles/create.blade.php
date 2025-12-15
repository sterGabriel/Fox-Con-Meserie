@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-slate-100 mb-8">Create New Profile</h1>

        <!-- Form -->
        <form action="{{ route('encode-profiles.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.encode_profiles._form')
        </form>
    </div>
</div>
@endsection
