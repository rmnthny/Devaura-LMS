@extends('layouts.library')
@section('title', 'Archive')
@section('content')
    <h1 class="h2 mb-3">Archive</h1>
    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Archive sections">
        @foreach ($sections as $type => $entry)
            <a class="btn {{ $type === $section ? 'btn-primary' : 'btn-outline-secondary' }}"
                href="{{ route('archive.index', ['section' => $type]) }}" @if ($type === $section) aria-current="page" @endif>
                {{ $entry['title'] }} <span class="badge text-bg-light ms-1">{{ $entry['records']->total() }}</span>
            </a>
        @endforeach
    </nav>

    @if ($section !== null)
        @php
            $records = $sections[$section]['records'];
        @endphp
        <section class="border rounded-3 bg-white mb-3" aria-labelledby="archive-section-title">
            <div class="p-3 p-md-4">
                <h2 class="h5 mb-1" id="archive-section-title">Archived {{ strtolower($sections[$section]['title']) }}</h2>
                @if ($records->count())
                    <p class="text-body-secondary small mb-0">Showing {{ $records->firstItem() }}–{{ $records->lastItem() }} of {{ $records->total() }}</p>
                @endif
            </div>
            @if ($records->isEmpty())
                <div class="border-top p-4 text-body-secondary">
                    @if ($records->total())
                        <a href="{{ $records->appends(['section' => $section])->url(1) }}">Back to the first page</a>
                    @else
                        No archived {{ strtolower($sections[$section]['title']) }}.
                    @endif
                </div>
            @else
                <div class="table-responsive border-top">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th scope="col" class="ps-3">Record</th><th scope="col">Archived on</th><th scope="col">Six-month mark</th><th scope="col">Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $record)
                                @php
                                    $label = match ($section) {
                                        'books' => $record->book_title,
                                        'accounts' => $record->email,
                                        default => $record->fullName(),
                                    };
                                    $detail = match ($section) {
                                        'books' => $record->book_qr_value,
                                        'members' => $record->user?->email,
                                        'accounts' => $record->reference_number,
                                        default => null,
                                    };
                                @endphp
                                <tr>
                                    <th scope="row" class="ps-3 fw-semibold">
                                        {{ $label }}
                                        @if ($detail)<small class="d-block text-body-secondary fw-normal text-break">{{ $detail }}</small>@endif
                                    </th>
                                    <td class="text-nowrap">{{ $record->deleted_at->format('M j, Y') }}</td>
                                    <td class="text-nowrap text-body-secondary">{{ $record->deleted_at->copy()->addMonthsNoOverflow(6)->format('M j, Y') }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <form method="post" action="{{ route('archive.restore', [$section, $record->getKey()]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" aria-label="Restore {{ $label }}">Restore</button>
                                            </form>
                                            @if ($section === 'members' && auth()->user()->hasRole(\App\Support\Rbac::SUPERADMIN))
                                                <a href="{{ route('archive.members.confirm-purge', $record->getKey()) }}" class="btn btn-sm btn-outline-danger"
                                                    aria-label="Review permanent deletion for {{ $label }}">Delete permanently</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($records->hasPages())
                    <div class="px-3 pt-3">{{ $records->appends(['section' => $section])->links('pagination::bootstrap-5') }}</div>
                @endif
            @endif
        </section>
    @endif
@endsection
