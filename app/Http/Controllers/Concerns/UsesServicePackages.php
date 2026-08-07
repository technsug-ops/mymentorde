<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ServiceCatalog;

/**
 * Hizmet paketlerine erişim.
 *
 * Paketler eskiden config'ten okunuyordu; artık firma bazlı katalogdan
 * geliyor (bkz. App\Support\ServiceCatalog). Bu trait'in imzası bilerek
 * DEĞİŞMEDİ — kullanan onlarca yer aynı diziyi almaya devam ediyor.
 *
 * @see \App\Support\ServiceCatalog miras zinciri ve neden tek kapı olduğu
 */
trait UsesServicePackages
{
    /** @return list<array<string,mixed>> */
    private function servicePackages(?int $companyId = null): array
    {
        return ServiceCatalog::packages($companyId)->all();
    }

    /** @return list<array<string,mixed>> */
    private function extraServiceOptions(?int $companyId = null): array
    {
        return ServiceCatalog::extras($companyId)->all();
    }

    /** Pasif paketler de bulunur — geçmiş seçimleri çözmek için. */
    private function findPackageByCode(string $code, ?int $companyId = null): ?array
    {
        return ServiceCatalog::findPackage($code, $companyId);
    }

    private function findExtraServiceByCode(string $code, ?int $companyId = null): ?array
    {
        return ServiceCatalog::findExtra($code, $companyId);
    }
}
