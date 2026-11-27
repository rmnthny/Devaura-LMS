<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\InventoryAuditItem;
use App\Models\InventoryAuditSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventorySessions
{
    public function start(User $head, ?string $remarks): InventoryAuditSession
    {
        return DB::transaction(function () use ($head, $remarks): InventoryAuditSession {
            $now = now();
            $session = InventoryAuditSession::create([
                'conducted_by' => $head->getKey(), 'started_at' => $now, 'audit_status' => InventoryAuditSession::OPEN,
                'remarks' => $remarks,
            ]);
            InventoryAuditItem::query()->insertUsing(
                ['audit_id', 'book_id', 'expected_status', 'created_at', 'updated_at'],
                Book::query()->selectRaw('? as audit_id, book_id, book_status, ? as created_at, ? as updated_at', [$session->getKey(), $now, $now]),
            );
            $this->log($head, $session, 'started');

            return $session;
        }, 3);
    }

    public function verify(User $head, InventoryAuditSession $session, int $itemId, array $data): void
    {
        DB::transaction(function () use ($head, $session, $itemId, $data): void {
            $session = $this->lockOpen($session);
            $item = $session->items()->findOrFail($itemId);
            $status = $data['verification_status'];
            $condition = $status === 'Present' ? $data['observed_condition'] : null;
            $discrepancy = match (true) {
                $status === 'Missing' => 'Missing',
                $condition === 'Damaged' && $item->expected_status !== 'Damaged' => 'Damaged',
                $status === 'Present' && in_array($item->expected_status, ['Borrowed', 'Lost'], true) => 'Status Mismatch',
                $condition === 'Good' && $item->expected_status === 'Damaged' => 'Status Mismatch',
                default => null,
            };
            $item->update([
                'verification_status' => $status, 'observed_condition' => $condition,
                'discrepancy_type' => $discrepancy, 'remarks' => $data['remarks'] ?? null, 'verified_at' => now(),
            ]);
            activity('inventory')->performedOn($item)->causedBy($head)->event('verified')
                ->withProperties(['audit_id' => $session->getKey(), 'book_id' => $item->book_id, 'condition' => $condition, 'discrepancy' => $discrepancy])
                ->log('Recorded an inventory audit verification');
        }, 3);
    }

    public function finish(User $head, InventoryAuditSession $session, bool $cancel): void
    {
        DB::transaction(function () use ($head, $session, $cancel): void {
            $session = $this->lockOpen($session);
            if (! $cancel) {
                $unchecked = $session->items()->where('verification_status', 'Not Checked')->orderBy('book_id')->get();
                $openLoans = BorrowTransaction::open()->whereIn('book_id', $unchecked->pluck('book_id'))
                    ->orderBy('book_id')->orderBy('transaction_id')->lockForUpdate()->get()->keyBy('book_id');
                foreach ($unchecked as $item) {
                    $loan = $openLoans->get($item->book_id);
                    if (! $loan) {
                        throw ValidationException::withMessages(['audit' => 'Verify every unchecked copy that is not on an open loan before approving.']);
                    }
                    $item->update([
                        'remarks' => 'Open loan verified at approval: '.$loan->transaction_code,
                        'verified_at' => now(),
                    ]);
                }
            }
            $session->update(['audit_status' => $cancel ? 'Cancelled' : 'Completed', 'completed_at' => now()]);
            $this->log($head, $session, $cancel ? 'cancelled' : 'approved');
        }, 3);
    }

    private function lockOpen(InventoryAuditSession $session): InventoryAuditSession
    {
        $session = InventoryAuditSession::lockForUpdate()->findOrFail($session->getKey());
        if ($session->audit_status !== InventoryAuditSession::OPEN) {
            throw ValidationException::withMessages(['audit' => 'This inventory audit is already closed.']);
        }

        return $session;
    }

    private function log(User $head, InventoryAuditSession $session, string $event): void
    {
        activity('inventory')->performedOn($session)->causedBy($head)->event($event)->log('Inventory audit '.$event);
    }
}
