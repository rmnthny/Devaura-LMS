<?php

namespace App\Services;

use App\Jobs\SendLibraryAlert;
use App\Models\BorrowTransaction;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class LibraryAlerts
{
    public function accountUpdated(User $user, string $message, bool $passwordChanged = false): void
    {
        $this->record($user, (string) Str::uuid(), [
            'event' => $passwordChanged ? 'account.password_changed' : 'account.updated',
            'title' => $passwordChanged ? 'Library password changed' : 'Library account updated',
            'message' => $message,
        ]);
    }

    public function loan(User $borrower, BorrowTransaction $loan, string $event): void
    {
        $title = match ($event) {
            'loan.pending' => 'Library book checked out',
            'loan.due' => 'Library book due today',
            'loan.overdue' => 'Library book overdue',
        };
        $due = $loan->due_date->toDateString();
        $message = match ($event) {
            'loan.overdue' => 'was due '.$due.'. Please return it before borrowing another book.',
            'loan.due' => 'is due today ('.$due.') in Manila time.',
            default => 'was checked out and is due '.$due.' in Manila time.',
        };
        $this->record($borrower, $this->loanNotificationId($loan, $event), [
            'event' => $event,
            'title' => $title,
            'message' => $loan->book->book_title.' ('.$loan->transaction_code.') '.$message,
            'loan_id' => $loan->getKey(),
            'book_title' => $loan->book->book_title,
            'transaction_code' => $loan->transaction_code,
            'due_date' => $due,
            'borrow_date' => $loan->borrow_date->toDateString(),
        ]);
    }

    private function record(User $user, string $id, array $data): void
    {
        $notification = $user->notifications()->createOrFirst(['id' => $id], [
            'type' => self::class,
            'data' => $data,
        ]);

        if ($notification->wasRecentlyCreated) {
            // The database queue job commits or rolls back with the notification.
            SendLibraryAlert::dispatch($notification->id)->onConnection('member-mail')->beforeCommit();
        }
    }

    public function remind(): void
    {
        $today = now(config('attendance.timezone'))->toDateString();
        BorrowTransaction::open()->where('due_date', '<=', $today)->chunkById(200, function (Collection $loans) use ($today): void {
            $noticeIds = $loans->mapWithKeys(fn (BorrowTransaction $loan) => [
                $loan->getKey() => $this->loanNotificationId($loan, $this->reminderEvent($loan, $today)),
            ]);
            $existing = DatabaseNotification::whereKey($noticeIds->values()->all())->pluck('id')->flip();

            foreach ($loans as $candidate) {
                if ($existing->has($noticeIds[$candidate->getKey()])
                    && ($candidate->transaction_status === BorrowTransaction::OVERDUE || ! $candidate->isOverdueOn($today))) {
                    continue;
                }

                DB::transaction(function () use ($candidate, $today): void {
                    $loan = BorrowTransaction::open()->with(['book', 'borrower'])->lockForUpdate()->find($candidate->getKey());
                    if (! $loan || $loan->due_date->toDateString() > $today) {
                        return;
                    }

                    $overdue = $loan->isOverdueOn($today);
                    if ($overdue && $loan->transaction_status !== BorrowTransaction::OVERDUE) {
                        $loan->update(['transaction_status' => BorrowTransaction::OVERDUE]);
                    }
                    if ($loan->borrower) {
                        $this->loan($loan->borrower, $loan, $this->reminderEvent($loan, $today));
                    }
                }, 3);
            }
        });
    }

    private function reminderEvent(BorrowTransaction $loan, string $today): string
    {
        return $loan->isOverdueOn($today) ? 'loan.overdue' : 'loan.due';
    }

    private function loanNotificationId(BorrowTransaction $loan, string $event): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'devaura:'.$event.':'.$loan->getKey())->toString();
    }
}
