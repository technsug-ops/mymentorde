<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Türkçe e-posta doğrulama bildirimi.
 *
 * Default Laravel VerifyEmail'i extend eder:
 *  - Mesaj Türkçeleştirildi
 *  - Doğrulama linki PUBLIC welcome.verify route'una gider (auth gerektirmez).
 *    Bu sayede kullanıcı login değilken de tek tıklamayla doğrulama yapabilir
 *    (race condition: ilk tıklamada login'e redirect → ikinci tıklama gerek
 *    sorunu önlendi).
 */
class VerifyEmailTr extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);
        $brand = config('brand.name', 'MentorDE');
        $name = trim((string) ($notifiable->name ?? ''));

        return (new MailMessage)
            ->subject("E-posta Doğrulama · {$brand}")
            ->greeting($name !== '' ? "Merhaba {$name}," : 'Merhaba,')
            ->line("Hesabınızı kullanmaya başlamak için e-posta adresinizi doğrulamanız gerekiyor.")
            ->action('E-postamı Doğrula', $url)
            ->line('Bu bağlantı 60 dakika boyunca geçerli, sonra yenisini istemeniz gerekecek.')
            ->line('Bu hesabı siz oluşturmadıysanız bu e-postayı görmezden gelebilirsiniz.')
            ->salutation("Saygılarımızla, {$brand}");
    }

    /**
     * Public welcome.verify route'una giden imzalı (signed) URL.
     * Default verification.verify auth gerektirir; welcome.verify gerektirmez.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'welcome.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
