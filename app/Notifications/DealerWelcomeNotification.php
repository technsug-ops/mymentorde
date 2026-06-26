<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Bayi başvurusu onaylanınca yeni hesap için gönderilen ilk e-posta.
 *
 * Laravel'in varsayılan "Reset Password" maili yerine kullanılır: hesap YENİ
 * oluşturuluyor, bir şifre sıfırlama TALEBİ yok — bu yüzden "hoş geldiniz,
 * şifrenizi belirleyin" (welcome / set password) metni doğru olandır.
 *
 * Şifre belirleme linki, aynı parola sıfırlama token mekanizmasını kullanır
 * (password.reset route'u), sadece sunum farklı.
 */
class DealerWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $brand = config('brand.name', 'MentorDE');
        $name  = trim((string) ($notifiable->name ?? ''));

        // Default ResetPassword ile aynı URL formatı (token + email)
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject("Bayi Hesabınız Hazır · {$brand}")
            ->greeting($name !== '' ? "Merhaba {$name}," : 'Merhaba,')
            ->line("Tebrikler! {$brand} satış ortağı (bayi) başvurunuz onaylandı ve panel hesabınız oluşturuldu.")
            ->line('Panele giriş yapabilmek için aşağıdaki bağlantıdan **şifrenizi belirleyin**:')
            ->action('Şifremi Belirle', $url)
            ->line('Bu bağlantı 60 dakika boyunca geçerlidir. Süre dolarsa giriş ekranındaki "Şifremi unuttum" ile yenisini isteyebilirsiniz.')
            ->line('Şifrenizi belirledikten sonra e-posta adresiniz ve yeni şifrenizle panele giriş yapabilirsiniz.')
            ->salutation("Hoş geldiniz, {$brand} ekibi");
    }
}
