<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Book;
use App\Models\BorrowTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now(config('attendance.timezone'))->toImmutable()->startOfDay();
        $date = $today->toDateString();
        $loans = BorrowTransaction::where('borrower_id', $user->getKey());
        $personal = [
            'My open loans' => (clone $loans)->open()->count(),
            'My overdue loans' => (clone $loans)->overdueOn($date)->count(),
        ];
        $operational = [];
        $trend = [];

        if ($user->can('reports.generate')) {
            $trend = $this->trends($today);
            $operational = [
                'Available copies' => Book::where('book_status', 'Available')->count(),
                'Open loans' => BorrowTransaction::open()->count(),
                'Overdue loans' => BorrowTransaction::overdueOn($date)->count(),
                'Visits today' => $trend[array_key_last($trend)]['visits'],
            ];
        }

        return view('reports.dashboard', compact('today', 'personal', 'operational', 'trend'));
    }

    public function history(Request $request): View
    {
        $loans = BorrowTransaction::with('book')->where('borrower_id', $request->user()->getKey())
            ->latest('borrow_date')->orderByDesc('transaction_id')->paginate(20);
        $today = now(config('attendance.timezone'))->toDateString();

        return view('reports.history', compact('loans', 'today'));
    }

    private function trends(CarbonImmutable $today): array
    {
        $first = $today->subDays(6);
        $visits = AttendanceLog::where('time_in_at', '>=', $first->utc())
            ->where('time_in_at', '<', $today->addDay()->utc())
            ->selectRaw('DATE(DATE_ADD(time_in_at, INTERVAL ? MINUTE)) as library_date, count(*) as total', [$today->offsetMinutes])
            ->groupBy('library_date')->pluck('total', 'library_date');
        $dates = [$first->toDateString(), $today->toDateString()];
        $checkouts = BorrowTransaction::whereBetween('borrow_date', $dates)
            ->selectRaw('borrow_date, count(*) as total')->groupBy('borrow_date')->pluck('total', 'borrow_date');
        $returns = BorrowTransaction::where('transaction_status', BorrowTransaction::RETURNED)
            ->whereBetween('return_date', $dates)
            ->selectRaw('return_date, count(*) as total')->groupBy('return_date')->pluck('total', 'return_date');

        $trend = [];
        for ($day = $first; $day <= $today; $day = $day->addDay()) {
            $date = $day->toDateString();
            $trend[] = [
                'date' => $date,
                'label' => $day->format('M j'),
                'visits' => (int) ($visits[$date] ?? 0),
                'checkouts' => (int) ($checkouts[$date] ?? 0),
                'returns' => (int) ($returns[$date] ?? 0),
            ];
        }

        return $trend;
    }
}
