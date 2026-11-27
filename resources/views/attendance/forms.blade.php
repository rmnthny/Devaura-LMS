
<div class="row g-3 attendance-form-grid">

    <!-- REGISTERED LIBRARY MEMBER -->
    <div class="col-md-6">
        <form
            method="post"
            action="{{ $actionUrl }}"
            class="card card-body h-100 attendance-member-form"
            autocomplete="off"
        >
            @csrf

            <input type="hidden" name="type" value="account">

            <h2 class="h5">
                {{ request()->routeIs('kiosk.*') ? 'Library member' : 'Registered account' }}
            </h2>

            <div class="attendance-member-field">
                <label
                    class="form-label"
                    for="attendance-code"
                >
                    QR code value or reference ID
                </label>

                <input
                    id="attendance-code"
                    type="text"
                    class="form-control"
                    name="identifier"
                    maxlength="50"
                    autocomplete="off"
                    placeholder="Scan QR code or enter reference ID"
                    required
                >
            </div>

            <div class="d-flex gap-2 mt-auto attendance-member-actions">
                <button
                    type="submit"
                    class="btn btn-primary"
                    name="action"
                    value="in"
                >
                    Time In
                </button>

                <button
                    type="submit"
                    class="btn btn-outline-primary"
                    name="action"
                    value="out"
                >
                    Time Out
                </button>
            </div>
        </form>
    </div>

    <!-- WALK-IN VISITOR -->
    <div class="col-md-6">
        <section class="card card-body h-100 attendance-visitor-card">

            <h2 class="h5">Walk-in visitor</h2>

            <!-- VISITOR TIME IN -->
            <details class="attendance-visitor-details">
                <summary>
                    <span>Time In</span>
                </summary>

                <form
                    method="post"
                    action="{{ $actionUrl }}"
                    class="attendance-visitor-form"
                    autocomplete="off"
                >
                    @csrf

                    <input type="hidden" name="type" value="visitor">
                    <input type="hidden" name="action" value="in">

                    <label for="visitor-last-name">
                        Last name
                        <input
                            id="visitor-last-name"
                            name="last_name"
                            class="form-control"
                            maxlength="50"
                            required
                        >
                    </label>

                    <label for="visitor-first-name">
                        First name
                        <input
                            id="visitor-first-name"
                            name="first_name"
                            class="form-control"
                            maxlength="50"
                            required
                        >
                    </label>

                    <label for="visitor-middle-initial">
                        Middle initial
                        <input
                            id="visitor-middle-initial"
                            name="middle_initial"
                            class="form-control"
                            maxlength="5"
                        >
                    </label>

                    <label for="visitor-checkin-email">
                        Email
                        <input
                            id="visitor-checkin-email"
                            type="email"
                            name="email"
                            class="form-control"
                            maxlength="254"
                            required
                        >
                    </label>

                    <label class="attendance-purpose-field"
                           for="visitor-purpose">
                        Purpose of visit
                        <input
                            id="visitor-purpose"
                            name="visit_purpose"
                            class="form-control"
                            maxlength="255"
                            required
                        >
                    </label>

                    <div class="attendance-visitor-submit">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Time In
                        </button>
                    </div>
                </form>
            </details>

            <!-- VISITOR TIME OUT -->
            <details class="attendance-visitor-details mt-2">
                <summary>
                    <span>Time Out</span>
                </summary>

                <form
                    method="post"
                    action="{{ $actionUrl }}"
                    class="attendance-visitor-form attendance-visitor-checkout"
                    autocomplete="off"
                >
                    @csrf

                    <input type="hidden" name="type" value="visitor">
                    <input type="hidden" name="action" value="out">

                    <label for="visitor-checkout-email">
                        Email used to time in
                        <input
                            id="visitor-checkout-email"
                            name="email"
                            type="email"
                            class="form-control"
                            maxlength="254"
                            required
                        >
                    </label>

                    <div class="attendance-visitor-submit">
                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >
                            Time Out
                        </button>
                    </div>
                </form>
            </details>

        </section>
    </div>

</div>
