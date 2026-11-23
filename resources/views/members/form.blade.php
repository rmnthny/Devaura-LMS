@extends('layouts.library')
@section('title', $member->exists ? 'Edit member' : 'Add member')
@section('content')
<h1 class="h2">@yield('title')</h1>
<form class="card card-body" method="post" action="{{ $member->exists ? route('api.v1.members.update', $member) : route('api.v1.members.store') }}">
    @csrf
    @if ($member->exists)
        @method('PUT')
    @else
        <p class="text-body-secondary">The member receives a welcome email with a generated temporary password.</p>
    @endif
    @include('members.fields')
    <div class="mt-3"><button class="btn btn-primary">Save member</button> <a class="btn btn-link" href="{{ route('members.index') }}">Cancel</a></div>
</form>
@endsection
