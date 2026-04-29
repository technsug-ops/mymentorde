<?php

namespace App\Services\ProgramCatalog;

use App\Contracts\ProgramCatalogContract;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Program kataloğu kayıt deposu — kaynak-bağımsız (Expatrio, Hochschulkompass vs.)
 * search/find çağrıları için tek giriş noktası.
 *
 * Kayıt: AppServiceProvider.boot() içinde register edilir.
 *
 * Kullanım:
 *   $registry = app(ProgramCatalogRegistry::class);
 *   $registry->getAdapter('expatrio')->find($id);
 *   $registry->searchAll('Informatik');  // tüm aktif kaynaklarda
 */
class ProgramCatalogRegistry
{
    /** @var array<string, ProgramCatalogContract> */
    private array $adapters = [];

    public function __construct()
    {
        // Default registration — Expatrio her zaman aktif (mevcut implementation)
        $this->register(new ExpatrioCatalogAdapter());

        // ── İleride aktif edilecek (boş tablo, henüz adapter implementation yok) ──
        // $this->register(new HochschulkompassCatalogAdapter());
    }

    public function register(ProgramCatalogContract $adapter): void
    {
        $this->adapters[$adapter->source()] = $adapter;
    }

    public function getAdapter(string $source): ProgramCatalogContract
    {
        if (! isset($this->adapters[$source])) {
            // Bilinmeyen source → default Expatrio (geriye uyumlu)
            return $this->adapters['expatrio'] ?? throw new RuntimeException('No catalog adapter registered.');
        }
        return $this->adapters[$source];
    }

    /** Aktif tüm kaynaklarda search yap, sonuçları birleştir. */
    public function searchAll(string $term, int $limitPerSource = 20): array
    {
        $results = [];
        foreach ($this->activeAdapters() as $adapter) {
            foreach ($adapter->search($term, $limitPerSource) as $item) {
                $results[] = $item;
            }
        }
        return $results;
    }

    /** Source bazlı find — kullanıcının seçtiği program bilgisini bulur. */
    public function find(string $source, string $id): ?array
    {
        return $this->getAdapter($source)->find($id);
    }

    /** @return Collection<int, ProgramCatalogContract> */
    public function activeAdapters(): Collection
    {
        return collect($this->adapters)->filter(fn (ProgramCatalogContract $a) => $a->isActive())->values();
    }

    /** Kayıtlı tüm kaynaklar (aktif olsun olmasın). */
    public function allSources(): array
    {
        return array_keys($this->adapters);
    }
}
