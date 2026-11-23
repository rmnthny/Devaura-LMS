@extends('layouts.library')
@section('title', 'Member QR card')
@push('styles')
    <style>
        .qr-pass { max-width: 24rem; }
        .qr-pass-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
        .qr-frame { background: #fff; border: 1px solid #e9ecef; border-radius: 1rem; padding: 1rem; display: inline-block; }
        @media print {
            body { background: #fff !important; }
            .qr-pass { box-shadow: none !important; border: 1px solid #dee2e6 !important; margin: 0 auto; }
        }
    </style>
@endpush
@section('content')
@php($department = $member->user?->personnel?->department?->department_name)
@php($idLabel = $member->member_role === \App\Models\LibraryMember::ROLE_FACULTY ? 'DepEd Employee No.' : 'Student LRN')
<div class="qr-pass mx-auto">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="qr-pass-header text-white px-4 py-3 d-flex justify-content-between align-items-center">
            <div>
                <div class="small text-uppercase opacity-75 fw-semibold" style="letter-spacing: 0.06em;">UBNHS Library</div>
                <div class="fw-bold">Digital Member Pass</div>
            </div>
            <span class="badge rounded-pill text-bg-light text-primary">{{ $member->user?->account_status ?? 'Active' }}</span>
        </div>
        <div class="card-body p-4 text-center bg-white">
            <h1 class="h4 fw-bold mb-1">{{ $member->fullName() }}</h1>
            <p class="text-body-secondary small mb-3">
                {{ $member->member_role }}@if ($department) · {{ $department }} @endif
            </p>
            <div class="qr-frame shadow-sm mb-3">
                <img src="data:image/png;base64,{{ $png }}" class="img-fluid d-block mx-auto" style="width: 190px; height: 190px; image-rendering: pixelated;" alt="Member QR code">
            </div>
            <div class="d-flex justify-content-center align-items-center gap-2 mb-1">
                <code class="fs-6 fw-semibold text-dark" id="member-qr-code">{{ $code }}</code>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 d-print-none" id="copy-qr-code" title="Copy code">Copy</button>
            </div>
            <p class="small text-body-secondary mb-0">Scan this QR code at the library kiosk or circulation desk.</p>
        </div>
        <div class="card-footer bg-body-tertiary px-4 py-3 border-top">
            <dl class="row small mb-0 gy-1">
                <dt class="col-5 text-body-secondary fw-normal">{{ $idLabel }}</dt>
                <dd class="col-7 text-end fw-semibold mb-0 font-monospace">{{ $member->reference_number }}</dd>
                <dt class="col-5 text-body-secondary fw-normal">Email</dt>
                <dd class="col-7 text-end mb-0 text-truncate" title="{{ $member->user?->email }}">{{ $member->user?->email }}</dd>
            </dl>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-center mt-3 d-print-none">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1" aria-hidden="true"></i>Print QR card
        </button>
        <a class="btn btn-outline-secondary" href="data:image/png;base64,{{ $png }}" download="{{ $code }}.png">
            <i class="bi bi-download me-1" aria-hidden="true"></i>Download PNG
        </a>
    </div>
</div>
<script>
    (() => {
        const btn = document.getElementById('copy-qr-code');
        const code = document.getElementById('member-qr-code')?.textContent?.trim();
        if (!btn || !code || !navigator.clipboard) return;
        btn.addEventListener('click', async () => {
            await navigator.clipboard.writeText(code);
            btn.textContent = 'Copied';
            setTimeout(() => { btn.textContent = 'Copy'; }, 1500);
        });
    })();
</script>
@endsection
