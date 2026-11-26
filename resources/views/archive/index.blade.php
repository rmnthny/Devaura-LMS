
@extends('layouts.library')

@section('title', 'Archive')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/archive.css') }}">
@endpush

@section('content')

<section class="archive-page">

    <!-- PAGE HEADER -->

    <header class="archive-header">
        <div class="archive-heading">
            <span class="archive-heading-icon" aria-hidden="true">
                <i class="bi bi-archive"></i>
            </span>

            <div>
                <h1>Archive</h1>
                <p>
                    Review archived library records and restore items
                    when needed.
                </p>
            </div>
        </div>
    </header>

    <!-- ARCHIVE SECTION NAVIGATION -->

    <nav class="archive-tabs" aria-label="Archive sections">
        @foreach($sections as $type => $entry)
            @php
                $icon = match ($type) {
                    'books' => 'bi-book',
                    'members' => 'bi-people',
                    'accounts' => 'bi-person-badge',
                    default => 'bi-archive',
                };
            @endphp

            <a
                class="archive-tab {{ $type === $section ? 'is-active' : '' }}"
                href="{{ route('archive.index', ['section' => $type]) }}"
                @if($type === $section)
                    aria-current="page"
                @endif
            >
                <i class="bi {{ $icon }}" aria-hidden="true"></i>

                <span>{{ $entry['title'] }}</span>

                <span class="archive-tab-count">
                    {{ $entry['records']->total() }}
                </span>
            </a>
        @endforeach
    </nav>

    <!-- SELECTED ARCHIVE -->

    @if($section !== null)

        @php
            $records = $sections[$section]['records'];
            $sectionTitle = $sections[$section]['title'];
        @endphp

        <section
            class="archive-directory"
            aria-labelledby="archive-section-title"
        >

            <!-- DIRECTORY HEADER -->

            <header class="archive-directory-header">
                <div>
                    <h2 id="archive-section-title">
                        Archived {{ strtolower($sectionTitle) }}
                    </h2>

                    <p>
                        @if($records->count())
                            Showing
                            {{ $records->firstItem() }}–{{ $records->lastItem() }}
                            of {{ $records->total() }} archived records.
                        @else
                            View the records in this archive section.
                        @endif
                    </p>
                </div>

                <span class="archive-record-count">
                    {{ $records->total() }}
                    {{ $records->total() === 1 ? 'record' : 'records' }}
                </span>
            </header>

            <!-- EMPTY STATE -->

            @if($records->isEmpty())

                <div class="archive-empty">
                    <span class="archive-empty-icon" aria-hidden="true">
                        <i class="bi bi-archive"></i>
                    </span>

                    @if($records->total())

                        <h3>No records on this page</h3>

                        <p>
                            Return to the first page to see archived records.
                        </p>

                        <a
                            class="btn btn-outline-primary"
                            href="{{ $records->appends(['section' => $section])->url(1) }}"
                        >
                            Back to first page
                        </a>

                    @else

                        <h3>No archived {{ strtolower($sectionTitle) }}</h3>

                        <p>
                            Archived records will appear here when available.
                        </p>

                    @endif
                </div>

            @else

                <!-- ARCHIVE TABLE -->

                <div class="table-responsive archive-table-wrap">

                    <table class="table align-middle archive-table">

                        <thead>
                            <tr>
                                <th scope="col">Record</th>
                                <th scope="col">Archived on</th>
                                <th scope="col">Six-month mark</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($records as $record)

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

                                    <!-- RECORD INFORMATION -->

                                    <th scope="row" class="archive-record-cell">

                                        <div class="archive-record-info">
                                            <span class="archive-record-name">
                                                {{ $label }}
                                            </span>

                                            @if($detail)
                                                <small class="archive-record-detail">
                                                    {{ $detail }}
                                                </small>
                                            @endif
                                        </div>

                                    </th>

                                    <!-- ARCHIVED DATE -->

                                    <td class="archive-date">
                                        {{ $record->deleted_at->format('M j, Y') }}
                                    </td>

                                    <!-- SIX-MONTH MARK -->

                                    <td class="archive-date archive-retention">
                                        {{ $record->deleted_at
                                            ->copy()
                                            ->addMonthsNoOverflow(6)
                                            ->format('M j, Y') }}
                                    </td>

                                    <!-- ORIGINAL ACTIONS -->

                                    <td>

                                        <div class="archive-row-actions">

                                            <form
                                                method="POST"
                                                action="{{ route('archive.restore', [
                                                    $section,
                                                    $record->getKey()
                                                ]) }}"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-primary"
                                                    aria-label="Restore {{ $label }}"
                                                >
                                                    <i
                                                        class="bi bi-arrow-counterclockwise"
                                                        aria-hidden="true"
                                                    ></i>

                                                    Restore
                                                </button>
                                            </form>

                                            @if(
                                                $section === 'members' &&
                                                auth()->user()->hasRole(
                                                    \App\Support\Rbac::SUPERADMIN
                                                )
                                            )

                                                <a
                                                    href="{{ route(
                                                        'archive.members.confirm-purge',
                                                        $record->getKey()
                                                    ) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    aria-label="Review permanent deletion for {{ $label }}"
                                                >
                                                    <i
                                                        class="bi bi-trash3"
                                                        aria-hidden="true"
                                                    ></i>

                                                    Delete permanently
                                                </a>

                                            @endif

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>
                </div>

                <!-- PAGINATION -->

                @if($records->hasPages())
                    <div class="archive-pagination">

                        {{ $records
                            ->appends(['section' => $section])
                            ->links('pagination::bootstrap-5') }}

                    </div>
                @endif

            @endif

        </section>

    @endif

</section>

@endsection
