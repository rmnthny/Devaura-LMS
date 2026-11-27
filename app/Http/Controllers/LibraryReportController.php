<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Services\LibraryReports;
use App\Services\LibraryReportWorkbook;
use App\Support\Rbac;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class LibraryReportController extends Controller
{
    private const EXPORT_LIMIT = 1000;

    public function index(Request $request, LibraryReports $reports)
    {
        $today = now(config('attendance.timezone'))->toImmutable();
        $type = $request->input('type', 'attendance');
        $inventoryAllowed = $request->user()->hasRole(Rbac::SUPERADMIN) && $request->user()->can('inventory.audit');
        abort_if($type === 'inventory' && ! $inventoryAllowed, 403);

        $filters = $this->filters($request, $today, [
            'type' => ['sometimes', Rule::in(['attendance', 'circulation', 'inventory'])],
            'role' => [Rule::excludeIf($type !== 'attendance'), 'nullable', Rule::in(array_keys(LibraryReports::ATTENDANCE_ROLES))],
            'circulation_activity' => [Rule::excludeIf($type !== 'circulation'), 'sometimes', Rule::in(array_keys(LibraryReports::CIRCULATION_ACTIVITIES))],
            'book_title' => [Rule::excludeIf($type !== 'inventory'), 'nullable', 'string', 'max:255'],
            'genre_id' => [Rule::excludeIf($type !== 'inventory'), 'nullable', 'integer', 'exists:genre,genre_id'],
            'published_on' => [Rule::excludeIf($type !== 'inventory'), 'nullable', 'date_format:Y-m-d'],
            'format' => ['sometimes', Rule::in(['pdf', 'xlsx'])],
        ]);
        $filters['type'] ??= 'attendance';
        if ($type === 'circulation') {
            $filters['circulation_activity'] ??= 'checkout';
        }
        $format = $filters['format'] ?? null;
        unset($filters['format']);
        $types = [
            'attendance' => LibraryReports::TYPES['attendance'],
            'circulation' => LibraryReports::TYPES['circulation'],
        ];
        if ($inventoryAllowed) {
            $types['inventory'] = LibraryReports::TYPES['inventory'];
        }

        return $this->respond($reports, $filters['type'], $filters, $today->toDateString(), $format, $types);
    }

    public function audit(Request $request, LibraryReports $reports)
    {
        $today = now(config('attendance.timezone'))->toImmutable();
        $filters = $this->filters($request, $today, [
            'area' => ['nullable', 'string', 'max:80'],
            'event' => ['nullable', 'string', 'max:80'],
            'action' => ['nullable', 'string', 'max:255'],
            'transaction' => ['nullable', 'string', 'max:30'],
            'format' => ['sometimes', Rule::in(['pdf', 'xlsx'])],
        ]);
        $format = $filters['format'] ?? null;
        unset($filters['format']);

        return $this->respond($reports, 'activity', $filters, $today->toDateString(), $format, []);
    }

    private function filters(Request $request, CarbonImmutable $today, array $rules): array
    {
        $request->mergeIfMissing([
            'from' => $today->subDays(29)->toDateString(),
            'through' => $today->toDateString(),
        ]);

        return $request->validate($rules + [
            'from' => ['required', 'date_format:Y-m-d'],
            'through' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }

    private function respond(LibraryReports $reports, string $type, array $filters, string $today, ?string $format, array $types)
    {
        [$query, $headings, $map] = $reports->build($type, $filters, $today);
        $title = LibraryReports::TYPES[$type];

        if ($format !== null) {
            $records = $query->limit(self::EXPORT_LIMIT + 1)->get();
            if ($records->count() > self::EXPORT_LIMIT) {
                throw ValidationException::withMessages(['through' => 'Narrow the filters to export at most 1,000 records.']);
            }

            $rows = $records->map($map)->all();
            $summary = $reports->summary($type, $filters, count($rows));
            $filename = $type === 'activity' ? 'audit-trail' : $type;
            $title = $type === 'activity' ? 'Audit Trail' : $title.' Report';
            $headings = array_map(fn (string $label) => str_replace(' (Manila)', '', $label), $headings);
            $summary = array_combine(
                array_map(fn (string $label) => str_replace(' (Manila)', '', $label), array_keys($summary)),
                array_values($summary),
            );
            if ($format === 'pdf') {
                $pdf = Pdf::loadView('pdf.library-report', compact('title', 'type', 'headings', 'rows', 'summary'))
                    ->setOption('isFontSubsettingEnabled', true)
                    ->setPaper('a4', count($headings) > 4 ? 'landscape' : 'portrait');
                $pdf->render();
                $renderer = $pdf->getDomPDF();
                $canvas = $renderer->getCanvas();
                $canvas->page_text(
                    40,
                    $canvas->get_height() - 28,
                    'UBNHS Library Services  |  Page {PAGE_NUM} of {PAGE_COUNT}',
                    $renderer->getFontMetrics()->getFont('DejaVu Sans', 'normal'),
                    8,
                    [0.28, 0.34, 0.40],
                );

                return $pdf->download($filename.'.pdf');
            }

            return Excel::download(new LibraryReportWorkbook(LibraryReports::TYPES[$type], $summary, $headings, $rows), $filename.'.xlsx');
        }

        $page = $query->paginate(25)->appends($filters);
        $rows = $page->getCollection()->map($map);
        $summary = $reports->summary($type, $filters, $page->total());
        $activity = $type === 'activity';

        $genres = $type === 'inventory' ? Genre::orderBy('genre_name')->pluck('genre_name', 'genre_id') : [];

        $auditFilterOptions = $activity ? $reports->auditFilterOptions($filters, $today) : [];

        $operationalFilterOptions = $activity ? [] : $reports->operationalFilterOptions($type, $types, collect($genres)->all());

        return view('reports.index', compact(
            'title', 'type', 'types', 'headings', 'rows', 'summary', 'page', 'filters',
            'activity', 'genres', 'auditFilterOptions', 'operationalFilterOptions',
        ));
    }
}
