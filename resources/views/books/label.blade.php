@extends('layouts.library')
@section('title', 'Book QR label')
@push('styles')
    <style>
        .book-label { width: 72mm; max-width: 100%; overflow-wrap: anywhere; }
        .book-label svg { display: block; width: 35mm; height: 35mm; margin: 0 auto; }
        @media print {
            @page { margin: 12mm; }
            body { background: white !important; }
            main.container { padding: 0 !important; }
            .book-label { break-inside: avoid; }
        }
    </style>
@endpush
@section('content')
    @can('books.qr')
        <div class="d-flex gap-2 mb-3 d-print-none">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print label</button>
            <a class="btn btn-outline-secondary" href="{{ route('books.png', $book) }}" download>Download PNG</a>
        </div>
        <article class="book-label border rounded bg-white p-3 text-center">
            <div aria-label="QR code for {{ $book->book_qr_value }}" role="img">{!! $qrSvg !!}</div>
            <h1 class="h6 mt-2 mb-1">{{ $book->book_title }}</h1>
            <div class="font-monospace">{{ $book->book_qr_value }}</div>
            <div class="small text-body-secondary">Copy #{{ $book->getKey() }}</div>
        </article>
    @endcan
@endsection
