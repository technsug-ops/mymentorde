<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PolicyDocument;
use Illuminate\Database\Seeder;

/**
 * MentörDE legal içerikleri (TR) — kullanıcı 29 Nisan 2026'da verdi.
 * Manager paneli üzerinden düzenlenebilir; bu seeder yalnızca initial content.
 *
 * Kapsam:
 *  - Kullanım Koşulları (terms)
 *  - Gizlilik Politikası (privacy)
 *  - İmpressum / Künye (imprint)
 */
class PolicyDocumentTrSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->select('id')->get();
        if ($companies->isEmpty()) return;

        $docs = $this->documents();

        foreach ($companies as $company) {
            foreach ($docs as $doc) {
                PolicyDocument::query()
                    ->updateOrCreate(
                        ['company_id' => $company->id, 'kind' => $doc['kind'], 'locale' => 'tr'],
                        ['title' => $doc['title'], 'body' => $doc['body']]
                    );
            }
        }

        $this->command?->info('PolicyDocumentTrSeeder: ' . (count($docs) * $companies->count()) . ' kayıt seedlendi.');
    }

    /**
     * @return array<int,array{kind:string,title:string,body:string}>
     */
    private function documents(): array
    {
        return [
            ['kind' => 'terms',   'title' => 'Kullanım Koşulları',     'body' => $this->termsBody()],
            ['kind' => 'privacy', 'title' => 'Gizlilik Politikası',    'body' => $this->privacyBody()],
            ['kind' => 'imprint', 'title' => 'Künye / Impressum',      'body' => $this->imprintBody()],
        ];
    }

    private function termsBody(): string
    {
        return <<<'HTML'
<p><em>Son güncelleme: 20 Nisan 2026</em></p>

<p>MentorDE platformunu kullanarak aşağıdaki koşulları kabul etmiş sayılırsınız. Koşulları kabul etmiyorsanız lütfen platformu kullanmayınız.</p>

<blockquote><strong>Özet:</strong> Hizmet, Almanya'da eğitim başvurusu sürecinde danışmanlık sağlar. Başvurunun kabul edilmesi veya vize alınması garanti değildir. Ücretler sözleşmede belirtilir. Platformu kötüye kullanmak yasaktır.</blockquote>

<h3>1. Taraflar ve Tanımlar</h3>
<p>Bu koşullar; MentorDE (bundan sonra "Platform") ile platformu kullanan gerçek veya tüzel kişi (bundan sonra "Kullanıcı") arasındaki ilişkiyi düzenler.</p>

<h3>2. Hizmetin Kapsamı</h3>
<ul>
    <li>Almanya'da üniversite / dil okulu / Ausbildung başvuruları için danışmanlık</li>
    <li>Belge hazırlama, çeviri yönlendirmesi, başvuru dosyası yönetimi</li>
    <li>Vize süreci rehberliği, konut ve seyahat organizasyonu yönlendirmesi</li>
    <li>Platform üzerinden danışmanla iletişim, randevu, belge yükleme</li>
</ul>
<p><strong>Garanti edilmeyen hususlar:</strong> Üniversite kabulü, vize onayı, başvuru portal ücretleri, konsolosluk harçları, yurt dışı sigorta primleri — bunlar kullanıcı sorumluluğundadır ve danışmanlık ücretinin dışındadır.</p>

<h3>3. Hesap Oluşturma ve Güvenliği</h3>
<ul>
    <li>Hesap açmak için 16 yaş ve üzerinde olmalısınız.</li>
    <li>Verdiğiniz bilgilerin doğru ve güncel olmasından sorumlusunuz.</li>
    <li>Şifrenizin gizliliği ve hesabınızın güvenliği size aittir. Yetkisiz erişim şüphesinde hemen bildirin.</li>
    <li>Bir kullanıcı yalnızca tek bir kişisel hesap açabilir.</li>
</ul>

<h3>4. Ücretler ve Ödeme</h3>
<ul>
    <li>Danışmanlık ücretleri ayrı bir sözleşmede yazılı olarak belirlenir.</li>
    <li>Ödemeler Stripe üzerinden veya havale ile alınır. Dijital ödemelerde 3D Secure zorunludur.</li>
    <li>Ödemesi yapılmayan süreçlerde hizmet duraklatılabilir; sözleşmenin 30 günden fazla askıda kalması durumunda fesih işletilebilir.</li>
    <li>Üçüncü taraf ücretleri (üniversite başvuru ücreti, vize harcı, çeviri, noter, APS vb.) kullanıcı tarafından karşılanır.</li>
</ul>

<h3>5. İptal ve İade</h3>
<ul>
    <li><strong>14 günlük cayma hakkı:</strong> Mesafeli sözleşmeler kanununa göre dijital hizmet başlamadan önce 14 gün içinde cayma hakkınız vardır.</li>
    <li>Hizmet başladıktan sonra yapılan iptallerde, o tarihe kadar sunulan hizmetin bedeli kesilir; kalan tutar iade edilir.</li>
    <li>Üçüncü taraf ücretleri (vize harcı, çeviri ücreti vs.) iade kapsamına girmez.</li>
    <li>İade talepleri 14 iş günü içinde ödeme yapılan kanala geri yansıtılır.</li>
</ul>

<h3>6. Kullanıcı Yükümlülükleri</h3>
<ul>
    <li>Doğru ve gerçek belgeler yüklemek. Sahte belge yüklemek başvurunuzu ve sözleşmeyi tek taraflı feshetme sebebidir ve yasal takibe konu olabilir.</li>
    <li>Danışmanla iletişimde saygı kurallarına uymak; taciz, hakaret, ırkçı/cinsiyetçi davranış yasaktır.</li>
    <li>Platformun güvenliğini tehdit eden eylemler (sızma, ddos, otomatik veri çekme) kesinlikle yasaktır.</li>
    <li>Platform içindeki başka kullanıcıların verisine erişmeye çalışmamak.</li>
</ul>

<h3>7. Fikri Mülkiyet</h3>
<p>Platformdaki metin, logo, içerik rehberleri, şablonlar, video ve tasarımlar MentorDE'ye aittir. Yazılı izin olmadan kopyalanamaz, dağıtılamaz veya ticari amaçla kullanılamaz. Kullanıcının yüklediği belgeler kullanıcıya aittir; Platform, yalnızca hizmeti sunmak amacıyla bu belgeleri işler.</p>

<h3>8. Hizmetin Değiştirilmesi veya Durdurulması</h3>
<p>Platform, önceden bildirim yaparak ücretleri, özellikleri veya politikaları değiştirebilir. Kritik değişikliklerde 30 gün önceden e-posta ile bildirim yapılır; kabul etmeyen kullanıcılar iptal hakkını kullanabilir.</p>

<h3>9. Sorumluluk Sınırlandırması</h3>
<ul>
    <li>Üniversite kabulü, vize onayı, konaklama bulma ve buna benzer üçüncü taraf kararları Platformun kontrolü dışındadır.</li>
    <li>Platform, dolaylı, arızi, özel veya sonuç olarak ortaya çıkan zararlardan sorumlu tutulamaz.</li>
    <li>Toplam sorumluluk, son 12 ay içinde ödenen danışmanlık ücretini aşamaz.</li>
    <li>Bu sınırlandırmalar kullanıcının tüketici haklarını engellemez.</li>
</ul>

<h3>10. Gizlilik ve Veri Koruma</h3>
<p>Kişisel verilerin işlenmesi <a href="/privacy">Gizlilik Politikası</a>'nda ayrıntılı açıklanmıştır. Platformu kullanarak Gizlilik Politikasını da kabul etmiş sayılırsınız.</p>

<h3>11. Uygulanacak Hukuk ve Uyuşmazlıklar</h3>
<ul>
    <li>Bu koşullar Türkiye Cumhuriyeti hukukuna tabidir.</li>
    <li>Tüketici uyuşmazlıklarında Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</li>
    <li>Ticari uyuşmazlıklarda İstanbul Anadolu Mahkemeleri ve İcra Daireleri yetkilidir.</li>
    <li>Uyuşmazlıkları öncelikli olarak iyi niyetle çözmeyi teşvik ederiz: <a href="mailto:support@mentorde.com">support@mentorde.com</a></li>
</ul>

<h3>12. Hesabın Kapatılması</h3>
<p>Hesabınızı dilediğiniz zaman <a href="mailto:support@mentorde.com">support@mentorde.com</a> adresine yazarak kapatabilirsiniz. Devam eden bir danışmanlık sözleşmeniz varsa, sözleşme hükümleri uygulanmaya devam eder. Yasal zorunluluk dışında tüm kişisel verileriniz 30 gün içinde silinir veya anonimleştirilir.</p>

<h3>13. İletişim</h3>
<p>Koşullar hakkında sorularınız için: <a href="mailto:support@mentorde.com">support@mentorde.com</a></p>
HTML;
    }

    private function privacyBody(): string
    {
        return <<<'HTML'
<p><em>Son güncelleme: 20 Nisan 2026 · KVKK &amp; GDPR uyumludur.</em></p>

<p>MentorDE ("biz", "MentorDE"), kullanıcılarının kişisel verilerinin korunmasına büyük önem verir. Bu Gizlilik Politikası; platformumuzu kullanırken hangi verileri topladığımızı, bu verileri nasıl işlediğimizi, ne kadar süre sakladığımızı ve haklarınızı nasıl kullanabileceğinizi açıklar.</p>

<blockquote><strong>Kısaca:</strong> Verileriniz yalnızca danışmanlık hizmetini sunmak, sözleşme yükümlülüklerini yerine getirmek ve yasal zorunlulukları karşılamak için kullanılır. Verilerinizi izinsiz üçüncü taraflarla paylaşmayız. Dilediğiniz zaman verilerinize erişebilir, silebilir veya dışa aktarabilirsiniz.</blockquote>

<h3>1. Veri Sorumlusu</h3>
<p>Kişisel verilerinizin işlenmesinden MentorDE sorumludur. İletişim: <a href="mailto:support@mentorde.com">support@mentorde.com</a></p>

<h3>2. Topladığımız Veriler</h3>
<h4>2.1 Sizden aldığımız veriler</h4>
<ul>
    <li><strong>Kimlik &amp; iletişim:</strong> Ad, soyad, e-posta, telefon, doğum tarihi.</li>
    <li><strong>Akademik bilgiler:</strong> Diploma, transkript, dil sertifikaları, not ortalaması, hedef üniversite/şehir.</li>
    <li><strong>Başvuru belgeleri:</strong> Pasaport kopyası, motivasyon mektubu, sağlık sigortası, finansal belgeler.</li>
    <li><strong>Ödeme bilgileri:</strong> Fatura ve dekont bilgisi. Kart bilgileri doğrudan ödeme sağlayıcısında (Stripe) işlenir, sunucumuza hiç düşmez.</li>
    <li><strong>İletişim geçmişi:</strong> Danışmanınızla yazışmalar, randevu notları, destek talepleri.</li>
</ul>

<h4>2.2 Otomatik topladığımız veriler</h4>
<ul>
    <li><strong>Teknik veriler:</strong> IP adresi, tarayıcı türü, dil tercihi, oturum çerezleri.</li>
    <li><strong>Kullanım verileri:</strong> Giriş kayıtları, platform içindeki aktiviteleriniz (sayfa ziyaretleri, belge yüklemeleri).</li>
    <li><strong>Çerezler:</strong> Oturum yönetimi ve tercih hatırlama için kullanılır. İsteğe bağlı analytics çerezleri için onayınız sorulur.</li>
</ul>

<h3>3. Verileri Ne İçin İşleriz (Hukuki Dayanak)</h3>
<table>
    <thead><tr><th>Amaç</th><th>Hukuki Dayanak</th></tr></thead>
    <tbody>
        <tr><td>Almanya eğitim başvurusu danışmanlığı (belge hazırlık, üniversite başvurusu, vize)</td><td>Sözleşmenin ifası (KVKK m.5/2-c, GDPR Art. 6/1-b)</td></tr>
        <tr><td>Fatura düzenleme, muhasebe kayıtları</td><td>Yasal yükümlülük (KVKK m.5/2-ç, GDPR Art. 6/1-c)</td></tr>
        <tr><td>Hizmet geliştirme, güvenlik, sahtecilik önleme</td><td>Meşru menfaat (KVKK m.5/2-f, GDPR Art. 6/1-f)</td></tr>
        <tr><td>Pazarlama iletişimi (yalnızca onay verdiyseniz)</td><td>Açık rıza (KVKK m.5/1, GDPR Art. 6/1-a)</td></tr>
    </tbody>
</table>

<h3>4. Verilerin Paylaşıldığı Taraflar</h3>
<p>Verilerinizi yalnızca hizmet vermek için gerekli taraflarla paylaşırız:</p>
<ul>
    <li>Üniversite başvuru portalları (Uni-Assist, Hochschulstart vb.) — başvuru için zorunlu.</li>
    <li>Alman konsoloslukları / VFS Global — vize dosyası gönderimi.</li>
    <li>Ödeme sağlayıcı (Stripe, Inc. — AB &amp; ABD Privacy Framework uyumlu).</li>
    <li>E-posta sağlayıcı (Resend) — bildirim ve işlem e-postaları için.</li>
    <li>Barındırma / altyapı (KAS All-inkl, AB sunucu).</li>
    <li>Yasal mercilere zorunlu bildirim hâlleri.</li>
</ul>
<p><strong>Pazarlama amacıyla üçüncü şahıslara veri satışı yapmayız.</strong></p>

<h3>5. Google ile Giriş</h3>
<p>Google hesabınızla giriş yapmayı seçerseniz Google, bize yalnızca ad, e-posta adresi ve profil fotoğrafı URL'si iletir. Gmail içeriklerinize, kişi listenize veya diğer Google hizmetlerinize erişimimiz yoktur. Google bağlantısını dilediğiniz zaman hesabınızdan kaldırabilirsiniz.</p>

<h3>6. Saklama Süreleri</h3>
<ul>
    <li><strong>Aktif danışmanlık:</strong> Sözleşme süresi boyunca + yasal zorunluluk süresi (genelde 10 yıl — muhasebe).</li>
    <li><strong>Başvuru belgeleri:</strong> Vize/kabul süreci tamamlandıktan 2 yıl sonra anonimleştirilir veya silinir.</li>
    <li><strong>Pazarlama onayları:</strong> Onay geri alınana kadar; en fazla 3 yıl.</li>
    <li><strong>Log kayıtları:</strong> 180 gün.</li>
</ul>

<h3>7. Haklarınız</h3>
<p>KVKK ve GDPR kapsamında şu haklara sahipsiniz:</p>
<ul>
    <li>Verilerinize erişim ve kopyasını talep etme</li>
    <li>Yanlış verilerin düzeltilmesini isteme</li>
    <li>Silme hakkı ("unutulma hakkı") — yasal saklama süreleri saklı kalmak üzere</li>
    <li>İşlemeyi kısıtlama veya itiraz etme</li>
    <li>Verilerinizi başka bir sağlayıcıya taşıma (veri taşınabilirliği)</li>
    <li>Açık rızayı geri çekme — geçmiş işlemleri etkilemez</li>
    <li>Veri Koruma Otoritesi'ne (KVKK Kurumu / yerel AB otoriteleri) şikâyet</li>
</ul>
<p>Taleplerinizi <a href="mailto:support@mentorde.com">support@mentorde.com</a> adresine iletebilirsiniz. <strong>30 gün içinde yanıt veririz.</strong></p>

<h3>8. Veri Güvenliği</h3>
<ul>
    <li>Tüm trafik HTTPS (TLS 1.3) üzerinden şifrelenir.</li>
    <li>Şifreler bcrypt ile hashlenir; tarafımızda düz metin saklanmaz.</li>
    <li>Belgeler erişim kontrolü ile korunur; yalnızca yetkili danışman ve siz erişebilirsiniz.</li>
    <li>Düzenli güvenlik güncellemeleri, olay müdahale politikası ve log izleme uygulanır.</li>
</ul>

<h3>9. Çerezler</h3>
<p>Zorunlu oturum çerezleri dışındaki çerezler için, ilk ziyarette onayınızı alırız. Onayınızı tarayıcı ayarlarınızdan veya bize yazarak dilediğiniz zaman geri çekebilirsiniz.</p>

<h3>10. Çocuk Gizliliği</h3>
<p>Hizmetimiz 16 yaş altındaki kullanıcılara yönelik değildir. 16 yaş altı bir kullanıcıya ait veriyi istemeden topladığımızı fark edersek bu veriyi hızla sileriz.</p>

<h3>11. Değişiklikler</h3>
<p>Bu politikayı zaman zaman güncelleyebiliriz. Önemli değişikliklerde e-posta ile veya platform içinden bildirim yaparız. En güncel sürüm daima bu sayfada yayındadır.</p>

<h3>12. İletişim</h3>
<p>Gizlilik soruları için: <a href="mailto:support@mentorde.com">support@mentorde.com</a><br>
Veri Koruma Sorumlusu: <a href="mailto:support@mentorde.com">support@mentorde.com</a></p>
HTML;
    }

    private function imprintBody(): string
    {
        return <<<'HTML'
<p><em>Son güncelleme: 20 Nisan 2026</em></p>

<p>MentorDE — Almanya eğitim danışmanlığı platformu. Aşağıda yasal mercii bilgileri yer almaktadır.</p>

<h3>İletişim</h3>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@mentorde.com">support@mentorde.com</a></li>
    <li><strong>Web:</strong> <a href="https://www.mentorde.com" target="_blank" rel="noopener">www.mentorde.com</a></li>
</ul>

<h3>Yasal Detaylar</h3>
<p>Detaylı yasal bilgiler ve impressum için:
<a href="https://www.mentorde.com/tr/impressum-yasal-bilgiler" target="_blank" rel="noopener">www.mentorde.com/tr/impressum-yasal-bilgiler</a></p>

<h3>Sorumluluk Reddi</h3>
<p>Bu sayfanın içeriği bilgilendirme amaçlıdır. Üniversite başvurusu, vize ve eğitim süreçleri konusunda nihai karar veren makamlar üçüncü taraflardır (üniversiteler, konsolosluklar, Uni-Assist, vb.). MentorDE, bu makamların kararları üzerinde garanti veremez.</p>
HTML;
    }
}
