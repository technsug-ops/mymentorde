<?php

namespace App\Mail;

use App\Models\GuestTicket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketClosedMail extends Mailable
{
    public function __construct(public readonly GuestTicket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Destek Talebiniz Çözüldü #' . $this->ticket->id);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ticket-closed',
            with: ['ticket' => $this->ticket],
        );
    }
}
