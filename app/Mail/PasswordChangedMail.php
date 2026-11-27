<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $memberName,
        public readonly string $changedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'UBNHS Library — Your password was changed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.library-alert',
            text: 'emails.library-alert-text',
            with: [
                'title' => 'Library account password changed',
                'section' => 'Account Security',
                'details' => 'The password for your library account was changed.',
                'fields' => ['Date and time' => $this->changedAt],
                'instructions' => 'If you made this change, no further action is needed. Otherwise, please contact the Head Librarian immediately.',
            ],
        );
    }
}
