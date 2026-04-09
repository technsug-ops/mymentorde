<?php

namespace App\Services;

class DocumentNamingService
{
    /**
     * Standart dosya adı üret.
     * Format: {CATEGORY}_{OWNER}_{DATE}_{Ad_ilk_harf}_{Soyad}.{ext}
     * Örnek: DOC-PASS_GST00000051_20260406_M_Yilmaz.png
     */
    public function buildStandardFileName(
        string $studentId,
        string $categoryCode,
        string $firstName = '',
        string $lastName = '',
        string $extension = 'pdf',
    ): string {
        $cleanCategory = strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', $categoryCode) ?: 'DOC');
        $cleanStudent  = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', $studentId) ?: 'STD');
        $date          = now()->format('Ymd');
        $ext           = strtolower($extension !== '' ? $extension : 'pdf');

        $initial = mb_strtoupper(mb_substr(trim($firstName), 0, 1)) ?: 'X';
        $surname = $this->slugify(trim($lastName)) ?: 'bilinmiyor';

        return sprintf('%s_%s_%s_%s_%s.%s', $cleanCategory, $cleanStudent, $date, $initial, $surname, $ext);
    }

    public function buildDocumentId(int $dbId): string
    {
        return sprintf('DOC-%s-%06d', now()->format('Y'), $dbId);
    }

    private function slugify(string $text): string
    {
        $map = [
            'ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G',
            'ı' => 'i', 'İ' => 'I', 'ö' => 'o', 'Ö' => 'O',
            'ş' => 's', 'Ş' => 'S', 'ü' => 'u', 'Ü' => 'U',
            'ä' => 'a', 'Ä' => 'A', 'ß' => 'ss',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^A-Za-z0-9]/', '-', $text) ?? '';
        $text = preg_replace('/-+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}
