<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotificationMail extends Mailable
{
    public string $mailSubject;
    public string $mailBody;

    public function __construct(
        string $mailSubject,
        string $mailBody,
    ) {
        $this->subject($mailSubject);
        $this->mailSubject = $mailSubject;
        $this->mailBody = $mailBody;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.notification');
    }
}
