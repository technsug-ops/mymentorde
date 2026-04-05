<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeStudentMail extends Mailable
{
    public function __construct(public readonly User $student) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'MentorDE\'ye Hoş Geldiniz! 🎓');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-student',
            with: ['student' => $this->student],
        );
    }
}
