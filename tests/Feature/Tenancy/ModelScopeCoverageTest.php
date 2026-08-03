<?php

namespace Tests\Feature\Tenancy;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\SharedAcrossCompanies;
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
     * Devralınan eksikler (2026-08-04 · 50 model).
     *
     * Sıra: Faz 3 → User, Conversation, DmThread, Student* (kimlik + öğrenci verisi)
     *       Faz 6 → StudentPayment, BusinessContract*, CompanyFinanceEntry, PayoutSetting
     *       Faz 7 → Student* süreç modelleri, GuestFeedback
     *       Faz 9 → Manager*, Notification*, Task*, AiLabs*, ABTest, Automation*
     *
     * @var list<class-string>
     */
    private const BASELINE_MISSING = [
        'ABTest',
        'AiLabsResponseCache',
        'AiLabsSettings',
        'AuditTrail',
        'AutomationWorkflow',
        'BusinessContract',
        'BusinessContractTemplate',
        'CompanyBulletin',
        'CompanyFinanceEntry',
        'ConsentRecord',
        'Conversation',
        'CustomerHealthScore',
        'DataRetentionPolicy',
        'DigitalAssetActivityLog',
        'DmThread',
        'DocumentBuilderTemplate',
        'GuestFeedback',
        'IpAccessRule',
        'ManagerAlertRule',
        'ManagerPerformanceTarget',
        'ManagerReport',
        'ManagerScheduledReport',
        'MarketingTeam',
        'NotificationDispatch',
        'NotificationPreference',
        'PayoutSetting',
        'PlatformBroadcastRecipient',
        'PlatformInvoice',
        'PlatformPaymentMethod',
        'PromoCodeRedemption',
        'PromoPopup',
        'ScheduledNotification',
        'SeniorPerformanceTarget',
        'SeniorResponseTemplate',
        'StaffKpiTarget',
        'StudentAccommodation',
        'StudentAppointment',
        'StudentChecklist',
        'StudentFeedback',
        'StudentInstitutionDocument',
        'StudentLanguageCourse',
        'StudentMaterialRead',
        'StudentPayment',
        'StudentShipment',
        'StudentUniversityApplication',
        'StudentVisaApplication',
        'TaskTemplate',
        'TrialExtension',
        'User',
        'WebhookLog',
    ];

    public function test_no_new_model_skips_tenant_scope_declaration(): void
    {
        $missing = $this->modelsMissingScopeDeclaration();

        $unexpected = array_values(array_diff($missing, self::BASELINE_MISSING));

        $this->assertSame([], $unexpected, sprintf(
            "Bu modellerde `company_id` var ama tenant niyeti bildirilmemiş:\n  %s\n\n"
            . "Düzeltmek için modele şunlardan birini ekle:\n"
            . "  • use BelongsToCompany;        → veri şirkete ait, filtrelenmeli\n"
            . "  • use SharedAcrossCompanies;   → bilinçli olarak tüm şirketlerde ortak\n",
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
                || in_array(SharedAcrossCompanies::class, $traits, true);

            if (!$declared) {
                $missing[] = $ref->getShortName();
            }
        }

        sort($missing);

        return $missing;
    }
}
