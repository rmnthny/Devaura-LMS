<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MemberQrMail extends Mailable
{
    public function __construct(public string $code, private string $png) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your UBNHS Library QR card');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.member-qr');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->png, 'library-member-qr.png')->withMime('image/png')];
    }
}
