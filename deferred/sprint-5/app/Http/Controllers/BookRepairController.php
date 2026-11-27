<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRepair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookRepairController extends Controller
{
    public function index()
    {
        $repairs = BookRepair::with('loan.book')->orderByRaw('completed_at IS NOT NULL')->orderBy('scheduled_on')
            ->orderBy('repair_id')->paginate(20);

        return view('circulation.repairs', compact('repairs'));
    }

    public function complete(Request $request, BookRepair $repair)
    {
        $data = $request->validate(['outcome' => ['required', Rule::in(['Available', 'Damaged'])]]);
        DB::transaction(function () use ($request, $repair, $data): void {
            $book = Book::lockForUpdate()->findOrFail($repair->loan->book_id);
            $repair = BookRepair::lockForUpdate()->findOrFail($repair->getKey());
            if ($repair->completed_at !== null) {
                return;
            }
            if ($book->hasOpenLoan() || $book->book_status !== 'Damaged') {
                throw ValidationException::withMessages(['outcome' => 'This copy must be marked Damaged and have no open loan.']);
            }
            $book->update(['book_status' => $data['outcome']]);
            $repair->update(['completed_at' => now(), 'outcome' => $data['outcome']]);
            activity('circulation')->performedOn($repair)->causedBy($request->user())->event('repair_completed')
                ->withProperties(['book_id' => $book->getKey(), 'outcome' => $data['outcome']])->log('Completed a book repair');
        }, 3);

        return $request->expectsJson() ? response()->noContent()
            : to_route('circulation.repairs.index')->with('status', 'Repair outcome recorded.');
    }
}
