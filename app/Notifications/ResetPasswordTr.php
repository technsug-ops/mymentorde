<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Türkçe şifre sıfırlama bildirimi (bkz. VerifyEmailTr — aynı desen).
 *
 * Default Laravel ResetPassword İngilizce ve konu/imza platform adıyla çıkar.
 * Marka config('brand.name')'den okunur: Brand::apply() ziyaret edilen domainin
 * şirketine göre çözdüğü için partner domaininde partnerin adı görünür.
 */
class ResetPasswordTr extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $brand  = trim((string) config('brand.name', '')) ?: 'MentorDE';
        $name   = trim((string) ($notifiable->name ?? ''));
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject("Şifre Sıfırlama · {$brand}")
            ->greeting($name !== '' ? "Merhaba {$name}," : 'Merhaba,')
            ->line('Hesabınız için şifre sıfırlama talebi aldık. Yeni şifrenizi belirlemek için aşağıdaki düğmeye tıklayın.')
            ->action('Şifremi Sıfırla', $url)
            ->line("Bu bağlantı {$expire} dakika boyunca geçerli.")
            ->line('Bu talebi siz yapmadıysanız herhangi bir işlem yapmanıza gerek yok; şifreniz değişmeden kalır.')
            ->salutation("Saygılarımızla, {$brand}");
    }
}
