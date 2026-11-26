<?php

namespace App\Http\Controllers;

use App\Services\LibraryReports;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class LibraryReportController extends Controller
{
    public function index(Request $request, LibraryReports $reports)
    {
        $activity = $request->routeIs('audit.index');
        $today = now(config('attendance.timezone'));
        $request->mergeIfMissing(['from' => $today->copy()->subDays(29)->toDateString(), 'through' => $today->toDateString()]);
        $filters = $request->validate([
            'type' => ['sometimes', Rule::in(['attendance', 'circulation', 'inventory'])],
            'from' => ['required', 'date_format:Y-m-d'],
            'through' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'area' => ['nullable', 'string', 'max:80'],
            'format' => ['sometimes', Rule::in(['pdf', 'xlsx'])],
        ]);
        $type = $activity ? 'activity' : ($filters['type'] ?? 'attendance');
        abort_if($type === 'inventory' && ! $request->user()->hasRole('superadmin'), 403);
        [$query, $headings, $map] = $reports->build($type, $filters);
        $title = LibraryReports::TYPES[$type];

        if (isset($filters['format'])) {
            $rows = $query->limit(1001)->get();
            if ($rows->count() > 1000) {
                throw ValidationException::withMessages(['through' => 'Narrow the date range to export at most 1,000 rows.']);
            }
            $rows = $rows->map($map)->all();
            if ($filters['format'] === 'pdf') {
                return Pdf::loadView('pdf.library-report', compact('title', 'headings', 'rows', 'filters'))
                    ->setPaper('a4', 'landscape')->download($type.'.pdf');
            }

            return Excel::download(new class($headings, $rows) extends StringValueBinder implements FromArray, WithHeadings, WithCustomValueBinder {
                public function __construct(private array $columns, private array $rows) {}
                public function array(): array { return $this->rows; }
                public function headings(): array { return $this->columns; }
            }, $type.'.xlsx');
        }

        $page = $query->paginate(25)->withQueryString();
        $rows = $page->getCollection()->map($map);

        return view('reports.index', compact('title', 'type', 'headings', 'rows', 'page', 'filters', 'activity'));
    }
}
