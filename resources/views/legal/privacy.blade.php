@extends('legal.layout', ['pageTitle' => 'Gizlilik Politikası'])

@section('content')
    <h1>Gizlilik Politikası</h1>
    <p class="lead">Son güncelleme: {{ \Carbon\Carbon::parse('2026-04-20')->translatedFormat('d F Y') }} · KVKK &amp; GDPR uyumludur.</p>

    <p>
        {{ config('brand.name', 'MentorDE') }} (“biz”, “{{ config('brand.name', 'MentorDE') }}”), kullanıcılarının kişisel verilerinin korunmasına büyük önem verir.
        Bu Gizlilik Politikası; platformumuzu kullanırken hangi verileri topladığımızı, bu verileri nasıl işlediğimizi,
        ne kadar süre sakladığımızı ve haklarınızı nasıl kullanabileceğinizi açıklar.
    </p>

    <div class="callout">
        Kısaca: Verileriniz yalnızca danışmanlık hizmetini sunmak, sözleşme yükümlülüklerini yerine getirmek ve yasal zorunlulukları karşılamak için kullanılır.
        Verilerinizi izinsiz üçüncü taraflarla paylaşmayız. Dilediğiniz zaman verilerinize erişebilir, silebilir veya dışa aktarabilirsiniz.
    </div>

    <h2>1. Veri Sorumlusu</h2>
    <p>
        Kişisel verilerinizin işlenmesinden <strong>{{ config('brand.name', 'MentorDE') }}</strong> sorumludur.
        İletişim: <a href="mailto:destek@mentorde.com">destek@mentorde.com</a>
    </p>

    <h2>2. Topladığımız Veriler</h2>
    <h3>2.1 Sizden aldığımız veriler</h3>
    <ul>
        <li><strong>Kimlik &amp; iletişim:</strong> Ad, soyad, e-posta, telefon, doğum tarihi.</li>
        <li><strong>Akademik bilgiler:</strong> Diploma, transkript, dil sertifikaları, not ortalaması, hedef üniversite/şehir.</li>
        <li><strong>Başvuru belgeleri:</strong> Pasaport kopyası, motivasyon mektubu, sağlık sigortası, finansal belgeler.</li>
        <li><strong>Ödeme bilgileri:</strong> Fatura ve dekont bilgisi. Kart bilgileri doğrudan ödeme sağlayıcısında (Stripe) işlenir, sunucumuza hiç düşmez.</li>
        <li><strong>İletişim geçmişi:</strong> Danışmanınızla yazışmalar, randevu notları, destek talepleri.</li>
    </ul>

    <h3>2.2 Otomatik topladığımız veriler</h3>
    <ul>
        <li><strong>Teknik veriler:</strong> IP adresi, tarayıcı türü, dil tercihi, oturum çerezleri.</li>
        <li><strong>Kullanım verileri:</strong> Giriş kayıtları, platform içindeki aktiviteleriniz (sayfa ziyaretleri, belge yüklemeleri).</li>
        <li><strong>Çerezler:</strong> Oturum yönetimi ve tercih hatırlama için kullanılır. İsteğe bağlı analytics çerezleri için onayınız sorulur.</li>
    </ul>

    <h2>3. Verileri Ne İçin İşleriz (Hukuki Dayanak)</h2>
    <table class="tbl">
        <tr>
            <th>Amaç</th>
            <th>Hukuki Dayanak</th>
        </tr>
        <tr>
            <td>Almanya eğitim başvurusu danışmanlığı (belge hazırlık, üniversite başvurusu, vize)</td>
            <td>Sözleşmenin ifası (KVKK m.5/2-c, GDPR Art. 6/1-b)</td>
        </tr>
        <tr>
            <td>Fatura düzenleme, muhasebe kayıtları</td>
            <td>Yasal yükümlülük (KVKK m.5/2-ç, GDPR Art. 6/1-c)</td>
        </tr>
        <tr>
            <td>Hizmet geliştirme, güvenlik, sahtecilik önleme</td>
            <td>Meşru menfaat (KVKK m.5/2-f, GDPR Art. 6/1-f)</td>
        </tr>
        <tr>
            <td>Pazarlama iletişimi (yalnızca onay verdiyseniz)</td>
            <td>Açık rıza (KVKK m.5/1, GDPR Art. 6/1-a)</td>
        </tr>
    </table>

    <h2>4. Verilerin Paylaşıldığı Taraflar</h2>
    <p>Verilerinizi <strong>yalnızca hizmet vermek için gerekli</strong> taraflarla paylaşırız:</p>
    <ul>
        <li><strong>Üniversite başvuru portalları</strong> (Uni-Assist, Hochschulstart vb.) — başvuru için zorunlu.</li>
        <li><strong>Alman konsoloslukları / VFS Global</strong> — vize dosyası gönderimi.</li>
        <li><strong>Ödeme sağlayıcı</strong> (Stripe, Inc. — AB &amp; ABD Privacy Framework uyumlu).</li>
        <li><strong>E-posta sağlayıcı</strong> (Resend) — bildirim ve işlem e-postaları için.</li>
        <li><strong>Barındırma / altyapı</strong> (KAS All-inkl, AB sunucu).</li>
        <li><strong>Yasal mercilere zorunlu bildirim</strong> hâlleri.</li>
    </ul>
    <p>Pazarlama amacıyla üçüncü şahıslara <strong>veri satışı yapmayız</strong>.</p>

    <h2>5. Google ile Giriş</h2>
    <p>
        Google hesabınızla giriş yapmayı seçerseniz Google, bize yalnızca <strong>ad, e-posta adresi ve profil fotoğrafı URL'si</strong>
        iletir. Gmail içeriklerinize, kişi listenize veya diğer Google hizmetlerinize erişimimiz <strong>yoktur</strong>.
        Google bağlantısını dilediğiniz zaman hesabınızdan kaldırabilirsiniz.
    </p>

    <h2>6. Saklama Süreleri</h2>
    <ul>
        <li><strong>Aktif danışmanlık:</strong> Sözleşme süresi boyunca + yasal zorunluluk süresi (genelde 10 yıl — muhasebe).</li>
        <li><strong>Başvuru belgeleri:</strong> Vize/kabul süreci tamamlandıktan 2 yıl sonra anonimleştirilir veya silinir.</li>
        <li><strong>Pazarlama onayları:</strong> Onay geri alınana kadar; en fazla 3 yıl.</li>
        <li><strong>Log kayıtları:</strong> 180 gün.</li>
    </ul>

    <h2>7. Haklarınız</h2>
    <p>KVKK ve GDPR kapsamında şu haklara sahipsiniz:</p>
    <ul>
        <li>Verilerinize erişim ve kopyasını talep etme</li>
        <li>Yanlış verilerin düzeltilmesini isteme</li>
        <li>Silme hakkı (“unutulma hakkı”) — yasal saklama süreleri saklı kalmak üzere</li>
        <li>İşlemeyi kısıtlama veya itiraz etme</li>
        <li>Verilerinizi başka bir sağlayıcıya taşıma (veri taşınabilirliği)</li>
        <li>Açık rızayı geri çekme — geçmiş işlemleri etkilemez</li>
        <li>Veri Koruma Otoritesi'ne (KVKK Kurumu / yerel AB otoriteleri) şikâyet</li>
    </ul>
    <p>Taleplerinizi <a href="mailto:destek@mentorde.com">destek@mentorde.com</a> adresine iletebilirsiniz. 30 gün içinde yanıt veririz.</p>

    <h2>8. Veri Güvenliği</h2>
    <ul>
        <li>Tüm trafik HTTPS (TLS 1.3) üzerinden şifrelenir.</li>
        <li>Şifreler bcrypt ile hashlenir; tarafımızda düz metin saklanmaz.</li>
        <li>Belgeler erişim kontrolü ile korunur; yalnızca yetkili danışman ve siz erişebilirsiniz.</li>
        <li>Düzenli güvenlik güncellemeleri, olay müdahale politikası ve log izleme uygulanır.</li>
    </ul>

    <h2>9. Çerezler</h2>
    <p>
        Zorunlu oturum çerezleri dışındaki çerezler için, ilk ziyarette onayınızı alırız. Onayınızı
        tarayıcı ayarlarınızdan veya bize yazarak dilediğiniz zaman geri çekebilirsiniz.
    </p>

    <h2>10. Çocuk Gizliliği</h2>
    <p>
        Hizmetimiz 16 yaş altındaki kullanıcılara yönelik değildir. 16 yaş altı bir kullanıcıya ait veriyi
        istemeden topladığımızı fark edersek bu veriyi hızla sileriz.
    </p>

    <h2>11. Değişiklikler</h2>
    <p>
        Bu politikayı zaman zaman güncelleyebiliriz. Önemli değişikliklerde e-posta ile veya platform
        içinden bildirim yaparız. En güncel sürüm daima bu sayfada yayındadır.
    </p>

    <h2>12. İletişim</h2>
    <p>
        Gizlilik soruları için: <a href="mailto:destek@mentorde.com">destek@mentorde.com</a><br>
        Veri Koruma Sorumlusu: <a href="mailto:destek@mentorde.com">destek@mentorde.com</a>
    </p>
@endsection
