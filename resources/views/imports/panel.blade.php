@php
    $fileErrors = $errors
        ->getBag('libraryImport')
        ->get('file');

    $importTitle = 'Import ' . $kind;

    $fileLabel = $kind === 'members'
        ? 'Member roster file'
        : 'Book inventory file';

    $description = $kind === 'members'
        ? 'Add multiple members using a spreadsheet.'
        : 'Add multiple book records using a spreadsheet.';

    $maxBytes =
        \App\Services\ImportRows::MAX_FILE_KB * 1024;
@endphp

<section
    class="ws-import-panel"
    aria-labelledby="import-{{ $kind }}-title"
>

    <!-- Import header -->

    <div class="ws-import-header">

        <div class="ws-import-heading">

            <span class="ws-import-heading-icon">
                <i class="bi bi-cloud-arrow-up"
                   aria-hidden="true"></i>
            </span>

            <div>

                <h2 id="import-{{ $kind }}-title">
                    {{ $importTitle }}
                </h2>

                <p>{{ $description }}</p>

            </div>

        </div>

        <!-- Existing template downloads -->

        <div class="ws-import-templates">

            <a
                class="btn btn-sm btn-outline-secondary"
                href="{{ route($kind . '.import-template', ['format' => 'xlsx']) }}"
                title="Download Excel template"
            >
                <i class="bi bi-file-earmark-excel"
                   aria-hidden="true"></i>

                Excel template

                <i class="bi bi-download"
                   aria-hidden="true"></i>
            </a>

            <a
                class="btn btn-sm btn-outline-secondary"
                href="{{ route($kind . '.import-template') }}"
                title="Download CSV template"
            >
                <i class="bi bi-file-earmark-text"
                   aria-hidden="true"></i>

                CSV template

                <i class="bi bi-download"
                   aria-hidden="true"></i>
            </a>

        </div>

    </div>

    <!-- Existing Laravel upload form -->

    <form
        id="import-{{ $kind }}-form"
        method="POST"
        action="{{ route($kind . '.import') }}"
        enctype="multipart/form-data"
    >

        @csrf

        <x-file-upload
            :id="'import-' . $kind . '-file'"
            name="file"
            variant="import"
            :label="$fileLabel"
            accept=".csv,.xls,.xlsx"
            :max-bytes="$maxBytes"
            hint="CSV, XLS, XLSX · Max. 5 MB"
            :required="true"
            :invalid="!empty($fileErrors)"
            :describedby="'import-' . $kind . '-limits' . ($fileErrors ? ' import-' . $kind . '-errors' : '')"
            :submit-label="$importTitle"
        />

        <p
            class="ws-import-limits"
            id="import-{{ $kind }}-limits"
        >
            <i class="bi bi-info-circle"
               aria-hidden="true"></i>

            <span>
                Up to 2,000 rows.
                Excel files must contain one worksheet.
            </span>
        </p>

        <!-- Original backend validation errors -->

        @if($fileErrors)

            <ul
                class="ws-import-errors"
                id="import-{{ $kind }}-errors"
                role="alert"
            >
                @foreach($fileErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        @endif

    </form>

    <!-- Existing backend import batch status -->

    @if($batchId = session('library-imports.' . $kind))

        @if($batch = \Illuminate\Support\Facades\Bus::findBatch($batchId))

            @php
                $counts = \App\Services\LibraryImports::counts($batch);

                $processed =
                    $counts['imported'] + $counts['skipped'];

                $totalRows =
                    $batch->options['import_rows'] ?? null;

                $failures = count($batch->failedJobIds);

                $pending =
                    !$batch->finished() &&
                    !$batch->cancelled() &&
                    $batch->pendingJobs > $failures;
            @endphp

            <div
                class="ws-import-status alert {{ $failures || $batch->cancelled() ? 'alert-warning' : 'alert-secondary' }}"
                id="import-{{ $kind }}-status"
                role="status"
                data-pending="{{ $pending ? 'true' : 'false' }}"
            >

                @if($batch->cancelled())

                    Import stopped.

                @elseif($batch->finished())

                    Import completed.

                @elseif(!$pending && $failures)

                    Import finished with errors.
                    An administrator can retry the failed jobs.

                @elseif(\App\Services\LibraryImports::started($batch))

                    Import in progress.

                @else

                    Import queued.
                    This page checks the status automatically.

                    @if($batch->createdAt->diffInSeconds(now()) >= 15)

                        Processing is taking longer to start.
                        Your upload is already saved.

                    @endif

                @endif

                @if($totalRows !== null && ($pending || $processed < $totalRows))

                    {{ $processed }} of {{ $totalRows }} rows processed.

                @endif

                @if(!$pending || $processed > 0)

                    Imported {{ $counts['imported'] }} rows.
                    Skipped {{ $counts['skipped'] }}.

                    @if($kind === 'members' && $counts['skipped'] > 0)

                        Skipped rows use an email or reference
                        number already assigned to an active
                        or archived account.

                        <a href="{{ route('archive.index', ['section' => 'members']) }}">
                            Review archived members
                        </a>.

                    @endif

                    @if($kind === 'books')

                        Created {{ $counts['copies'] }} copies.

                    @endif

                @endif

                <a href="{{ request()->fullUrl() }}">
                    Refresh {{ $kind === 'members'
                        ? 'member directory'
                        : 'book list' }}
                </a>

            </div>

        @endif

    @endif

</section>

<!-- Preserve actual backend status polling -->

<script type="text/javascript">
(() => {
    const statusId = @js('import-' . $kind . '-status');

    let status = document.getElementById(statusId);

    if (status?.dataset.pending !== "true") return;

    const refreshStatus = async () => {
        try {
            const response = await fetch(
                window.location.href,
                { cache: "no-store" }
            );

            if (!response.ok || response.redirected) return;

            const page = new DOMParser().parseFromString(
                await response.text(),
                "text/html"
            );

            const updated = page.getElementById(statusId);

            if (!updated) return;

            if (
                updated.textContent !== status.textContent ||
                updated.dataset.pending !== status.dataset.pending
            ) {
                status.replaceWith(updated);
                status = updated;
            }

            if (updated.dataset.pending !== "true") return;

        } catch {
            // Manual refresh remains available
            // if the connection drops.
        }

        window.setTimeout(refreshStatus, 5000);
    };

    window.setTimeout(refreshStatus, 5000);
})();
</script>