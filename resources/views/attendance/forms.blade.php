<div class="row g-3">
    <div class="col-md-6">
        <form method="post" action="{{ $actionUrl }}" class="card card-body h-100" autocomplete="off">
            @csrf
            <input type="hidden" name="type" value="account">
            <h2 class="h5">{{ request()->routeIs('kiosk.*') ? 'Library member' : 'Registered account' }}</h2>
            <label class="form-label" for="attendance-code">QR code value or reference ID</label>
            <input id="attendance-code" class="form-control mb-3" name="identifier" maxlength="50" required>
            <div class="d-flex gap-2 mt-auto">
                <button class="btn btn-primary" name="action" value="in">Time In</button>
                <button class="btn btn-outline-primary" name="action" value="out">Time Out</button>
            </div>
        </form>
    </div>
    <div class="col-md-6">
        <section class="card card-body h-100">
            <h2 class="h5">Walk-in visitor</h2>
            <details>
                <summary>Time In</summary>
                <form method="post" action="{{ $actionUrl }}" class="vstack gap-2 mt-2" autocomplete="off">
                    @csrf
                    <input type="hidden" name="type" value="visitor">
                    <input type="hidden" name="action" value="in">
                    <label>Last name <input name="last_name" class="form-control" maxlength="50" required></label>
                    <label>First name <input name="first_name" class="form-control" maxlength="50" required></label>
                    <label>Middle initial <input name="middle_initial" class="form-control" maxlength="5"></label>
                    <label>Email <input name="email" type="email" class="form-control" maxlength="254" required></label>
                    <label>Purpose of visit <input name="visit_purpose" class="form-control" maxlength="255" required></label>
                    <div><button class="btn btn-primary">Time In</button></div>
                </form>
            </details>
            <details class="mt-2">
                <summary>Time Out</summary>
                <form method="post" action="{{ $actionUrl }}" class="mt-2" autocomplete="off">
                    @csrf
                    <input type="hidden" name="type" value="visitor">
                    <input type="hidden" name="action" value="out">
                    <label for="visitor-checkout-email" class="form-label">Email used to time in</label>
                    <input id="visitor-checkout-email" name="email" type="email" class="form-control mb-2" maxlength="254" required>
                    <button class="btn btn-outline-primary">Time Out</button>
                </form>
            </details>
        </section>
    </div>
</div>
