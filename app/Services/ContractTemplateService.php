<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\GuestApplication;
use App\Models\MarketingAdminSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class ContractTemplateService
{
    /** @var array<int,Company|null> */
    private array $companyMemo = [];

    /** @var array<int,array<string,string>> */
    private array $settingsMemo = [];

    public function resolveActiveTemplate(int $companyId = 0): ContractTemplate
    {
        $row = ContractTemplate::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('is_active', true)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        if ($row) {
            return $row;
        }

        $companyId = $companyId > 0 ? $companyId : (app()->bound('current_company_id') ? (int) app('current_company_id') : 1);
        return $this->createDefaultTemplate($companyId);
    }

    /**
     * @return array{template_id:int,template_code:string,body_text:string,annex_kvkk_text:string,annex_commitment_text:string,annex_payment_text:string}
     */
    public function buildSnapshot(GuestApplication $guest, int $companyId = 0): array
    {
        $tpl = $this->resolveActiveTemplate($companyId > 0 ? $companyId : (int) ($guest->company_id ?: 0));
        $vars = $this->buildVariables($guest);

        return [
            'template_id'           => (int) $tpl->id,
            'template_code'         => (string) $tpl->code,
            'body_text'             => $this->renderText((string) $tpl->body_text, $vars),
            'annex_kvkk_text'       => $this->renderText((string) ($tpl->annex_kvkk_text ?? ''), $vars),
            'annex_commitment_text' => $this->renderText((string) ($tpl->annex_commitment_text ?? ''), $vars),
            'annex_payment_text'    => $this->renderText((string) ($tpl->annex_payment_text ?? ''), $vars),
        ];
    }

    /**
     * @return array{template_id:int,template_code:string,body_text:string,annex_kvkk_text:string,annex_commitment_text:string,annex_payment_text:string}
     */
    public function buildSnapshotCached(GuestApplication $guest, int $companyId = 0): array
    {
        $resolvedCompanyId = $companyId > 0 ? $companyId : (int) ($guest->company_id ?: 0);
        $tpl = $this->resolveActiveTemplate($resolvedCompanyId);
        $cacheKey = sprintf(
            'contract_snapshot:c%s:g%s:t%s:tu%s:gu%s',
            $resolvedCompanyId,
            (int) $guest->id,
            (int) $tpl->id,
            (string) optional($tpl->updated_at)?->timestamp,
            (string) optional($guest->updated_at)?->timestamp
        );

        /** @var array{template_id:int,template_code:string,body_text:string,annex_kvkk_text:string,annex_commitment_text:string,annex_payment_text:string} $snapshot */
        $snapshot = Cache::remember($cacheKey, now()->addSeconds(45), function () use ($guest, $resolvedCompanyId): array {
            return $this->buildSnapshot($guest, $resolvedCompanyId);
        });

        return $snapshot;
    }

    /**
     * @return array<string,string>
     */
    public function buildPreviewVariables(GuestApplication $guest): array
    {
        return $this->buildVariables($guest);
    }

    /**
     * @return array<string,string>
     */
    private function buildVariables(GuestApplication $guest): array
    {
        $companyId = (int) ($guest->company_id ?: (app()->bound('current_company_id') ? (int) app('current_company_id') : 0));
        $company   = $this->resolveCompany($companyId);
        $settings  = $this->resolveCompanyContractSettings($companyId);
        $draft     = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        // --- Temel bilgiler ---
        $fullName        = trim((string) ($guest->first_name ?? '') . ' ' . (string) ($guest->last_name ?? ''));
        $applicationType = trim((string) ($guest->application_type ?? ''));
        $typeLabel       = match (strtolower($applicationType)) {
            'bachelor'                    => 'Lisans',
            'master'                      => 'Yüksek Lisans',
            'ausbildung'                  => 'Mesleki Eğitim (Ausbildung)',
            'dil_kursu', 'language_course'=> 'Dil Kursu',
            'phd'                         => 'Doktora',
            default                       => $applicationType !== '' ? $applicationType : 'Lisans',
        };
        $educationLevel = $typeLabel . ' Eğitimi';

        // --- Paket ---
        $packageTitle  = trim((string) ($guest->selected_package_title ?? ''));
        $packagePrice  = trim((string) ($guest->selected_package_price ?? ''));
        $extraServices = collect(is_array($guest->selected_extra_services) ? $guest->selected_extra_services : [])
            ->map(fn ($x) => trim((string) ($x['title'] ?? '')))
            ->filter()
            ->values();
        $serviceScope = $packageTitle !== ''
            ? ('Paket: ' . $packageTitle . ($packagePrice !== '' ? ' (' . $packagePrice . ')' : ''))
            : 'Paket seçimi yok';
        if ($extraServices->isNotEmpty()) {
            $serviceScope .= ' | Ek hizmetler: ' . $extraServices->implode(', ');
        }

        // --- Öğrenci kişisel bilgileri (kayıt formu draft) ---
        $studentIdentityNo = trim((string) ($draft['passport_number'] ?? $draft['identity_no'] ?? '-'));
        $studentBirthDate  = trim((string) ($draft['birth_date'] ?? $draft['birthdate'] ?? '-'));
        $studentAddress    = trim((string) ($draft['home_address'] ?? $draft['address'] ?? '-'));

        // --- Yasal temsilci (18 altı) ---
        $guardianFullName   = trim((string) ($draft['guardian_name'] ?? $draft['guardian_full_name'] ?? '-'));
        $guardianIdentityNo = trim((string) ($draft['guardian_identity_no'] ?? '-'));
        $guardianRelation   = trim((string) ($draft['guardian_relation'] ?? '-'));

        // --- Sözleşme numarası ---
        $contractNumber = 'MDE-' . now()->format('Y') . '-' . str_pad((string) $guest->id, 6, '0', STR_PAD_LEFT);

        // --- Firma ayarları ---
        $advisorCompanyName      = (string) ($company?->name ?: 'MentorDE');
        $advisorCompanyAddress   = $settings['advisor_company_address'] ?? '';
        $advisorTaxInfo          = $settings['advisor_tax_info'] ?? '';
        $advisorAuthorizedPerson = $settings['advisor_authorized_person'] ?? '';
        $advisorPhone            = $settings['advisor_phone'] ?? '';
        $advisorEmail            = $settings['advisor_email'] ?? '';
        $advisorWebsite          = $settings['advisor_website'] ?? '';
        $jurisdictionCity        = $settings['jurisdiction_city'] ?? 'İstanbul';
        $taxStatus               = $settings['tax_status'] ?? 'hariç';

        // --- Ödeme taksit bilgileri ---
        $installment1Amount     = $settings['installment_1_amount'] ?? '-';
        $installment2Condition  = $settings['installment_2_condition'] ?? 'Üniversite başvuruları başlatıldığında';
        $installment2Amount     = $settings['installment_2_amount'] ?? '-';
        $installment3Condition  = $settings['installment_3_condition'] ?? 'Vize başvuru dosyası tamamlandığında';
        $installment3Amount     = $settings['installment_3_amount'] ?? '-';

        // --- Banka bilgileri ---
        $bankName   = $settings['bank_name'] ?? '-';
        $bankBranch = $settings['bank_branch'] ?? '-';
        $bankIban   = $settings['bank_iban'] ?? '-';

        // --- Paket detayları ---
        $maxUniversityCount = $settings['max_university_count'] ?? '5';

        // Ödeme planı (eski alan — geri uyumluluk)
        $paymentPlanText = $settings['payment_plan'] ?? '';
        if ($paymentPlanText === '' && ($installment1Amount !== '-' || $installment2Amount !== '-')) {
            $paymentPlanText = implode("\n", array_filter([
                $installment1Amount !== '-' ? "1. Peşinat: {$installment1Amount} — Sözleşme imza tarihinde" : '',
                $installment2Amount !== '-' ? "2. Taksit: {$installment2Amount} — {$installment2Condition}" : '',
                $installment3Amount !== '-' ? "3. Taksit: {$installment3Amount} — {$installment3Condition}" : '',
            ]));
        }

        return [
            // Sözleşme genel
            'contract_number'             => $contractNumber,
            'contract_date'               => now()->format('d.m.Y'),

            // Danışman firma
            'advisor_company_name'        => $advisorCompanyName,
            'advisor_company_address'     => $advisorCompanyAddress !== '' ? $advisorCompanyAddress : '-',
            'advisor_tax_info'            => $advisorTaxInfo !== '' ? $advisorTaxInfo : '-',
            'advisor_authorized_person'   => $advisorAuthorizedPerson !== '' ? $advisorAuthorizedPerson : '-',
            'advisor_phone'               => $advisorPhone !== '' ? $advisorPhone : '-',
            'advisor_email'               => $advisorEmail !== '' ? $advisorEmail : '-',
            'advisor_website'             => $advisorWebsite !== '' ? $advisorWebsite : '-',

            // Öğrenci
            'student_full_name'           => $fullName !== '' ? $fullName : '-',
            'student_id'                  => (string) ($guest->converted_student_id ?: ('GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT))),
            'student_email'               => (string) ($guest->email ?? '-'),
            'student_phone'               => (string) ($guest->phone ?? '-'),
            'student_identity_no'         => $studentIdentityNo,
            'student_birth_date'          => $studentBirthDate,
            'student_address'             => $studentAddress,

            // Yasal temsilci
            'guardian_full_name'          => $guardianFullName,
            'guardian_identity_no'        => $guardianIdentityNo,
            'guardian_relation'           => $guardianRelation,

            // Başvuru
            'application_country'         => (string) ($guest->application_country ?? 'Almanya'),
            'application_type'            => $typeLabel,
            'education_level'             => $educationLevel,

            // Paket ve hizmet
            'package_name'                => $packageTitle !== '' ? $packageTitle : '-',
            'service_total_price'         => $packagePrice !== '' ? $packagePrice : '-',
            'service_scope'               => $serviceScope,
            'extra_services'              => $extraServices->isNotEmpty() ? $extraServices->implode(', ') : '-',
            'max_university_count'        => $maxUniversityCount,

            // Ödeme
            'payment_plan'                => $paymentPlanText !== '' ? $paymentPlanText : '-',
            'tax_status'                  => $taxStatus,
            'installment_1_amount'        => $installment1Amount,
            'installment_2_date_or_condition' => $installment2Condition,
            'installment_2_amount'        => $installment2Amount,
            'installment_3_date_or_condition' => $installment3Condition,
            'installment_3_amount'        => $installment3Amount,

            // Banka
            'bank_name'                   => $bankName,
            'bank_branch'                 => $bankBranch,
            'bank_iban'                   => $bankIban,

            // Yetkili mahkeme
            'jurisdiction_city'           => $jurisdictionCity,
        ];
    }

    /**
     * Sözleşmeyi PDF olarak render edip binary string döndür.
     * barryvdh/laravel-dompdf gerektirir.
     */
    public function generatePdf(GuestApplication $guest, int $companyId = 0): string
    {
        $resolvedCompanyId = $companyId > 0 ? $companyId : (int) ($guest->company_id ?: 0);
        $snapshot = $this->buildSnapshotCached($guest, $resolvedCompanyId);

        $templateId  = (int) ($guest->contract_template_id ?? 0);
        $tpl = $templateId > 0
            ? ContractTemplate::find($templateId, ['print_header_html', 'print_footer_html'])
            : null;
        if (!$tpl) {
            $tpl = ContractTemplate::query()
                ->when($resolvedCompanyId > 0, fn ($q) => $q->forCompany($resolvedCompanyId))
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first(['print_header_html', 'print_footer_html']);
        }

        $printVars = $this->buildVariables($guest);
        $printHeaderHtml = $this->renderText((string) ($tpl?->print_header_html ?? ''), $printVars);
        $printFooterHtml = $this->renderText((string) ($tpl?->print_footer_html ?? ''), $printVars);

        $html = view('manager.contract-pdf', [
            'contractText'     => (string) ($snapshot['body_text'] ?? ''),
            'annexKvkkText'    => (string) ($snapshot['annex_kvkk_text'] ?? ''),
            'annexCommitText'  => (string) ($snapshot['annex_commitment_text'] ?? ''),
            'annexPaymentText' => (string) ($snapshot['annex_payment_text'] ?? ''),
            'guest'            => $guest,
            'contractStatus'   => (string) ($guest->contract_status ?? 'not_requested'),
            'generatedAt'      => $guest->contract_generated_at,
            'printHeaderHtml'  => $printHeaderHtml,
            'printFooterHtml'  => $printFooterHtml,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->output();
    }

    /**
     * @param array<string,string> $vars
     */
    public function renderText(string $text, array $vars): string
    {
        if ($text === '' || $vars === []) {
            return $text;
        }

        $replaceMap = [];
        foreach ($vars as $key => $value) {
            $replaceMap['{{' . $key . '}}'] = (string) $value;
        }

        return strtr($text, $replaceMap);
    }

    private function createDefaultTemplate(int $companyId): ContractTemplate
    {
        $body = <<<'TXT'
YURT DIŞI EĞİTİM DANIŞMANLIK SÖZLEŞMESİ
Sözleşme No: {{contract_number}}
Sözleşme Tarihi: {{contract_date}}

MADDE 1 — TARAFLAR
1.1. DANIŞMAN:
Unvan: {{advisor_company_name}}
Adres: {{advisor_company_address}}
Vergi Dairesi / No: {{advisor_tax_info}}
Yetkili: {{advisor_authorized_person}}
Telefon: {{advisor_phone}}
E-posta: {{advisor_email}}
Web: {{advisor_website}}
(Bundan sonra "Danışman" olarak anılacaktır.)

1.2. ÖĞRENCİ / YASAL TEMSİLCİ:
Ad Soyad: {{student_full_name}}
Öğrenci No: {{student_id}}
T.C. Kimlik / Pasaport No: {{student_identity_no}}
Doğum Tarihi: {{student_birth_date}}
Adres: {{student_address}}
Telefon: {{student_phone}}
E-posta: {{student_email}}
(Bundan sonra "Öğrenci" olarak anılacaktır.)

(Öğrenci 18 yaşından küçük ise bu sözleşmeyi yasal temsilcisi imzalar:)
Yasal Temsilci Ad Soyad: {{guardian_full_name}}
T.C. Kimlik No: {{guardian_identity_no}}
Öğrenci ile Yakınlık Derecesi: {{guardian_relation}}

MADDE 2 — SÖZLEŞMENİN KONUSU VE KAPSAMI
Bu sözleşme, Danışman tarafından Öğrenci'nin {{application_type}} kapsamında Almanya'da {{education_level}} alması amacıyla başvuru ve danışmanlık süreçlerinin yürütülmesini düzenler. Danışman'ın bu sözleşme kapsamındaki yükümlülüğü, başvuru sürecini profesyonel standartlarda yönetmek olup; üniversite kabulü veya vize onayı alınmasına yönelik herhangi bir sonuç garantisi içermemektedir.

MADDE 3 — DANIŞMANLIK HİZMETLERİ
Danışman, seçilen paket kapsamında aşağıdaki başvuru yönetimi hizmetlerini sunar:

3.1. Üniversite Başvuru Süreci
a) Öğrencinin profiline uygun üniversite ve programların araştırılması.
b) Başvurulacak üniversitelere yönelik belgelerin usulüne uygun düzenlenmesi ve başvuruların yapılması.
c) Başvuru süreçlerinin ve kurumlarla iletişimin takip edilmesi.

3.2. Vize Başvuru Süreci
a) Vize türüne göre gerekli belge listesinin öğrenciye iletilmesi ve dosya hazırlığına destek olunması.
b) Vize görüşmesi süreçleri hakkında bilgilendirme yapılması.
c) Bloke hesap açılışı prosedürlerinde rehberlik sağlanması.

3.3. Konaklama ve Dil Kursu Rehberliği
a) Almanya'daki yurt ve konaklama seçenekleri hakkında bilgilendirme yapılması.
b) İhtiyaç halinde uygun dil kursu alternatiflerinin sunulması.

3.4. Seçilen Hizmet Paketi ve Ek Hizmetler
Seçilen Paket: {{package_name}}
Paket Kapsamı: {{service_scope}}
Ek Hizmetler (varsa): {{extra_services}}
(Sadece seçilen paket kapsamına dahil olan hizmetler ifa edilecektir.)

MADDE 4 — HİZMET BEDELİ VE ÖDEME ŞARTLARI
4.1. Danışmanlık hizmet bedeli toplamı {{service_total_price}} olarak belirlenmiştir.
4.2. Ödeme planı aşağıdaki şekildedir:
{{payment_plan}}
4.3. Belirtilen hizmet bedeline öğrencinin vize harcı, sigorta primi, uçak bileti, okul harçları, konaklama, yeminli tercüme, noter, kargo ve benzeri üçüncü taraf masrafları dahil değildir. Tüm bu masraflar Öğrenci'ye aittir.
4.4. Öğrenci, vadesi gelen ödemeyi en geç 5 (beş) iş günü içinde yapmakla yükümlüdür. Gecikme yaşanması halinde Danışman, önceden ihtara gerek kalmaksızın hizmetleri durdurma veya sözleşmeyi tek taraflı haklı nedenle feshetme hakkına sahiptir. Bu durumda o ana kadar yapılan ödemeler iade edilmez.

MADDE 5 — ÖĞRENCİ'NİN YÜKÜMLÜLÜKLERİ
5.1. Danışman tarafından talep edilen belgeleri zamanında, eksiksiz ve gerçeğe uygun şekilde teslim etmek. Belgelerin geç veya hatalı tesliminden doğacak hak kayıplarından Danışman sorumlu tutulamaz.
5.2. Hizmet bedellerini sözleşmede belirtilen tarihlerde eksiksiz ödemek.
5.3. Danışman'ın yönlendirmelerine ve belirlediği takvime uymak.

MADDE 6 — DANIŞMAN'IN YÜKÜMLÜLÜKLERİ
6.1. Danışman, başvuru sürecini bilgi ve tecrübesi doğrultusunda, özen yükümlülüğü çerçevesinde yürütecektir.
6.2. Danışman, Öğrenci'nin sunduğu belgeleri yalnızca başvuru işlemleri için kullanacak ve yetkili kurumlar dışında üçüncü kişilerle paylaşmayacaktır.

MADDE 7 — SÖZLEŞMENİN SÜRESİ
7.1. Bu sözleşme, imzalandığı tarihte yürürlüğe girer ve seçilen paketteki hizmetlerin tamamlanması, üniversite/vize başvuru sonuçlarının Öğrenci'ye iletilmesi veya sözleşmenin feshi ile sona erer.

MADDE 8 — SÖZLEŞMENİN FESHİ
8.1. Öğrenci Tarafından Fesih
Öğrenci sözleşmeyi tek taraflı olarak feshedebilir. Ancak bu durumda, fesih anına kadar yapılan ödemeler Danışman'ın harcadığı mesai ve danışmanlık hizmetinin karşılığı olarak kabul edilir ve kesinlikle iade edilmez. Kalan bakiye varsa muaccel hale gelir.

8.2. Danışman Tarafından Fesih
Danışman; Öğrenci'nin ödeme yükümlülüklerini aksatması, istenen belgeleri zamanında temin etmemesi, sahte belge sunması veya kurumlara yanıltıcı beyanda bulunması durumlarında sözleşmeyi derhal ve tek taraflı olarak feshedebilir. Bu durumda herhangi bir ücret iadesi yapılmaz ve Danışman'ın maddi tazminat hakkı saklıdır.

MADDE 9 — BAŞVURU SONUÇLARI VE ÜCRET İADESİ DURUMU
9.1. Üniversite Kabulü veya Vize Reddi Durumu
Danışman'ın temel yükümlülüğü başvuru dosyalarının eksiksiz hazırlanması ve kurumlara iletilmesidir. Üniversitelerin kabul kriterleri, kontenjan durumları veya konsoloslukların vize kararları tamamen ilgili makamların bağımsız inisiyatifindedir. Bu nedenle, üniversitelerden kabul alınamaması, başvurunun reddedilmesi veya vize başvurusunun olumsuz sonuçlanması durumunda Danışman hizmetini ifa etmiş sayılır ve Öğrenci'ye herhangi bir ücret iadesi yapılmaz.
9.2. Öğrenci'nin eksik, hatalı bilgi vermesi veya mülakatlarda başarısız olması nedeniyle yaşanacak olumsuzluklarda Danışman'ın hiçbir hukuki veya mali sorumluluğu bulunmamaktadır.

MADDE 10 — SORUMLULUK SINIRI VE GÜVENCE KAPSAMI
10.1. Danışman, hiçbir resmi kurumun (üniversite, göçmenlik bürosu, konsolosluk vb.) karar organı veya temsilcisi değildir. Alınacak kararlar üzerinde hiçbir yaptırımı veya taahhüdü yoktur.
10.2. Üniversitelerin veya resmi kurumların kendi iç işleyişlerinden kaynaklanan gecikmeler, ani kural değişiklikleri veya sistem hatalarından Danışman sorumlu tutulamaz.

MADDE 11 — KİŞİSEL VERİLERİN KORUNMASI (KVKK / DSGVO)
Öğrenci, Danışman'ın başvuru süreçlerini yürütebilmesi amacıyla kişisel ve özel nitelikli kişisel verilerini işlemesine ve yurt dışındaki ilgili kurumlara aktarmasına açık rıza gösterir. Ayrıntılar Ek-1'de yer almaktadır.

MADDE 12 — GİZLİLİK
Taraflar, sözleşme kapsamında edindikleri ticari ve kişisel bilgileri gizli tutmayı kabul eder.

MADDE 13 — UYUŞMAZLIK ÇÖZÜMÜ
İşbu sözleşmeden doğabilecek uyuşmazlıklarda Türkiye Cumhuriyeti kanunları uygulanacak olup, {{jurisdiction_city}} Mahkemeleri ve İcra Daireleri yetkilidir.

MADDE 14 — YÜRÜRLÜK
Toplam 14 (on dört) maddeden oluşan işbu sözleşme, taraflarca okunarak iradelerine uygun bulunmuş ve imzalanarak yürürlüğe girmiştir.

DANIŞMAN
Unvan: {{advisor_company_name}}
Yetkili: {{advisor_authorized_person}}
Tarih: {{contract_date}}
İmza: ________________________

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        $annexKvkk = <<<'TXT'
EK-1: KİŞİSEL VERİLERİN İŞLENMESİNE İLİŞKİN AYDINLATMA VE AÇIK RIZA METNİ (KVKK & DSGVO)

1. VERİ SORUMLUSUNUN KİMLİĞİ
6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") ve Avrupa Birliği Genel Veri Koruma Tüzüğü ("DSGVO/GDPR") uyarınca, kişisel verileriniz veri sorumlusu sıfatıyla {{advisor_company_name}} tarafından aşağıda açıklanan kapsamda işlenebilecektir.

2. İŞLENEN KİŞİSEL VERİLERİNİZ
Yurt dışı eğitim danışmanlık hizmetlerinin yürütülebilmesi amacıyla aşağıdaki veri kategorileri işlenmektedir:

Kimlik ve Pasaport Bilgileri: Ad, soyad, T.C. Kimlik No, doğum tarihi, pasaport kopyası, uyruk vb.
İletişim Bilgileri: Telefon numarası, e-posta adresi, ikamet adresi.
Eğitim ve Akademik Bilgiler: Diploma, transkript, dil yeterlilik belgeleri, niyet mektupları, CV, referans mektupları.
Finansal Bilgiler: Vize ve bloke hesap süreçleri için gereken banka hesap dökümleri, sponsor bilgileri, ödeme dekontları.
Özel Nitelikli Kişisel Veriler: Vize ve sağlık sigortası işlemleri için zorunlu olması halinde sağlık raporları, biyometrik veriler veya adli sicil kaydı.

3. KİŞİSEL VERİLERİN İŞLENME AMACI
Almanya'daki üniversitelere, dil kurslarına ve Uni-Assist gibi başvuru portallarına kayıt işlemlerinin yapılması; konsolosluklar nezdinde vize başvuru dosyalarının hazırlanması; bloke hesap (Sperrkonto) açılışı ve zorunlu sağlık sigortası işlemlerinin yürütülmesi; konaklama ve yurt başvurularının yapılması; muhasebe/faturalandırma süreçlerinin yönetilmesi amaçlarıyla işlenmektedir.

4. KİŞİSEL VERİLERİN AKTARILMASI VE YURT DIŞINA AKTARIM
Verileriniz yalnızca yukarıda belirtilen amaçlar doğrultusunda; Almanya'daki eğitim kurumlarına, Alman Konsolosluklarına/Büyükelçiliklerine, Yabancılar Dairesine (Ausländerbehörde), sağlık sigortası şirketlerine, bloke hesap hizmeti sunan finansal kuruluşlara ve resmi makamlara aktarılacaktır.

5. KİŞİSEL VERİ TOPLAMANIN YÖNTEMİ VE HUKUKİ SEBEBİ
Kişisel verileriniz, Danışmanlık Sözleşmesi'nin kurulması ve ifası (KVKK m.5/2-c), veri sorumlusunun hukuki yükümlülüğünü yerine getirmesi (KVKK m.5/2-ç) ve meşru menfaatlerimiz (KVKK m.5/2-f) hukuki sebeplerine dayanarak toplanmaktadır. Sağlık bilgileri ve yurt dışına aktarım ise yalnızca açık rızanıza istinaden işlenir.

6. İLGİLİ KİŞİNİN HAKLARI (KVKK Madde 11 ve DSGVO)
Verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltilmesini veya silinmesini isteme haklarına sahipsiniz.

AÇIK RIZA VE ONAY BEYANI
Yukarıda yer alan Aydınlatma Metni'ni okudum, anladım ve haklarım konusunda bilgilendirildim.

Bu kapsamda; kimlik, iletişim, eğitim ve finansal verilerimin yurt dışındaki ilgili kurumlara aktarılmasına ve zorunlu olması halinde özel nitelikli kişisel verilerimin işlenmesine özgür irademle açıkça rıza gösteriyorum.

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        $annexCommitment = <<<'TXT'
EK-2: SEÇİLEN HİZMET PAKETİ DETAYI VE KAPSAM LİSTESİ

Paket Adı: {{package_name}}

1. Kapsama Dahil Olan Hizmetler:

Akademik Planlama: Öğrencinin akademik geçmişine uygun en fazla {{max_university_count}} adet üniversite/bölüm tespit edilmesi ve başvuru takviminin oluşturulması.

Evrak Yönetimi: Üniversite başvuru evrak listesinin iletilmesi, motivasyon mektubu ve CV taslaklarının formata uygunluğunun kontrol edilmesi.

Başvuru İşlemleri: Belirlenen üniversitelere veya doğrudan Uni-Assist sistemi üzerinden başvuruların gerçekleştirilmesi ve yazışmaların yürütülmesi.

Vize Danışmanlığı: Almanya Ulusal Öğrenci/Dil Kursu Vizesi için gerekli güncel evrak listesinin temin edilmesi, başvuru dosyasının kontrolü ve vize randevu alım sürecinde rehberlik sağlanması.

Bloke Hesap Rehberliği: Almanya'nın talep ettiği bloke hesap (Sperrkonto) açılışı ve zorunlu sağlık sigortası başvuru süreçlerinde bilgi aktarımı ve yönlendirme.

2. Kapsam Dışında Kalan Hizmetler ve Masraflar (Öğrenciye Ait Olanlar):

Üniversitelerin talep ettiği başvuru harçları ve Uni-Assist işlem ücretleri.
Vize harcı, iDATA/VFS Global hizmet bedelleri.
Noter tasdiki, apostil işlemleri ve yeminli tercüme masrafları.
Seyahat sağlık sigortası ve bloke hesap açılış/aylık işletim ücretleri.
Uçak bileti, Almanya'daki konaklama depozitoları, kira bedelleri ve genel yaşam masrafları.

Danışman, yukarıda sayılan üçüncü taraf kurumların talep ettiği ücretlerden, bu kurumlardan kaynaklanan ret kararlarından veya süreç gecikmelerinden sorumlu tutulamaz.

DANIŞMAN
Unvan: {{advisor_company_name}}
Tarih: {{contract_date}}
İmza: ________________________

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        $annexPayment = <<<'TXT'
EK-3: ÖDEME PLANI DETAYI VE BANKA BİLGİLERİ

1. Toplam Hizmet Bedeli: {{service_total_price}} (KDV {{tax_status}})

2. Taksit ve Ödeme Tablosu:

1. Peşinat — Sözleşme İmza Tarihinde — {{installment_1_amount}}
   Dosya açılış, ön değerlendirme ve planlama bedeli.

2. Taksit — {{installment_2_date_or_condition}} — {{installment_2_amount}}
   Üniversite başvurularının başlatılması aşamasında.

3. Taksit — {{installment_3_date_or_condition}} — {{installment_3_amount}}
   Vize başvuru dosyasının teslimi / tamamlanması aşamasında.

3. Banka Hesap Bilgileri:
Hesap Sahibi: {{advisor_company_name}}
Banka Adı: {{bank_name}}
Şube / Şube Kodu: {{bank_branch}}
IBAN: {{bank_iban}}
Açıklama: {{student_full_name}} - {{contract_number}}

4. Ödeme Şartlarına İlişkin İhtar:
Ana sözleşmenin 4.4. maddesi gereğince; vadesi gelen taksit ödemelerinin belirtilen tarihlerde ve eksiksiz olarak yukarıdaki hesaba yatırılması zorunludur. Ödemelerde yaşanacak 5 (beş) iş gününü aşan gecikmelerde Şirket, başvuru süreçlerini derhal durdurma ve sözleşmeyi tek taraflı feshetme hakkına sahiptir.

DANIŞMAN
Unvan: {{advisor_company_name}}
Tarih: {{contract_date}}
İmza: ________________________

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        return ContractTemplate::query()->create([
            'company_id'            => $companyId > 0 ? $companyId : 1,
            'code'                  => 'yurt_disi_egitim_v2',
            'name'                  => 'Yurt Dışı Eğitim Danışmanlık Sözleşmesi v2',
            'version'               => 2,
            'is_active'             => true,
            'body_text'             => $body,
            'annex_kvkk_text'       => $annexKvkk,
            'annex_commitment_text' => $annexCommitment,
            'annex_payment_text'    => $annexPayment,
            'notes'                 => 'Profesyonel şablon. Tüm {{placeholder}} değerleri sistem ve firma ayarlarından otomatik doldurulur.',
        ]);
    }

    /**
     * @return array<string,string>
     */
    private function loadCompanyContractSettings(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $keys = [
            'advisor_company_address',
            'advisor_tax_info',
            'advisor_authorized_person',
            'advisor_phone',
            'advisor_email',
            'advisor_website',
            'jurisdiction_city',
            'payment_plan',
            'tax_status',
            'bank_name',
            'bank_branch',
            'bank_iban',
            'installment_1_amount',
            'installment_2_condition',
            'installment_2_amount',
            'installment_3_condition',
            'installment_3_amount',
            'max_university_count',
        ];

        $rows = MarketingAdminSetting::query()
            ->forCompany($companyId)
            ->whereIn('setting_key', $keys)
            ->get(['setting_key', 'setting_value']);

        $out = [];
        foreach ($rows as $row) {
            $value = '';
            $raw   = $row->setting_value;
            if (is_array($raw)) {
                $value = (string) ($raw['value'] ?? $raw['text'] ?? $raw['tr'] ?? '');
            } else {
                $value = (string) $raw;
            }
            $out[(string) $row->setting_key] = trim($value);
        }

        return $out;
    }

    private function resolveCompany(int $companyId): ?Company
    {
        if (array_key_exists($companyId, $this->companyMemo)) {
            return $this->companyMemo[$companyId];
        }

        $company = Company::query()
            ->when($companyId > 0, fn ($q) => $q->where('id', $companyId))
            ->first();

        $this->companyMemo[$companyId] = $company;

        return $company;
    }

    /**
     * @return array<string,string>
     */
    private function resolveCompanyContractSettings(int $companyId): array
    {
        if (array_key_exists($companyId, $this->settingsMemo)) {
            return $this->settingsMemo[$companyId];
        }

        $rows = $this->loadCompanyContractSettings($companyId);
        $this->settingsMemo[$companyId] = $rows;

        return $rows;
    }
}
