@extends('layouts.panel')

@section('content')
    <h1 class="text-2xl font-semibold mb-4">Dashboard</h1>
    <p class="text-sm text-slate-300">
        You're logged in, {{ auth()->user()->name }}.
    </p>
@endsection
