<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryAuditRequest;
use App\Models\Activity;
use App\Models\Book;
use App\Services\InventoryAudit;

class InventoryAuditController extends Controller
{
    public function index()
    {
        $byStatus = Book::selectRaw('book_status, count(*) as total')
            ->groupBy('book_status')
            ->pluck('total', 'book_status');

        $counts = [
            'Total' => (int) $byStatus->sum(),
            'Available' => (int) ($byStatus['Available'] ?? 0),
            'Borrowed' => (int) ($byStatus['Borrowed'] ?? 0),
            'Lost' => (int) ($byStatus['Lost'] ?? 0),
            'Damaged' => (int) ($byStatus['Damaged'] ?? 0),
        ];

        $recentChecks = Activity::with(['causer', 'subject'])
            ->where('log_name', 'inventory')
            ->latest()
            ->limit(15)
            ->get();

        return view('books.audit', [
            'conditions' => Book::AUDIT_CONDITIONS,
            'counts' => $counts,
            'recentChecks' => $recentChecks,
        ]);
    }

    public function store(InventoryAuditRequest $request, InventoryAudit $audit)
    {
        $data = $request->validated();
        $book = $audit->record($request->user(), $data['book_qr_value'], $data['condition']);

        return $request->expectsJson()
            ? response()->json($book)
            : to_route('inventory.index')->with('status', "{$book->book_title} marked {$book->book_status}.");
    }
}
