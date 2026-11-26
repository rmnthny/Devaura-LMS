<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Book;
use App\Models\BorrowTransaction;
use Illuminate\Http\Request;

class LibraryDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = now(config('attendance.timezone'));
        $date = $now->toDateString();
        $operational = $user->can('reports.generate');
        $loans = BorrowTransaction::where('borrower_id', $user->getKey());

        $summary = [
            'My open loans' => (clone $loans)->open()->count(),
            'My overdue loans' => (clone $loans)->overdueOn($date)->count(),
        ];
        $trend = [];
        if ($operational) {
            $summary += [
                'Available copies' => Book::where('book_status', 'Available')->count(),
                'Open loans' => BorrowTransaction::open()->count(),
                'Overdue loans' => BorrowTransaction::overdueOn($date)->count(),
                'Visits today' => AttendanceLog::where('time_in_at', '>=', $now->copy()->startOfDay()->utc())
                    ->where('time_in_at', '<', $now->copy()->addDay()->startOfDay()->utc())->count(),
            ];
        }

        if ($operational) {
            $start = $now->copy()->subDays(6)->startOfDay();
            $visits = AttendanceLog::where('time_in_at', '>=', $start->copy()->utc())
                ->where('time_in_at', '<', $now->copy()->addDay()->startOfDay()->utc())
                ->selectRaw('DATE(DATE_ADD(time_in_at, INTERVAL ? MINUTE)) as library_date, count(*) as total', [$now->offsetMinutes])
                ->groupBy('library_date')->pluck('total', 'library_date');
            $checkouts = BorrowTransaction::whereBetween('borrow_date', [$start->toDateString(), $date])
                ->selectRaw('borrow_date, count(*) as total')->groupBy('borrow_date')->pluck('total', 'borrow_date');

            for ($day = $start->copy(); $day->toDateString() <= $date; $day->addDay()) {
                $key = $day->toDateString();
                $trend[] = ['label' => $day->format('M j'), 'visits' => (int) ($visits[$key] ?? 0), 'checkouts' => (int) ($checkouts[$key] ?? 0)];
            }
        }

        return view('reports.dashboard', compact('summary', 'operational', 'trend'));
    }

    public function history(Request $request)
    {
        $loans = BorrowTransaction::with('book')->where('borrower_id', $request->user()->getKey())
            ->latest('borrow_date')->orderByDesc('transaction_id')->paginate(20);

        return view('reports.history', compact('loans'));
    }
}
