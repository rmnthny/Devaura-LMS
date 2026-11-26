<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LibraryAlertMail extends Mailable
{
    public function __construct(
        public string $title,
        public string $memberName,
        public string $event,
        public string $message,
        public array $fields,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'UBNHS Library — '.$this->title);
    }

    public function content(): Content
    {
        $details = match ($this->event) {
            'loan.pending' => 'The library has recorded your checkout.',
            'loan.due' => 'Our records show that your borrowed book is due today.',
            'loan.overdue' => 'Our records show that your borrowed book is overdue.',
            default => $this->message,
        };
        $instructions = match ($this->event) {
            'loan.pending' => 'Please return the book to the library circulation desk by the due date.',
            'loan.due' => 'Please return the book to the library circulation desk today.',
            'loan.overdue' => 'Please return the book to the library circulation desk as soon as possible. You may borrow again once all overdue items have been returned.',
            default => 'If you did not expect this change, please contact the Head Librarian.',
        };

        $isLoan = str_starts_with($this->event, 'loan.');

        return new Content(
            view: 'emails.library-alert',
            text: 'emails.library-alert-text',
            with: [
                'section' => $isLoan ? 'Circulation Desk' : 'Account Services',
                'details' => $isLoan && ! isset($this->fields['Book title'])
                    ? $this->message
                    : $details,
                'instructions' => $instructions,
            ],
        );
    }
}
