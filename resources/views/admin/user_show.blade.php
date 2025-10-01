
@extends('layouts.app')

@section('content')
    <h1>User Details</h1>
    <p><strong>Name:</strong> {{ $user->name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Status:</strong> {{ $user->status }}</p>
    <p><strong>Photo:</strong>
        @if($user->photo)
            <img src="{{ asset('storage/' . $user->photo) }}" alt="User Photo" style="width:150px; height:150px; object-fit:cover; border-radius:4px;">
        @else
            No photo uploaded.
        @endif
    </p>
    <a href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
@endsection