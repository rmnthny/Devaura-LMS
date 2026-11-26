
@extends('layouts.library')

@section('title', 'Member QR card')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/member-qr.css') }}">
@endpush

@section('content')

@php
    $department = $member->user?->personnel?->department?->department_name;

    $idLabel = $member->member_role === \App\Models\LibraryMember::ROLE_FACULTY
        ? 'DepEd Employee No.'
        : 'Student LRN';

    $status = $member->user?->account_status ?? 'Active';

    $statusClass = match ($status) {
        'Active' => 'is-active',
        'Pending' => 'is-pending',
        'Inactive', 'Locked' => 'is-inactive',
        default => 'is-neutral'
    };
@endphp

<section class="member-pass-page">

    <!-- PAGE HEADER -->

    <header class="member-pass-page-header d-print-none">
        <div class="member-pass-heading">
            <span class="member-pass-page-icon" aria-hidden="true">
                <i class="bi bi-qr-code"></i>
            </span>

            <div>
                <h1>My QR Card</h1>
                <p>
                    Your digital library identification pass.
                </p>
            </div>
        </div>
    </header>

    <!-- ACTUAL DIGITAL MEMBER PASS -->

    <article class="member-pass" aria-label="UBNHS digital library member pass">

        <!-- SCHOOL BRAND HEADER -->

        <header class="member-pass-brand">
            <div class="member-pass-school">
                <img
                    class="member-pass-logo"
                    src="{{ asset('images/school-logo.png') }}"
                    alt="UBNHS school seal"
                >

                <div class="member-pass-school-name">
                    <strong>UPPER BICUTAN NATIONAL HIGH SCHOOL</strong>
                    <span>Library Management System</span>
                </div>
            </div>

            <span class="member-pass-document-label">
                DIGITAL MEMBER PASS
            </span>
        </header>

        <!-- IDENTITY -->

        <div class="member-pass-content">

            <div class="member-pass-identity">
                <h2>{{ $member->fullName() }}</h2>

                <p>
                    {{ $member->member_role }}

                    @if($department)
                        <span aria-hidden="true">·</span>
                        {{ $department }}
                    @endif
                </p>
            </div>

            <!-- REAL QR CODE -->

            <div class="member-pass-qr-frame">

                <img
                    class="member-pass-qr-image"
                    src="data:image/png;base64,{{ $png }}"
                    alt="Library member QR code for {{ $member->fullName() }}"
                    width="196"
                    height="196"
                >

            </div>

            <!-- COPYABLE CODE -->

            <div class="member-pass-code-area">
                <span class="member-pass-code-label">
                    Member QR code
                </span>

                <div class="member-pass-code-line">

                    <code id="member-qr-code">{{ $code }}</code>

                    <button
                        type="button"
                        class="member-pass-copy-btn d-print-none"
                        id="copy-qr-code"
                        aria-label="Copy member QR code"
                    >
                        <i class="bi bi-copy" aria-hidden="true"></i>
                        <span>Copy</span>
                    </button>

                </div>

                <span
                    class="member-pass-copy-feedback d-print-none"
                    id="copy-qr-feedback"
                    role="status"
                    aria-live="polite"
                ></span>
            </div>

            <p class="member-pass-scan-note">
                Present or scan this code at the library kiosk
                or circulation desk.
            </p>

        </div>

        <!-- MEMBER INFORMATION -->

        <footer class="member-pass-details">

            <div class="member-pass-detail">
                <span>{{ $idLabel }}</span>
                <strong>{{ $member->reference_number }}</strong>
            </div>

            <div class="member-pass-detail">
                <span>Account status</span>
                <strong class="member-pass-status {{ $statusClass }}">
                    {{ $status }}
                </strong>
            </div>

            <div class="member-pass-detail member-pass-email">
                <span>Email address</span>
                <strong>{{ $member->user?->email ?? '—' }}</strong>
            </div>

        </footer>

    </article>

    <!-- ACTIONS -->

    <div class="member-pass-actions d-print-none">

        <button
            type="button"
            class="btn btn-primary"
            id="print-member-pass"
        >
            <i class="bi bi-printer" aria-hidden="true"></i>
            Print QR card
        </button>

        <a
            class="btn btn-outline-secondary"
            href="data:image/png;base64,{{ $png }}"
            download="{{ $code }}.png"
        >
            <i class="bi bi-download" aria-hidden="true"></i>
            Download PNG
        </a>

    </div>

    <p class="member-pass-help d-print-none">
        Download PNG saves the QR image. Print QR card prints
        the complete digital member pass.
    </p>

</section>

@endsection

@push('scripts')
<script>
(() => {
    "use strict";

    const copyButton = document.getElementById("copy-qr-code");
    const codeElement = document.getElementById("member-qr-code");
    const copyFeedback = document.getElementById("copy-qr-feedback");
    const printButton = document.getElementById("print-member-pass");

    printButton?.addEventListener("click", () => {
        window.print();
    });

    if (!copyButton || !codeElement) return;

    copyButton.addEventListener("click", async () => {
        const code = codeElement.textContent.trim();

        if (!code) return;

        try {
            if (!navigator.clipboard?.writeText) {
                throw new Error("Clipboard is not available");
            }

            await navigator.clipboard.writeText(code);

            if (copyFeedback) {
                copyFeedback.textContent = "QR code copied.";
            }
        } catch (error) {
            if (copyFeedback) {
                copyFeedback.textContent =
                    "Unable to copy automatically. Select the code to copy it.";
            }
        }
    });
})();
</script>
@endpush
