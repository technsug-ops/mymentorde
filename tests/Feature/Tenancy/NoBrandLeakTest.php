<?php

namespace Tests\Feature\Tenancy;

use App\Mail\WelcomeStudentMail;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * MARKA SIZINTI BEKÇİSİ
 *
 * White-label tenant'a ait bir sayfada/mailde "MentorDE" geçmesi, kullanıcının
 * açık isteğinin ihlalidir: B2B partner firmalar MentorDE adını hiçbir yerde
 * görmemeli.
 *
 * Kod tabanında 139 satırda çıplak "MentorDE" var. Hepsini elle takip etmek
 * yerine bu test davranışı ölçer: tenant markası tanımlıyken çıktıda MentorDE
 * görünüyorsa sızıntı vardır. Yeni eklenen hardcode'u da yakalar.
 */
class NoBrandLeakTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private const LEAK_PATTERNS = ['MentorDE', 'mentorde.com', '@mentorde'];

    protected function setUpTenantBrand(): void
    {
        $this->companyB->update([
            'brand_name' => 'YourGermanUni',
            'primary_domain' => 'yourgermanuni.test',
            'brand_overrides' => [
                'email' => 'info@yourgermanuni.test',
                'support_email' => 'destek@yourgermanuni.test',
                'tagline' => 'Almanya Eğitim Danışmanlığı',
            ],
        ]);

        Brand::flushCache((int) $this->companyB->id);
    }

    public function test_tenant_login_page_does_not_leak_the_platform_brand(): void
    {
        $this->setUpTenantBrand();

        $html = $this->get('http://yourgermanuni.test/login')->assertOk()->getContent();

        foreach (self::LEAK_PATTERNS as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $html,
                "Tenant login sayfasında platform markası sızdı: '{$needle}'"
            );
        }

        $this->assertStringContainsString('YourGermanUni', $html);
    }

    /**
     * ŞİFRE AKIŞI SAYFALARI — canlıda yakalanan sızıntı: yourgermanuni.com/
     * forgot-password sabit "MentorDE" logosu basıyordu (logo yoksa @else dalı
     * hardcode'a düşüyordu). Reset sayfasında logo tamamen sabitti.
     */
    public function test_tenant_password_pages_do_not_leak_the_platform_brand(): void
    {
        $this->setUpTenantBrand();

        $pages = [
            'http://yourgermanuni.test/forgot-password',
            'http://yourgermanuni.test/reset-password/fake-token-abc123',
        ];

        foreach ($pages as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            foreach (self::LEAK_PATTERNS as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $html,
                    "Tenant şifre sayfasında platform markası sızdı ({$url}): '{$needle}'"
                );
            }

            $this->assertStringContainsString('YourGermanUni', $html, "Tenant markası basılmadı: {$url}");
        }
    }

    /**
     * Şifre sıfırlama MAİLİ tenant markasıyla çıkmalı. Default Laravel
     * ResetPassword İngilizce + platform adıyla çıkıyordu; ResetPasswordTr
     * config('brand.name') okur, vendor şablon başlığı da app.name üzerinden
     * markaya bağlandı (Brand::apply).
     */
    public function test_password_reset_mail_uses_the_tenant_brand(): void
    {
        $this->setUpTenantBrand();

        Brand::apply($this->companyB->fresh());

        $user = User::create([
            'name' => 'Reset Kullanıcı',
            'email' => 'reset@yourgermanuni.test',
            'password' => Hash::make('secret-password'),
            'role' => User::ROLE_STUDENT,
            'company_id' => $this->companyB->id,
        ]);

        $mail = (new \App\Notifications\ResetPasswordTr('fake-token'))->toMail($user);

        $this->assertStringContainsString('YourGermanUni', (string) $mail->subject);
        $this->assertStringNotContainsString('MentorDE', (string) $mail->subject);
        $this->assertStringNotContainsString('MentorDE', (string) $mail->salutation);

        // Vendor mail şablonunun başlık/alt bilgisi app.name okur — o da markaya
        // bağlanmış olmalı, yoksa konu doğru ama başlık "MentorDE" çıkar.
        $this->assertSame('YourGermanUni', config('app.name'));
    }

    /**
     * Reset maili KULLANICININ FİRMASININ markasıyla çıkmalı — isteğin geldiği
     * host'un markasıyla değil. Canlıda yakalandı: ortak portal domaininden
     * (yourgermanuni.com) istenince alt firmanın kullanıcısına portalın (hatta
     * stock bildirimde platformun) kimliğiyle mail gidiyordu.
     *
     * Burada tersi kurgulanır: platform host'undan istek, companyB kullanıcısı →
     * mail yine companyB markasıyla çıkmalı. Event üzerinden GERÇEK gönderilen
     * mesaj denetlenir (array transport'un mesaj listesi Brand::restore'un
     * forgetMailers çağrısıyla düştüğü için oradan okunamaz).
     */
    public function test_password_reset_mail_is_branded_for_the_users_own_company(): void
    {
        $this->setUpTenantBrand();

        $user = User::create([
            'name' => 'Firma Kullanıcısı',
            'email' => 'firma-reset@yourgermanuni.test',
            'password' => Hash::make('secret-password'),
            'role' => User::ROLE_STUDENT,
            'company_id' => $this->companyB->id,
        ]);

        \Illuminate\Support\Facades\Event::fake([\Illuminate\Mail\Events\MessageSent::class]);

        // Platform host'undan istek — marka bağlamı kasıtlı olarak YANLIŞ şirkette.
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();

        \Illuminate\Support\Facades\Event::assertDispatched(
            \Illuminate\Mail\Events\MessageSent::class,
            function ($event): bool {
                $message = $event->sent->getOriginalMessage();
                $subject = (string) $message->getSubject();
                $html    = (string) $message->getHtmlBody();

                return str_contains($subject, 'YourGermanUni')
                    && !str_contains($subject, 'MentorDE')
                    && str_contains($html, 'YourGermanUni')
                    && !str_contains($html, 'MentorDE');
            }
        );

        // İade kontrolü: istek platform markasıyla sürmeli, firma markası kalmamalı.
        $this->assertNotSame('YourGermanUni', config('brand.name'));
    }

    /**
     * NotificationMail şablonu (başvuru alındı, belge talebi... tüm sistem
     * mailleri) — alt bilgideki link config('app.url') basıyordu, yani partner
     * mailinin altında panel.mentorde.com çıkıyordu.
     */
    public function test_notification_mail_template_does_not_leak_the_platform_brand(): void
    {
        $this->setUpTenantBrand();

        Brand::apply($this->companyB->fresh());

        $html = view('mail.notification', [
            'mailSubject' => 'Test Konu',
            'mailBody'    => 'Test gövde metni.',
        ])->render();

        foreach (self::LEAK_PATTERNS as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $html,
                "Tenant sistem mailinde platform markası sızdı: '{$needle}'"
            );
        }

        $this->assertStringContainsString('YourGermanUni', $html);
    }

    public function test_welcome_mail_subject_uses_the_tenant_brand(): void
    {
        $this->setUpTenantBrand();

        // Mail, tenant bağlamında üretiliyor
        Brand::apply($this->companyB->fresh());

        $student = User::create([
            'name' => 'Test Öğrenci',
            'email' => 'ogrenci@yourgermanuni.test',
            'password' => Hash::make('secret-password'),
            'role' => User::ROLE_STUDENT,
            'company_id' => $this->companyB->id,
        ]);

        $subject = (new WelcomeStudentMail($student))->envelope()->subject;

        $this->assertStringContainsString('YourGermanUni', (string) $subject);
        $this->assertStringNotContainsString('MentorDE', (string) $subject);
    }

    /**
     * PANEL İÇİ sızıntı — login sayfası temiz olsa bile kullanıcı içeri girdiğinde
     * marka sızabilir. Staging'de tam olarak bu yaşandı: B firmasının panelinde
     * MentorDE adı görünüyordu (doc-request-modal'ın öğrenciye gönderdiği
     * WhatsApp/e-posta metni ve içerik partial'larındaki sabit ifadeler).
     */
    public function test_tenant_panel_pages_do_not_leak_the_platform_brand(): void
    {
        $this->setUpTenantBrand();

        $this->companyB->update([
            'subscription_tier' => 'gold',
            'enabled_modules' => ['core', 'marketing_admin', 'doc_request'],
        ]);

        $admin = $this->userFor($this->companyB, User::ROLE_MARKETING_ADMIN);

        $html = $this->actingAs($admin)->get('/mktg-admin/dashboard')->assertOk()->getContent();

        // Teknik anahtarlar (mentorde_dark, mentorde-theme-css, panel.mentorde.com
        // linki) marka değil — yalnızca kullanıcıya GÖRÜNEN "MentorDE" aranıyor.
        $visible = preg_replace('/mentorde[_-][a-z-]+|panel\.mentorde\.com/i', '', $html);

        $this->assertStringNotContainsString(
            'MentorDE',
            (string) $visible,
            'Tenant panelinde platform markası görünüyor.'
        );
    }

    /**
     * Platform sahibinin KENDİ sayfaları etkilenmemeli — panel.mentorde.com'da
     * MentorDE markası görünmeye devam etmeli. (Regresyon: marka çözümlemesi
     * varsayılan şirketi de bozmasın.)
     */
    public function test_default_brand_still_renders_for_the_platform_itself(): void
    {
        $this->setUpTenantBrand();

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'YourGermanUni',
            $html,
            'Varsayılan domainde tenant markası görünüyor — marka çözümlemesi sızdırıyor.'
        );
    }
}
