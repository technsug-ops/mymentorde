<?php

namespace App\Mail;

use App\Models\StudentAppointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentReminderMail extends Mailable
{
    public function __construct(public readonly StudentAppointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '📅 Randevu Hatırlatması — ' . ($this->appointment->scheduled_at?->format('d.m.Y H:i') ?? ''));
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.appointment-reminder',
            with: ['appointment' => $this->appointment],
        );
    }
}
