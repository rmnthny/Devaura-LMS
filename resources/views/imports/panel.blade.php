<section class="border rounded-3 p-3 p-md-4 my-3 bg-white" aria-labelledby="import-{{ $kind }}-title">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h2 class="h5 mb-0" id="import-{{ $kind }}-title">Import {{ $kind }}</h2>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a class="btn btn-sm btn-outline-primary" href="{{ route($kind.'.import-template', ['format' => 'xlsx']) }}">Download Excel template</a>
            <a class="small" href="{{ route($kind.'.import-template') }}">CSV template</a>
        </div>
    </div>
    @php
        $fileErrors = $errors->getBag('libraryImport')->get('file');
    @endphp
    <form id="import-{{ $kind }}-form" class="mt-3" method="post" action="{{ route($kind.'.import') }}" enctype="multipart/form-data">
        @csrf
        <label class="form-label" for="import-{{ $kind }}-file">{{ $kind === 'members' ? 'Member roster file' : 'Book inventory file' }}</label>
        <div class="row g-2">
            <div class="col-sm-8 col-lg-6">
                <input class="form-control @if ($fileErrors) is-invalid @endif" type="file" id="import-{{ $kind }}-file"
                    name="file" accept=".csv,.xls,.xlsx" aria-describedby="import-{{ $kind }}-limits{{ $fileErrors ? ' import-'.$kind.'-errors' : '' }}"
                    @if ($fileErrors) aria-invalid="true" @endif required>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-primary" type="submit">Import {{ $kind }}</button>
            </div>
        </div>
        <p class="form-text mb-0" id="import-{{ $kind }}-limits">.csv, .xls, or .xlsx. Up to 2,000 rows and 5 MB. Excel files must have one tab.</p>
        @if ($fileErrors)
            <ul class="small text-danger mb-0 mt-2" id="import-{{ $kind }}-errors" role="alert">
                @foreach ($fileErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </form>
    @if ($batchId = session('library-imports.'.$kind))
        @if ($batch = \Illuminate\Support\Facades\Bus::findBatch($batchId))
            @php
                $counts = \App\Services\LibraryImports::counts($batch);
                $processed = $counts['imported'] + $counts['skipped'];
                $totalRows = $batch->options['import_rows'] ?? null;
                $failures = count($batch->failedJobIds);
                $pending = ! $batch->finished() && ! $batch->cancelled() && $batch->pendingJobs > $failures;
            @endphp
            <div class="alert {{ $failures || $batch->cancelled() ? 'alert-warning' : 'alert-secondary' }} mt-3 mb-0" id="import-{{ $kind }}-status" role="status"
                data-pending="{{ $pending ? 'true' : 'false' }}">
                @if ($batch->cancelled())
                    Import stopped.
                @elseif ($batch->finished())
                    Import completed.
                @elseif (! $pending && $failures)
                    Import finished with errors. An administrator can retry the failed jobs.
                @elseif (\App\Services\LibraryImports::started($batch))
                    Import in progress.
                @else
                    Import queued. This page checks the status automatically.
                    @if ($batch->createdAt->diffInSeconds(now()) >= 15)
                        Processing is taking longer to start. Your upload is already saved.
                    @endif
                @endif
                @if ($totalRows !== null && ($pending || $processed < $totalRows))
                    {{ $processed }} of {{ $totalRows }} rows processed.
                @endif
                @if (! $pending || $processed > 0)
                    Imported {{ $counts['imported'] }} rows. Skipped {{ $counts['skipped'] }}.
                    @if ($kind === 'members' && $counts['skipped'] > 0)
                        Skipped rows use an email or reference number already assigned to an active or archived account.
                        <a href="{{ route('archive.index', ['section' => 'members']) }}">Review archived members</a>.
                    @endif
                    @if ($kind === 'books') Created {{ $counts['copies'] }} copies. @endif
                @endif
                <a href="{{ request()->fullUrl() }}">Refresh {{ $kind === 'members' ? 'member directory' : 'book list' }}</a>
            </div>
        @endif
    @endif
</section>
<script type="text/javascript">
    (() => {
        const form = document.getElementById(@js('import-'.$kind.'-form'));
        const input = form.elements.namedItem('file');
        const button = form.querySelector('button[type="submit"]');
        const label = button.textContent;
        const maxBytes = @js(\App\Services\ImportRows::MAX_FILE_KB * 1024);

        input.addEventListener('change', () => {
            const tooLarge = input.files[0]?.size > maxBytes;
            input.setCustomValidity(tooLarge ? 'Choose a file that is 5 MB or smaller.' : '');
            if (tooLarge) input.reportValidity();
        });
        form.addEventListener('submit', () => {
            button.disabled = true;
            button.textContent = 'Uploading…';
        });
        window.addEventListener('pageshow', () => {
            button.disabled = false;
            button.textContent = label;
        });

        const statusId = @js('import-'.$kind.'-status');
        let status = document.getElementById(statusId);
        if (status?.dataset.pending !== 'true') return;

        const refreshStatus = async () => {
            try {
                const response = await fetch(window.location.href, { cache: 'no-store' });
                if (!response.ok || response.redirected) return;
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const updated = page.getElementById(statusId);
                if (!updated) return;
                if (updated.textContent !== status.textContent || updated.dataset.pending !== status.dataset.pending) {
                    status.replaceWith(updated);
                    status = updated;
                }
                if (updated.dataset.pending !== 'true') return;
            } catch {
                // Keep manual refresh available if the connection drops.
            }
            window.setTimeout(refreshStatus, 5000);
        };
        window.setTimeout(refreshStatus, 5000);
    })();
</script>
