@extends('layouts.library')
@section('title', 'Verify email')
@section('content')
    <h1 class="h3">Verify your email</h1>
    <form method="post" action="{{ route('api.v1.otp.verify') }}" class="mb-3" style="max-width: 24rem">
        @csrf
        <label class="form-label" for="code">Six-digit code</label>
        <input class="form-control mb-3" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
        <button class="btn btn-primary">Verify</button>
    </form>
    @php($resendWait = max(0, (int) session('otp_resend_available_at', 0) - now()->timestamp))
    <form method="post" action="{{ route('api.v1.otp.resend') }}">
        @csrf
        <button class="btn btn-outline-secondary" id="otp-resend-btn" data-wait="{{ $resendWait }}" @disabled($resendWait > 0)>
            {{ $resendWait > 0 ? "Resend code ({$resendWait}s)" : 'Resend code' }}
        </button>
    </form>
    <script>
        (() => {
            const btn = document.getElementById('otp-resend-btn');
            let remaining = parseInt(btn.dataset.wait || '0', 10);
            if (remaining <= 0) return;

            const tick = () => {
                if (remaining <= 0) {
                    btn.disabled = false;
                    btn.textContent = 'Resend code';
                    return;
                }
                btn.disabled = true;
                btn.textContent = `Resend code (${remaining}s)`;
                remaining -= 1;
                setTimeout(tick, 1000);
            };
            tick();
        })();
    </script>
@endsection
