<?php

namespace App\Jobs;

use App\Mail\LibraryAlertMail;
use App\Mail\PasswordChangedMail;
use App\Models\BorrowTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;

class SendLibraryAlert implements ShouldQueue
{
    use Queueable;

    public int $timeout = 45;

    public function __construct(public string $notificationId) {}

    public function handle(): void
    {
        $notification = DatabaseNotification::find($this->notificationId);
        if (! $notification || $notification->email_attempted_at !== null) {
            return;
        }

        $recipient = $notification->notifiable;
        if (! $recipient instanceof User || ! $this->isCurrent($recipient, $notification->data)) {
            return;
        }

        // A worker can lose its connection after SMTP accepts a message.
        $claimed = DatabaseNotification::whereKey($notification->id)->whereNull('email_attempted_at')
            ->update(['email_attempted_at' => now()]);
        if (! $claimed) {
            return;
        }

        $data = $notification->data;
        $memberName = $recipient->fullName();
        $localTime = $notification->created_at->copy()->timezone(config('attendance.timezone'))
            ->format('F j, Y \\a\\t g:i A').' (Manila time)';
        $fields = isset($data['book_title'], $data['transaction_code'], $data['due_date'], $data['borrow_date'])
            ? [
                'Book title' => $data['book_title'],
                'Transaction reference' => $data['transaction_code'],
                'Checkout date' => CarbonImmutable::parse($data['borrow_date'])->format('F j, Y'),
                'Due date' => CarbonImmutable::parse($data['due_date'])->format('F j, Y').' (Manila time)',
            ]
            : ['Notice issued' => $localTime];

        $mail = $data['event'] === 'account.password_changed'
            ? new PasswordChangedMail($memberName, $localTime)
            : new LibraryAlertMail($data['title'], $memberName, $data['event'], $data['message'], $fields);
        Mail::to($recipient->email)->send($mail);
        DatabaseNotification::whereKey($notification->id)->update(['email_sent_at' => now()]);
    }

    private function isCurrent(User $recipient, array $data): bool
    {
        if (! isset($data['loan_id'])) {
            return true;
        }

        $loan = BorrowTransaction::open()->where('borrower_id', $recipient->getKey())->find($data['loan_id']);
        $today = now(config('attendance.timezone'))->toDateString();

        return $loan && match ($data['event']) {
            'loan.due' => $loan->due_date->toDateString() === $today && ! $loan->isOverdueOn($today),
            'loan.overdue' => $loan->isOverdueOn($today),
            default => true,
        };
    }
}
