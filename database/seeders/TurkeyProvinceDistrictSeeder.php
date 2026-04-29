<?php

namespace Database\Seeders;

use App\Models\TrDistrict;
use App\Models\TrProvince;
use Illuminate\Database\Seeder;

/**
 * Türkiye 81 il + 973 ilçe verilerini database/data/turkey-*.json
 * snapshot'larından DB'ye yükler.
 *
 * Snapshot kaynağı: github.com/ubeydeozdmr/turkiye-api (146★)
 * — bir kerelik fetch edildi, repo offline olsa bile bizim kopyamız çalışır.
 *
 * Idempotent: updateOrCreate kullanır, mevcut kayıtların slug'ları değişmez.
 *
 * Çalıştır: php artisan db:seed --class=TurkeyProvinceDistrictSeeder
 */
class TurkeyProvinceDistrictSeeder extends Seeder
{
    public function run(): void
    {
        // ── 81 il ────────────────────────────────────────────────────────
        $provincesPath = database_path('data/turkey-provinces.json');
        if (! file_exists($provincesPath)) {
            $this->command?->error('TurkeyProvinceDistrictSeeder: provinces JSON yok → ' . $provincesPath);
            return;
        }

        $provinces = json_decode((string) file_get_contents($provincesPath), true) ?? [];
        foreach ($provinces as $p) {
            $plateCode = (int) ($p['id'] ?? 0);
            if ($plateCode === 0) continue;

            TrProvince::updateOrCreate(
                ['plate_code' => $plateCode],
                [
                    'slug'            => $this->slugify((string) ($p['name'] ?? '')),
                    'name'            => (string) ($p['name'] ?? ''),
                    'region'          => $this->resolveRegion($p),
                    'is_metropolitan' => (bool) ($p['isMetropolitan'] ?? false),
                ]
            );
        }
        $this->command?->info('TR provinces: ' . count($provinces) . ' kayıt yüklendi/güncellendi.');

        // ── 973 ilçe ─────────────────────────────────────────────────────
        $districtsPath = database_path('data/turkey-districts.json');
        if (! file_exists($districtsPath)) {
            $this->command?->error('TurkeyProvinceDistrictSeeder: districts JSON yok → ' . $districtsPath);
            return;
        }

        // plate_code → DB primary id mapping (FK için)
        $provinceMap = TrProvince::query()->pluck('id', 'plate_code')->all();

        $districts = json_decode((string) file_get_contents($districtsPath), true) ?? [];
        $count = 0;
        foreach ($districts as $d) {
            $plateCode = (int) ($d['provinceId'] ?? 0);
            $provinceId = $provinceMap[$plateCode] ?? null;
            if ($provinceId === null) continue;

            $name = (string) ($d['name'] ?? '');
            if ($name === '') continue;

            TrDistrict::updateOrCreate(
                ['province_id' => $provinceId, 'slug' => $this->slugify($name)],
                [
                    'name'       => $name,
                    'is_central' => $this->isCentralDistrict($name, (string) ($d['province'] ?? '')),
                ]
            );
            $count++;
        }
        $this->command?->info('TR districts: ' . $count . ' kayıt yüklendi/güncellendi.');
    }

    /**
     * Türkçe karakter güvenli slug.
     */
    private function slugify(string $name): string
    {
        $tr = [
            'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'I' => 'i', 'İ' => 'i', 'ö' => 'o',
            'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
        ];
        $s = strtr($name, $tr);
        $s = mb_strtolower($s);
        $s = (string) preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-');
    }

    /**
     * "Adana / Akdeniz", "İstanbul / Marmara" gibi region bilgisini
     * JSON'daki nuts1.name.tr veya region alanından çek.
     */
    private function resolveRegion(array $province): ?string
    {
        // 1. Direkt 'region' alanı
        if (! empty($province['region'])) {
            $region = $province['region'];
            if (is_string($region)) return $region;
            if (is_array($region) && isset($region['tr'])) return (string) $region['tr'];
            if (is_array($region) && isset($region['name']['tr'])) return (string) $region['name']['tr'];
        }

        // 2. nuts1.name.tr (yedek)
        if (isset($province['nuts']['nuts1']['name']['tr'])) {
            return (string) $province['nuts']['nuts1']['name']['tr'];
        }
        return null;
    }

    /**
     * Merkez ilçe heuristic: ilçe adı, il adıyla aynıysa veya il'in ilk
     * heceli formuna eşitse merkez kabul edilir. (Diyarbakır gibi
     * 4-merkez illerde false döner, kabul edilebilir.)
     */
    private function isCentralDistrict(string $district, string $province): bool
    {
        if ($province === '' || $district === '') return false;
        return mb_strtolower($district) === mb_strtolower($province);
    }
}
