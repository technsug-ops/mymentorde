<?php

namespace Tests\Feature\Tenancy;

use App\Mail\PasswordResetByManagerMail;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Şifre sıfırlama maili partnerin markasıyla gitmeli.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Şablonda üç yerde MentorDE kimliği sabit yazılıydı: başlıktaki varsayılan,
 * destek adresi (info@panel.mentorde.com) ve alttaki config('app.url').
 * Partner firmanın öğrencisine giden mailde MentorDE görünüyordu.
 *
 * ⚠ İNCE NOKTA: `config('brand.name', 'MentorDE')` gibi bir varsayılan burada
 * KORUMAZ. Brand, partner firmalarda kimlik alanlarını BOŞ METNE çeviriyor;
 * boş metin de "değer var" sayıldığı için varsayılan hiç devreye girmiyor.
 * Bu yüzden şablonda ?: kullanılıyor — test o davranışı sabitliyor.
 */
class PasswordResetMailBrandTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function renderFor(string $loginUrl): string
    {
        $mail = new PasswordResetByManagerMail(
            name: 'Test Ogrenci',
            email: 'ogrenci@example.test',
            tempPassword: 'GECICI-1234',
            loginUrl: $loginUrl,
        );

        return $mail->render();
    }

    public function test_partner_mail_carries_no_platform_identity(): void
    {
        $this->companyB->update(['brand_name' => 'Novavia Yurtdisi']);

        Brand::apply($this->companyB->fresh());

        $html = $this->renderFor('https://yourgermanuni.com/login');

        $this->assertStringContainsString('Novavia Yurtdisi', $html, 'Partner markasi mailde yok.');

        // Sabit yazılı platform kimliği hiçbir yerde geçmemeli.
        $this->assertStringNotContainsString('info@panel.mentorde.com', $html, 'Platform destek adresi sizdi.');
        $this->assertStringNotContainsString('panel.mentorde.com', $html, 'Platform adresi sizdi.');
    }

    /** Alt bilgideki adres isteğin geldiği alan adından üretilmeli. */
    public function test_footer_uses_the_partner_host(): void
    {
        $this->companyB->update(['brand_name' => 'Novavia Yurtdisi']);

        Brand::apply($this->companyB->fresh());

        $html = $this->renderFor('https://yourgermanuni.com/login');

        $this->assertStringContainsString('https://yourgermanuni.com', $html);
    }

    /** Marka adı boş kalsa bile başlık boş görünmemeli. */
    public function test_brand_name_never_renders_empty(): void
    {
        Brand::apply($this->companyB->fresh());

        $html = $this->renderFor('https://yourgermanuni.com/login');

        $this->assertStringNotContainsString('<h1></h1>', $html, 'Marka adi bos basildi.');
    }
}
