<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Self-service signup sonrası yeni şirket sahibine gönderilen welcome maili.
 *
 * İçerik: hoş geldin + 14 gün trial bilgisi + login URL + quick start tips.
 * Queueable — signup response'unu yavaşlatmasın diye notifications queue'ya düşer.
 */
class WelcomeNewCompanyMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public string $queue = 'notifications';

    public function __construct(
        public readonly Company $company,
        public readonly User $manager,
    ) {}

    public function envelope(): Envelope
    {
        $brand = config('brand.name', 'MentorDE');
        return new Envelope(
            subject: "🎉 {$brand}'ye hoş geldin — 14 gün ücretsiz başladı",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-new-company',
            with: [
                'company'      => $this->company,
                'manager'      => $this->manager,
                'trialEndsAt'  => $this->company->trial_ends_at,
                'loginUrl'     => url('/login'),
                'dashboardUrl' => url('/manager/dashboard'),
                'planUrl'      => url('/manager/my-plan'),
                'brandName'    => config('brand.name', 'MentorDE'),
                'supportEmail' => 'destek@mentorde.com',
            ],
        );
    }
}
