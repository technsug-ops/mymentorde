<?php

namespace App\Contracts;

/**
 * Almanya üniversite programı kataloğu sözleşmesi (kaynak-bağımsız).
 *
 * Implementation'lar (Expatrio, Hochschulkompass vs.) bu interface'i yerine
 * getirerek aynı API ile farklı 3rd party kaynakları üzerinden program search,
 * find ve sync yapılabilir.
 *
 * Search/find dönen item formatı:
 *   [
 *     'id'                    => string,  // kaynağın kendi ID'si (UUID veya HK numara)
 *     'source'                => 'expatrio'|'hk',
 *     'label'                 => string,  // "Course — University" formatında
 *     'university_name'       => string,
 *     'course_name'           => string,
 *     'degree_specification'  => ?string,
 *     'location'              => ?string,
 *     'languages'             => array,
 *   ]
 */
interface ProgramCatalogContract
{
    /** Kaynak adı: 'expatrio', 'hk', vb. */
    public function source(): string;

    /**
     * Programları search et — autocomplete için LIKE query.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 20): array;

    /**
     * Tek program detayı (source ID ile).
     *
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array;

    /**
     * Tüm kataloğu DB'ye senkronize et. Returns kaydedilen program sayısı.
     */
    public function syncAll(): int;

    /** Kaynağın aktif olup olmadığı (DB'de veri var mı?). */
    public function isActive(): bool;
}
