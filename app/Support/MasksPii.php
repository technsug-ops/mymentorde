<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * K3 — PII maskeleme yardımcı trait.
 * API controller'larında hassas verileri role göre maskelemek için kullanılır.
 *
 * Kullanım: controller'a "use MasksPii;" ekle
 */
trait MasksPii
{
    protected function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '***@***.***';
        }
        [$local, $domain] = explode('@', $email, 2);
        return substr($local, 0, 1) . '***@' . substr($domain, 0, 1) . '***.' . Str::afterLast($domain, '.');
    }

    protected function maskPhone(?string $phone): string
    {
        if (!$phone) {
            return '***';
        }
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) < 4) {
            return '***';
        }
        return substr($clean, 0, 3) . ' *** *** ' . substr($clean, -2);
    }

    protected function maskName(?string $name): string
    {
        if (!$name) {
            return '***';
        }
        $parts = explode(' ', trim($name));
        return substr($parts[0], 0, 1) . '*** ' . (count($parts) > 1 ? substr(end($parts), 0, 1) . '***' : '');
    }

    /**
     * Verilen kullanıcı rolünün PII görmeye yetkisi var mı?
     * Senior ve Manager tam erişime sahip; Marketing Staff maskelenir.
     */
    protected function canViewFullPii(string $role): bool
    {
        return in_array($role, ['manager', 'senior', 'mentor']);
    }
}
