<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class WelcomeMemberMail extends Mailable implements ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 45;

    public function __construct(
        public string $name,
        public string $email,
        public string $memberType,
        public ?string $department,
        public string $maskedReference,
        public string $qrId,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your UBNHS Library account');
    }

    public function send($mailer)
    {
        $account = User::where('email', $this->email)->where('user_qr_value', $this->qrId)
            ->where('account_status', User::STATUS_ACTIVE)->first();
        if (! $account || ! Hash::check($this->temporaryPassword, $account->getAuthPassword())) {
            return null;
        }

        try {
            return parent::send($mailer);
        } catch (TransportExceptionInterface) {
            throw new \RuntimeException('The welcome email could not be sent through SMTP.');
        }
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome-member', text: 'emails.welcome-member-text');
    }
}
