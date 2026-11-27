<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Models\InventoryAuditSession;
use App\Services\InventorySessions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventorySessionController extends Controller
{
    public function index()
    {
        $sessions = InventoryAuditSession::withCount('items')->latest('started_at')->orderByDesc('audit_id')->paginate(20);

        return view('inventory.sessions', compact('sessions'));
    }

    public function store(Request $request, InventorySessions $sessions)
    {
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);
        $session = $sessions->start($request->user(), $data['remarks'] ?? null);

        return to_route('inventory.sessions.show', $session)->with('status', 'Inventory audit started.');
    }

    public function show(Request $request, InventoryAuditSession $session)
    {
        $filters = $request->validate(['filter' => ['nullable', Rule::in(['unchecked', 'discrepancies', 'on-loan'])]]);
        $checkedOut = BorrowTransaction::open()->select('book_id');
        $items = $session->items()->with('book')
            ->addSelect(['on_loan' => (clone $checkedOut)->whereColumn('borrow_transaction.book_id', 'inventory_audit_item.book_id')->limit(1)])
            ->when(($filters['filter'] ?? null) === 'unchecked', fn ($query) => $query
                ->where('verification_status', 'Not Checked')->whereNotIn('inventory_audit_item.book_id', $checkedOut))
            ->when(($filters['filter'] ?? null) === 'discrepancies', fn ($query) => $query->whereNotNull('discrepancy_type'))
            ->when(($filters['filter'] ?? null) === 'on-loan', fn ($query) => $query->whereIn('inventory_audit_item.book_id', $checkedOut))
            ->orderBy('inventory_audit_item.book_id')->paginate(20)->withQueryString();

        return view('inventory.session', compact('session', 'items', 'filters'));
    }

    public function update(Request $request, InventoryAuditSession $session, int $item, InventorySessions $sessions)
    {
        $data = $request->validate([
            'verification_status' => ['required', Rule::in(['Present', 'Missing'])],
            'observed_condition' => ['exclude_unless:verification_status,Present', 'required', Rule::in(['Good', 'Damaged'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
        $sessions->verify($request->user(), $session, $item, $data);

        return back()->with('status', 'Inventory result recorded.');
    }

    public function finish(Request $request, InventoryAuditSession $session, InventorySessions $sessions)
    {
        $data = $request->validate(['outcome' => ['required', Rule::in(['approve', 'cancel'])]]);
        $sessions->finish($request->user(), $session, $data['outcome'] === 'cancel');

        return to_route('inventory.sessions.show', $session)->with('status', 'Inventory audit closed.');
    }
}
