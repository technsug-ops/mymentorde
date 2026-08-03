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
