<?php

namespace Tests\Feature\Tenancy;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\PlatformOwnedData;
use App\Models\Concerns\SharedAcrossCompanies;
use App\Models\Concerns\SharedBetweenTwoCompanies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * TENANT KAPSAM BEKÇİSİ
 *
 * Kural: `company_id` kolonu olan her model, niyetini AÇIKÇA bildirmek zorundadır —
 * ya `BelongsToCompany` (şirkete ait, filtrelenir) ya `SharedAcrossCompanies`
 * (bilinçli olarak paylaşımlı).
 *
 * Neden: Bir modele trait eklemeyi unutmak = sessiz veri sızıntısı. Firma A'nın
 * yöneticisi firma B'nin kaydını görür ve kimse fark etmez. Bu test o unutkanlığı
 * derleme zamanında yakalar.
 *
 * BASELINE: Aşağıdaki liste, multi-tenant çalışmasına başlarken devraldığımız
 * eksiklerdir. Her faz bu listeyi KÜÇÜLTMELİ. Listeye yeni isim EKLENMEMELİ —
 * yeni bir model yazarken trait eklemek zorunludur.
 *
 * Liste boşaldığında bu test tam kapsam garantisi verir.
 */
class ModelScopeCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Devralınan eksikler — BOŞ.
     *
     * Liste 2026-08-04'te 50 modelle açıldı ve kurulduğu günden 2026-08-08'e
     * kadar hiç küçülmemişti; izolasyon Faz 3 dışında açılmamıştı. Bu boşluk
     * iki adımda kapandı:
     *
     *   1. Ölçüm ve geri-doldurma (2026_08_08_090000 + _120000): her kaydın
     *      firması üst kaydından türetildi, türetilemeyenler tek tek
     *      incelendi. Sahibi hiç olmayan şablon satırları `company_id = 0`
     *      fabrika işaretine çekildi.
     *   2. Kalan 44 modele trait eklendi. Öğrenci/aday süreci modelleri
     *      `OwnedBySubjectCompany` kullanıyor: kayıt işlemi YAPANIN değil,
     *      konusunun şirketine yazılıyor — aksi halde MentorDE personelinin
     *      partner öğrencisi için açtığı kayıt partnerin kutusuna hiç düşmez.
     *
     * ⚠ Bu liste bir daha DOLMAMALI. Boş kalması, `company_id` kolonu olan
     * her modelin tenant niyetini açıkça bildirdiği anlamına geliyor.
     *
     * @var list<class-string>
     */
    private const BASELINE_MISSING = [];

    public function test_no_new_model_skips_tenant_scope_declaration(): void
    {
        $missing = $this->modelsMissingScopeDeclaration();

        $unexpected = array_values(array_diff($missing, self::BASELINE_MISSING));

        $this->assertSame([], $unexpected, sprintf(
            "Bu modellerde `company_id` var ama tenant niyeti bildirilmemiş:\n  %s\n\n"
            . "Düzeltmek için modele şunlardan birini ekle:\n"
            . "  • use BelongsToCompany;          → veri şirkete ait, filtrelenmeli\n"
            . "  • use SharedAcrossCompanies;     → bilinçli olarak tüm şirketlerde ortak\n"
            . "  • use SharedBetweenTwoCompanies; → kaydın iki tarafı var, sınır sorguda kuruluyor\n",
            implode("\n  ", $unexpected)
        ));
    }

    /**
     * Baseline küçüldükçe listeyi güncel tutar: bir model düzeltildiğinde
     * onu listeden çıkarmayı hatırlatır. Böylece liste gerçeği yansıtmaya devam eder.
     */
    public function test_baseline_does_not_contain_already_fixed_models(): void
    {
        $missing = $this->modelsMissingScopeDeclaration();

        $fixed = array_values(array_diff(self::BASELINE_MISSING, $missing));

        $this->assertSame([], $fixed, sprintf(
            "Bu modeller artık tenant niyetini bildiriyor — BASELINE_MISSING listesinden çıkar:\n  %s",
            implode("\n  ", $fixed)
        ));
    }

    /**
     * `company_id` kolonu olup ne BelongsToCompany ne SharedAcrossCompanies kullanan modeller.
     *
     * @return list<string>  model kısa adları
     */
    private function modelsMissingScopeDeclaration(): array
    {
        $missing = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (!class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);

            if ($ref->isAbstract() || !$ref->isSubclassOf(Model::class)) {
                continue;
            }

            $table = (new $class())->getTable();

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            $traits = class_uses_recursive($class);

            $declared = in_array(BelongsToCompany::class, $traits, true)
                || in_array(SharedAcrossCompanies::class, $traits, true)
                // İki taraflı kayıt: global kapsam uygulanamaz, sınır
                // sorgularda elle kuruluyor. Muafiyet değil, farklı bir
                // koruma biçimi — bkz. SharedBetweenTwoCompanies.
                || in_array(SharedBetweenTwoCompanies::class, $traits, true)
                // Platform sahibinin kendi kaydı: company_id sahipliği değil
                // konuyu gösteriyor. İzolasyon rotayla — bkz. PlatformOwnedData.
                || in_array(PlatformOwnedData::class, $traits, true);

            if (!$declared) {
                $missing[] = $ref->getShortName();
            }
        }

        sort($missing);

        return $missing;
    }
}
