<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PolicyDocument extends Model
{
    use BelongsToCompany;

    public const KIND_PRIVACY        = 'privacy'; // KVKK / Datenschutz / GDPR (3 dil aynı kind altında)
    public const KIND_COOKIE         = 'cookie';
    public const KIND_TERMS          = 'terms';
    public const KIND_IMPRINT        = 'imprint';
    public const KIND_TOM            = 'tom';            // Technische und Organisatorische Maßnahmen (DSGVO Art. 32, internal)
    public const KIND_INCIDENT_PLAN  = 'incident_plan';  // Datenpannen-Notfallplan (DSGVO Art. 33-34, internal)

    public const LOCALES = ['tr', 'de', 'en'];
    public const LOCALE_LABELS = [
        'tr' => 'KVKK (Türkçe)',
        'de' => 'Datenschutzerklärung (Deutsch)',
        'en' => 'GDPR (English)',
    ];

    protected $fillable = [
        'company_id',
        'kind',
        'locale',
        'title',
        'body',
        'updated_by_user_id',
    ];

    /**
     * 3 dilli kind için company'nin tüm metinlerini döndür.
     * Yoksa boş string döner.
     *
     * @return array<string,array{title:string,body:string,updated_at:?string}>
     */
    public static function loadAllLocales(int $companyId, string $kind): array
    {
        $rows = static::query()
            ->where('company_id', $companyId)
            ->where('kind', $kind)
            ->get();

        $out = [];
        foreach (self::LOCALES as $locale) {
            $row = $rows->firstWhere('locale', $locale);
            $out[$locale] = [
                'title'      => (string) ($row->title ?? ''),
                'body'       => (string) ($row->body ?? ''),
                'updated_at' => $row?->updated_at?->toIso8601String(),
            ];
        }
        return $out;
    }
}
