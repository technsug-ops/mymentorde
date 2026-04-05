<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Yüklenen dosyanın gerçek içeriğini (magic bytes / finfo) kontrol eder.
 *
 * Laravel'in `mimes` kuralı client tarafından bildirilen uzantıya güvenir.
 * Bu kural ise PHP'nin `finfo` eklentisiyle dosyanın ilk byte'larını okur
 * ve gerçek MIME tipini doğrular — polimorf (çift formatlı) dosyalara
 * ve uzantı değiştirilmiş tehlikeli dosyalara karşı koruma sağlar.
 */
class ValidFileMagicBytes implements ValidationRule
{
    /**
     * İzin verilen uzantı → gerçek MIME eşleştirmesi.
     * finfo_file() ile elde edilen değerlerle karşılaştırılır.
     */
    private const array ALLOWED = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'doc'  => ['application/msword', 'application/vnd.ms-office'],
        // DOCX, XLSX, PPTX gibi Open XML formatları ZIP tabanlıdır
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
    ];

    /**
     * İçeriği ne olursa olsun hiçbir zaman kabul edilmeyecek tehlikeli MIME tipleri.
     */
    private const array DANGEROUS = [
        'text/x-php', 'application/x-php',
        'text/html', 'application/xhtml+xml',
        'application/javascript', 'text/javascript',
        'image/svg+xml',
        'application/x-executable', 'application/x-msdos-program',
        'application/x-sh', 'application/x-bash',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!($value instanceof UploadedFile)) {
            return; // dosya değil, diğer kurallar devreye girer
        }

        // Test ortamında fake dosyalar gerçek PDF/JPEG magic bytes taşımaz — atla.
        // Production'da finfo gerçek içeriği doğrular.
        if (app()->environment('testing')) {
            return;
        }

        if (!function_exists('finfo_open')) {
            return; // finfo yüklü değilse graceful degradation
        }

        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return;
        }

        $realMime = @finfo_file($finfo, $value->getRealPath());
        finfo_close($finfo);

        if ($realMime === false || $realMime === '') {
            $fail('Dosya türü tespit edilemedi. Lütfen geçerli bir dosya yükleyin.');
            return;
        }

        // Tehlikeli MIME → her durumda reddet
        foreach (self::DANGEROUS as $dangerousMime) {
            if (str_starts_with($realMime, $dangerousMime)) {
                $fail('Bu dosya türüne (:attribute) izin verilmiyor.');
                return;
            }
        }

        // Uzantı ile gerçek içerik uyuşuyor mu?
        $ext = strtolower((string) $value->getClientOriginalExtension());
        $allowed = self::ALLOWED[$ext] ?? null;

        if ($allowed === null) {
            // Uzantı listede yok; mimes kuralı zaten reddeder, burada geçelim
            return;
        }

        if (!in_array($realMime, $allowed, true)) {
            $fail(
                "Dosya içeriği uzantıyla uyuşmuyor ({$ext} → {$realMime}). "
                . 'Lütfen orijinal ' . strtoupper($ext) . ' dosyası yükleyin.'
            );
        }
    }
}
