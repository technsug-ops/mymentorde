@extends('legal.layout', ['pageTitle' => 'Kullanım Koşulları'])

@section('content')
    <h1>Kullanım Koşulları</h1>
    <p class="lead">Son güncelleme: {{ \Carbon\Carbon::parse('2026-04-20')->translatedFormat('d F Y') }}</p>

    <p>
        {{ config('brand.name', 'MentorDE') }} platformunu kullanarak aşağıdaki koşulları kabul etmiş sayılırsınız.
        Koşulları kabul etmiyorsanız lütfen platformu kullanmayınız.
    </p>

    <div class="callout">
        Özet: Hizmet, Almanya'da eğitim başvurusu sürecinde danışmanlık sağlar. Başvurunun kabul edilmesi veya
        vize alınması garanti değildir. Ücretler sözleşmede belirtilir. Platformu kötüye kullanmak yasaktır.
    </div>

    <h2>1. Taraflar ve Tanımlar</h2>
    <p>
        Bu koşullar; <strong>{{ config('brand.name', 'MentorDE') }}</strong> (bundan sonra "Platform") ile platformu kullanan
        gerçek veya tüzel kişi (bundan sonra "Kullanıcı") arasındaki ilişkiyi düzenler.
    </p>

    <h2>2. Hizmetin Kapsamı</h2>
    <ul>
        <li>Almanya'da üniversite / dil okulu / Ausbildung başvuruları için danışmanlık</li>
        <li>Belge hazırlama, çeviri yönlendirmesi, başvuru dosyası yönetimi</li>
        <li>Vize süreci rehberliği, konut ve seyahat organizasyonu yönlendirmesi</li>
        <li>Platform üzerinden danışmanla iletişim, randevu, belge yükleme</li>
    </ul>
    <p>
        <strong>Garanti edilmeyen hususlar:</strong> Üniversite kabulü, vize onayı, başvuru portal ücretleri,
        konsolosluk harçları, yurt dışı sigorta primleri — bunlar kullanıcı sorumluluğundadır ve danışmanlık
        ücretinin dışındadır.
    </p>

    <h2>3. Hesap Oluşturma ve Güvenliği</h2>
    <ul>
        <li>Hesap açmak için 16 yaş ve üzerinde olmalısınız.</li>
        <li>Verdiğiniz bilgilerin doğru ve güncel olmasından sorumlusunuz.</li>
        <li>Şifrenizin gizliliği ve hesabınızın güvenliği size aittir. Yetkisiz erişim şüphesinde hemen bildirin.</li>
        <li>Bir kullanıcı yalnızca tek bir kişisel hesap açabilir.</li>
    </ul>

    <h2>4. Ücretler ve Ödeme</h2>
    <ul>
        <li>Danışmanlık ücretleri ayrı bir sözleşmede yazılı olarak belirlenir.</li>
        <li>Ödemeler Stripe üzerinden veya havale ile alınır. Dijital ödemelerde 3D Secure zorunludur.</li>
        <li>Ödemesi yapılmayan süreçlerde hizmet duraklatılabilir; sözleşmenin 30 günden fazla askıda kalması durumunda fesih işletilebilir.</li>
        <li>Üçüncü taraf ücretleri (üniversite başvuru ücreti, vize harcı, çeviri, noter, APS vb.) kullanıcı tarafından karşılanır.</li>
    </ul>

    <h2>5. İptal ve İade</h2>
    <ul>
        <li>
            <strong>14 günlük cayma hakkı:</strong> Mesafeli sözleşmeler kanununa göre dijital hizmet başlamadan önce 14 gün içinde cayma hakkınız vardır.
        </li>
        <li>
            Hizmet başladıktan sonra yapılan iptallerde, o tarihe kadar sunulan hizmetin bedeli kesilir; kalan tutar iade edilir.
        </li>
        <li>
            Üçüncü taraf ücretleri (vize harcı, çeviri ücreti vs.) iade kapsamına <strong>girmez</strong>.
        </li>
        <li>
            İade talepleri 14 iş günü içinde ödeme yapılan kanala geri yansıtılır.
        </li>
    </ul>

    <h2>6. Kullanıcı Yükümlülükleri</h2>
    <ul>
        <li>Doğru ve gerçek belgeler yüklemek. Sahte belge yüklemek başvurunuzu ve sözleşmeyi <strong>tek taraflı</strong> feshetme sebebidir ve yasal takibe konu olabilir.</li>
        <li>Danışmanla iletişimde saygı kurallarına uymak; taciz, hakaret, ırkçı/cinsiyetçi davranış yasaktır.</li>
        <li>Platformun güvenliğini tehdit eden eylemler (sızma, ddos, otomatik veri çekme) kesinlikle yasaktır.</li>
        <li>Platform içindeki başka kullanıcıların verisine erişmeye çalışmamak.</li>
    </ul>

    <h2>7. Fikri Mülkiyet</h2>
    <p>
        Platformdaki metin, logo, içerik rehberleri, şablonlar, video ve tasarımlar
        {{ config('brand.name', 'MentorDE') }}'ye aittir. Yazılı izin olmadan kopyalanamaz, dağıtılamaz veya
        ticari amaçla kullanılamaz. Kullanıcının yüklediği belgeler kullanıcıya aittir; Platform, yalnızca
        hizmeti sunmak amacıyla bu belgeleri işler.
    </p>

    <h2>8. Hizmetin Değiştirilmesi veya Durdurulması</h2>
    <p>
        Platform, önceden bildirim yaparak ücretleri, özellikleri veya politikaları değiştirebilir.
        Kritik değişikliklerde 30 gün önceden e-posta ile bildirim yapılır; kabul etmeyen kullanıcılar
        iptal hakkını kullanabilir.
    </p>

    <h2>9. Sorumluluk Sınırlandırması</h2>
    <ul>
        <li>Üniversite kabulü, vize onayı, konaklama bulma ve buna benzer üçüncü taraf kararları Platformun kontrolü dışındadır.</li>
        <li>Platform, dolaylı, arızi, özel veya sonuç olarak ortaya çıkan zararlardan sorumlu tutulamaz.</li>
        <li>Toplam sorumluluk, son 12 ay içinde ödenen danışmanlık ücretini aşamaz.</li>
        <li>Bu sınırlandırmalar kullanıcının tüketici haklarını engellemez.</li>
    </ul>

    <h2>10. Gizlilik ve Veri Koruma</h2>
    <p>
        Kişisel verilerin işlenmesi <a href="{{ route('legal.privacy') }}">Gizlilik Politikası</a>'nda ayrıntılı açıklanmıştır.
        Platformu kullanarak Gizlilik Politikasını da kabul etmiş sayılırsınız.
    </p>

    <h2>11. Uygulanacak Hukuk ve Uyuşmazlıklar</h2>
    <ul>
        <li>Bu koşullar Türkiye Cumhuriyeti hukukuna tabidir.</li>
        <li>Tüketici uyuşmazlıklarında Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.</li>
        <li>Ticari uyuşmazlıklarda İstanbul Anadolu Mahkemeleri ve İcra Daireleri yetkilidir.</li>
        <li>Uyuşmazlıkları öncelikli olarak iyi niyetle çözmeyi teşvik ederiz: <a href="mailto:destek@mentorde.com">destek@mentorde.com</a></li>
    </ul>

    <h2>12. Hesabın Kapatılması</h2>
    <p>
        Hesabınızı dilediğiniz zaman <a href="mailto:destek@mentorde.com">destek@mentorde.com</a> adresine yazarak kapatabilirsiniz.
        Devam eden bir danışmanlık sözleşmeniz varsa, sözleşme hükümleri uygulanmaya devam eder.
        Yasal zorunluluk dışında tüm kişisel verileriniz 30 gün içinde silinir veya anonimleştirilir.
    </p>

    <h2>13. İletişim</h2>
    <p>
        Koşullar hakkında sorularınız için: <a href="mailto:destek@mentorde.com">destek@mentorde.com</a>
    </p>
@endsection
