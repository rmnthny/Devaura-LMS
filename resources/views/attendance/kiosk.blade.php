<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Public Kiosk | UBNHS Library</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
</head>
<body class="bg-body-tertiary">
<main class="container py-4">
    <h1 class="h2 mb-3">Public Kiosk</h1>
    @if ($unavailable ?? false)
        <div class="card card-body">
            <h2 class="h5">Public attendance is temporarily unavailable.</h2>
            <p class="mb-2">Please try again when the staff application is back online.</p>
            <div><a href="{{ route('kiosk.index') }}" class="btn btn-outline-primary">Try again</a></div>
        </div>
    @elseif ($status = session('kiosk_status'))
        <div class="card card-body">
            <p class="mb-2" role="status">{{ $status }}</p>
            <div><a href="{{ route('kiosk.index') }}" class="btn btn-primary">Resume</a></div>
        </div>
    @elseif ($open)
        @if ($errors->kiosk->any())
            <ul class="alert alert-danger list-unstyled" role="alert">
                @foreach ($errors->kiosk->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <p class="text-body-secondary">New library member? Ask the Head Librarian to register your account. Walk-in visitors can use visitor attendance.</p>
        @include('attendance.forms', ['actionUrl' => route('kiosk.record')])
    @else
        <div class="card card-body">
            <h2 class="h5">Kiosk closed</h2>
            <p class="mb-2">Public attendance opens when the Head Librarian or someone assigned to the current shift times in.</p>
            <div><a href="{{ route('kiosk.index') }}" class="btn btn-outline-primary">Resume</a></div>
        </div>
    @endif
</main>
</body>
</html>
