<?php

/**
 * Application Guides — Almanya başvuru süreçleri için manager rehberleri.
 *
 * Her guide bir kod ile tanımlı (uri slug + view'da seçim):
 *  - aps       Akademische Prüfstelle (önlisans/lisans denkliği)
 *  - anmeldung Şehir kaydı (Bürgeramt)
 *  - sperrkonto Bloke hesap (öğrenci finansal kanıt)
 *  - vpd        Vorprüfungsdokumentation (Uni-Assist VPD belgesi)
 *
 * Yapı:
 *  - meta: title, icon, color (UI gradient + accent)
 *  - intro: banner metni
 *  - external_links: resmi sayfa URL'leri
 *  - sections: adım adım yapılacaklar listesi
 *  - fields: $guest model'inden çıkartılacak alanlar (copy-to-clipboard)
 *  - documents: gerekli belgeler listesi
 *  - tips: önemli ipuçları
 */
return [

    // ─── APS — Akademische Prüfstelle ───────────────────────────────────
    'aps' => [
        'meta' => [
            'title'    => 'APS Başvuru Rehberi',
            'subtitle' => 'Akademische Prüfstelle — Türk öğrencilerin Almanya başvurusunda zorunlu denklik',
            'icon'     => '📜',
            'color'    => '#0066b3',
            'color2'   => '#003c8f',
            'bg_tint'  => '#e8f1fb',
            'border'   => '#a8c4e8',
        ],
        'intro' => 'APS sertifikası **Türk vatandaşı tüm öğrenciler için ZORUNLUDUR**. Yüksek lisans için Plausibilitätsinterview, lisans için doğrudan değerlendirme.',
        'external_links' => [
            ['label' => '🌐 APS Türkiye Resmi Site', 'url' => 'https://www.aps.tr/'],
            ['label' => '📋 Online Başvuru Portali', 'url' => 'https://www.uni-assist.de/en/applicants/preparation/aps/'],
            ['label' => '💳 Ödeme Bilgileri',          'url' => 'https://www.aps.tr/uecretler/'],
        ],
        'sections' => [
            [
                'title' => 'Adım 1: Online APS başvurusu',
                'items' => [
                    'aps.tr → "Online Başvuru" → kayıt ol',
                    'Üye girişi sonrası "Yeni Başvuru" → kişisel bilgiler',
                    'Eğitim bilgileri: lise + üniversite (varsa) + transkriptler',
                    'Pasaport bilgilerini gir (no, geçerlilik, çıkış tarihi)',
                ],
            ],
            [
                'title' => 'Adım 2: Belge yükleme (PDF)',
                'items' => [
                    'Lise diploması + Türkçe + Almanca/İngilizce tercüme',
                    'Lise transkripti + tercüme',
                    'Üniversite diploması (varsa) + tercüme',
                    'Üniversite transkripti (varsa) + tercüme',
                    'Pasaport ilk 2 sayfa (renkli)',
                    'Vesikalık fotoğraf (biyometrik, son 6 ay)',
                ],
            ],
            [
                'title' => 'Adım 3: Ücret ödeme',
                'items' => [
                    'Ön lisans/lisans denkliği: 150€ + 75€ posta = ~225€',
                    'Yüksek lisans (Plausibilitätsinterview): 250€ + 75€ posta = ~325€',
                    'Garanti Bankası IBAN ile EUR ödemesi (APS hesabı)',
                    'Dekont online sisteme yüklenir',
                ],
            ],
            [
                'title' => 'Adım 4: Süreç + Sertifika',
                'items' => [
                    'Lisans denkliği: ~4-6 hafta',
                    'YL Plausibilitätsinterview: ~6-8 hafta + Ankara Goethe-Institut\'ta yüz yüze görüşme',
                    'Sertifika orijinali kişiye posta + dijital uni-assist üzerinden başvuruda kullanılır',
                    'Geçerlilik süresi: SINIRSIZ (bir kez al, ömür boyu kullan)',
                ],
            ],
        ],
        'fields' => [
            'first_name'       => ['label' => 'Ad',                  'desc' => 'Pasaporttaki gibi'],
            'last_name'        => ['label' => 'Soyad',               'desc' => 'Pasaporttaki gibi'],
            'email'            => ['label' => 'E-posta',             'desc' => 'aps.tr kayıt için'],
            'phone'            => ['label' => 'Telefon',             'desc' => 'TR formatı'],
            'birth_date'       => ['label' => 'Doğum Tarihi',        'desc' => 'GG.AA.YYYY'],
            'birth_city'       => ['label' => 'Doğum Yeri',          'desc' => 'Pasaporttaki gibi'],
            'passport_number'  => ['label' => 'Pasaport No',         'desc' => 'TR pasaportu'],
            'nationality'      => ['label' => 'Uyruk',               'desc' => 'TR'],
        ],
        'documents' => [
            ['code' => 'DOC-DIPL', 'name' => 'Lise Diploması + Tercüme',     'required' => true],
            ['code' => 'DOC-TRNS', 'name' => 'Lise Transkripti + Tercüme',   'required' => true],
            ['code' => 'DOC-DIPL-UNI', 'name' => 'Lisans Diploması + Tercüme (varsa)', 'required' => false],
            ['code' => 'DOC-TRNS-UNI', 'name' => 'Lisans Transkripti + Tercüme (varsa)', 'required' => false],
            ['code' => 'DOC-PASS', 'name' => 'Pasaport Fotokopisi',          'required' => true],
            ['code' => 'DOC-PHOTO', 'name' => 'Vesikalık Fotoğraf',          'required' => true],
        ],
        'tips' => [
            '🚨 YL adayları için **Plausibilitätsinterview Ankara\'da yüz yüze yapılır** — Türkiye dışında geçerli değil',
            '💡 Sertifika ALMANYA SINIRSIZ geçerlidir — bir kez aldıktan sonra ömür boyu kullanılır',
            '💡 Eğer adayın çift uyruğu varsa (TC + AB) APS gerekmez',
            '⚠ Tercümeler yeminli tercüman tarafından yapılmalı — apostil gerekmez ama akredite tercüman zorunlu',
        ],
    ],

    // ─── Anmeldung — Şehir Kaydı (Bürgeramt) ────────────────────────────
    'anmeldung' => [
        'meta' => [
            'title'    => 'Anmeldung Rehberi',
            'subtitle' => 'Almanya\'ya varış sonrası 14 gün içinde zorunlu şehir kaydı',
            'icon'     => '🏠',
            'color'    => '#dc2626',
            'color2'   => '#991b1b',
            'bg_tint'  => '#fef2f2',
            'border'   => '#fecaca',
        ],
        'intro' => 'Almanya\'ya varıştan sonra **14 gün içinde** yerel Bürgeramt\'a kayıt zorunludur. Anmeldebestätigung olmadan banka hesabı, sigorta, vize uzatma YOK.',
        'external_links' => [
            ['label' => '🏛 Berlin Bürgeramt Termin', 'url' => 'https://service.berlin.de/dienstleistung/120335/'],
            ['label' => '🏛 München KVR Termin',      'url' => 'https://www.muenchen.de/dienstleistungsfinder/muenchen/1063341/'],
            ['label' => '🏛 Köln Bürgeramt',           'url' => 'https://www.stadt-koeln.de/leben-in-koeln/buergerdienste/anmeldung'],
            ['label' => '📋 Anmeldeformular PDF',     'url' => 'https://service.berlin.de/dienstleistung/120335/'],
        ],
        'sections' => [
            [
                'title' => 'Adım 1: Termin al (online)',
                'items' => [
                    'Şehrin Bürgeramt online termin sistemi → "Anmeldung einer Wohnung"',
                    'Berlin\'de termin alabilmek ZOR — sabah 7\'de yenilenir, hızlı tıkla',
                    'Müsait termin yoksa: erkenden kuyruğa gir (07:00\'de Bürgeramt önünde olun)',
                    'Bazı şehirlerde online alternatif: video randevu',
                ],
            ],
            [
                'title' => 'Adım 2: Belgeleri hazırla',
                'items' => [
                    'Geçerli pasaport (orijinal)',
                    'Wohnungsgeberbestätigung — ev sahibinden imzalı belge (kira sözleşmesi YETMEZ)',
                    'Kira sözleşmesi kopyası',
                    'Doldurulmuş Anmeldeformular (PDF, A4 yazdır)',
                    'Vize/oturum izni (varsa)',
                ],
            ],
            [
                'title' => 'Adım 3: Bürgeramt randevusu',
                'items' => [
                    'Termin yerine vaktinden 10 dk önce git',
                    'Anmeldebestätigung damgalı + imzalı çık (saklamak ŞART)',
                    'Steueridentifikationsnummer (vergi no) 2-4 hafta içinde posta ile gelir',
                    'Adres değişirse 14 gün içinde Ummeldung yapılmalı',
                ],
            ],
        ],
        'fields' => [
            'first_name'   => ['label' => 'Ad',          'desc' => 'Pasaport'],
            'last_name'    => ['label' => 'Soyad',       'desc' => 'Pasaport'],
            'birth_date'   => ['label' => 'Doğum Tarihi', 'desc' => 'GG.AA.YYYY'],
            'nationality'  => ['label' => 'Uyruk',       'desc' => 'TR'],
            'passport_number' => ['label' => 'Pasaport No', 'desc' => ''],
        ],
        'documents' => [
            ['code' => 'DOC-PASS', 'name' => 'Pasaport',                              'required' => true],
            ['code' => 'DOC-WGB',  'name' => 'Wohnungsgeberbestätigung (ev sahibinden)', 'required' => true],
            ['code' => 'DOC-MIET', 'name' => 'Kira Sözleşmesi',                       'required' => true],
            ['code' => 'DOC-ANM',  'name' => 'Anmeldeformular (doldurulmuş)',         'required' => true],
            ['code' => 'DOC-VISA', 'name' => 'Vize/Oturum İzni (varsa)',              'required' => false],
        ],
        'tips' => [
            '🚨 **14 gün** içinde yapılmazsa 1000€\'ya kadar ceza (genelde ilk seferde uyarı)',
            '💡 Wohnungsgeberbestätigung olmadan Anmeldung yapılamaz — ev sahibinden ısrarla iste',
            '💡 Anmeldebestätigung damgasız geçersiz — kontrol et',
            '⚠ Steueridentifikationsnummer 2-4 hafta sürebilir; Brutto iş başlamak için bekleyin',
        ],
    ],

    // ─── Sperrkonto — Bloke Hesap ───────────────────────────────────────
    'sperrkonto' => [
        'meta' => [
            'title'    => 'Sperrkonto Rehberi',
            'subtitle' => 'Vize için zorunlu bloke hesap — €11.904 (2026)',
            'icon'     => '🏦',
            'color'    => '#15803d',
            'color2'   => '#166534',
            'bg_tint'  => '#f0fdf4',
            'border'   => '#bbf7d0',
        ],
        'intro' => 'Vize başvurusu için **€11.904** (2026 değeri, aylık €992) bloke hesap zorunlu. Expatrio / Fintiba / Coracle popüler sağlayıcılar — fiyat/UX farklı.',
        'external_links' => [
            ['label' => '🟢 Expatrio (önerilen)', 'url' => 'https://www.expatrio.com/'],
            ['label' => '🟢 Fintiba',              'url' => 'https://www.fintiba.com/'],
            ['label' => '🟢 Coracle',              'url' => 'https://www.coracle.de/'],
            ['label' => '📋 Auswärtiges Amt Resmi Bilgi', 'url' => 'https://www.auswaertiges-amt.de/de/service/laender/tuerkei-node/finanzierungsnachweis/2335550'],
        ],
        'sections' => [
            [
                'title' => 'Adım 1: Sağlayıcı seç + online başvuru',
                'items' => [
                    'Expatrio: ~€89 setup, ~€5/ay - sağlık sigortası entegre paket avantajlı',
                    'Fintiba: ~€89 setup, €5/ay - en hızlı onay (24-48 saat)',
                    'Coracle: ~€99 setup, €5/ay - Türkçe destek var',
                    'Online başvuru → kişisel bilgiler + pasaport upload',
                ],
            ],
            [
                'title' => 'Adım 2: Kimlik doğrulama (VideoIdent)',
                'items' => [
                    'PostIdent veya WebID üzerinden video kimlik doğrulama',
                    'Pasaport orijinal hazır + 10 dk süre + sessiz oda',
                    'Türkçe destek varsa Türkçe ajan iste',
                    'Onay: 24-72 saat',
                ],
            ],
            [
                'title' => 'Adım 3: Para transferi',
                'items' => [
                    'Banka transferi (TL → EUR conversion): Garanti, İş Bankası, Yapı Kredi popüler',
                    'Önce €1 test transferi yap (account number doğrula)',
                    '11.904€ + ~€100 buffer → EUR cinsinden gönder',
                    'Transfer onayı 1-3 iş günü',
                ],
            ],
            [
                'title' => 'Adım 4: Sperrbestätigung al',
                'items' => [
                    'Para hesaba düştükten sonra "Sperrbestätigung" PDF indir',
                    'Bu belge vize başvurusunda KONSOLOSLUKTA istenir',
                    'Almanya\'ya varış sonrası Anmeldung + banka hesap aç → €992/ay otomatik para geçer',
                ],
            ],
        ],
        'fields' => [
            'first_name'      => ['label' => 'Ad',                'desc' => 'Pasaport'],
            'last_name'       => ['label' => 'Soyad',             'desc' => 'Pasaport'],
            'email'           => ['label' => 'E-posta',           'desc' => 'Hesap için'],
            'birth_date'      => ['label' => 'Doğum Tarihi',      'desc' => 'GG.AA.YYYY'],
            'passport_number' => ['label' => 'Pasaport No',       'desc' => 'TR'],
            'nationality'     => ['label' => 'Uyruk',             'desc' => 'TR'],
        ],
        'documents' => [
            ['code' => 'DOC-PASS',   'name' => 'Pasaport',                        'required' => true],
            ['code' => 'DOC-ACPT',   'name' => 'Kabul Mektubu (varsa)',           'required' => false],
            ['code' => 'DOC-PHONE',  'name' => 'TR Telefon (SMS doğrulama için)', 'required' => true],
            ['code' => 'DOC-IBAN',   'name' => 'TR IBAN (transfer için)',         'required' => true],
        ],
        'tips' => [
            '🚨 **€11.904 (2026)** — her yıl güncellenebilir, kontrol et',
            '💡 Expatrio = paket içinde sağlık sigortası dahil → toplam ~€800 tasarruf',
            '💡 Para transferinde TL bozdurmak yerine USD ile düşük TL kuru avantajlı (TR mevduat değil)',
            '⚠ Vize öncesi Sperrbestätigung sahip değilseniz konsolosluktan vize ALMAYA HAKKINIZ YOK',
            '⚠ Coracle dışındaki sağlayıcılar Türkçe destek SUNMUYOR — İngilizce/Almanca gereklidir',
        ],
    ],

    // ─── VPD — Vorprüfungsdokumentation ─────────────────────────────────
    'vpd' => [
        'meta' => [
            'title'    => 'VPD Rehberi',
            'subtitle' => 'Vorprüfungsdokumentation — Uni-Assist üzerinden ön değerlendirme',
            'icon'     => '📑',
            'color'    => '#7c3aed',
            'color2'   => '#6d28d9',
            'bg_tint'  => '#faf5ff',
            'border'   => '#c4b5fd',
        ],
        'intro' => 'VPD = Uni-Assist\'in **ön değerlendirme belgesi**. Bazı üniversiteler VPD\'siz başvuru kabul etmez — VPD\'yi üniversiteye değil DİREKT öğrenciye verir, sen üniversiteye yüklersin.',
        'external_links' => [
            ['label' => '🌐 Uni-Assist VPD Sayfası', 'url' => 'https://www.uni-assist.de/en/applicants/applying/vpd/'],
            ['label' => '📋 Mein Konto Girişi',       'url' => 'https://my.uni-assist.de/'],
            ['label' => '💳 Ücret Tablosu',           'url' => 'https://www.uni-assist.de/en/applicants/applying/fees/'],
            ['label' => '📚 DAAD VPD Açıklama',        'url' => 'https://www.daad.de/en/studying-in-germany/requirements/uni-assist/'],
        ],
        'sections' => [
            [
                'title' => 'Adım 1: Uni-Assist Mein Konto',
                'items' => [
                    'my.uni-assist.de → kayıt ol',
                    'Profil sekmesi: kişisel bilgiler + adres + pasaport',
                    'Eğitim sekmesi: lise + üniversite (varsa) bilgileri detaylı',
                    'BID/BAN no\'larını sakla (her başvuruda kullanılır)',
                ],
            ],
            [
                'title' => 'Adım 2: VPD başvurusu seç',
                'items' => [
                    '"VPD beantragen" tuşuna bas → tek başvuru ücreti €75 (her ek tabi: €15-30)',
                    'Birden fazla program için tek VPD yeterli (aynı dönem için)',
                    'Kış dönemi başvuru: Mayıs - Temmuz (1-15 Temmuz son tarih genelde)',
                    'Yaz dönemi başvuru: Kasım - Ocak (1-15 Ocak son tarih)',
                ],
            ],
            [
                'title' => 'Adım 3: Belge yükleme',
                'items' => [
                    'Lise diploması + Türkçe + tercüme (Almanca/İngilizce, yeminli)',
                    'Lise transkripti + tercüme',
                    'YKS/AYT sonuç belgesi + tercüme',
                    'Üniversite Kazandı Belgesi + tercüme (varsa)',
                    'APS sertifikası (Türk öğrenciler için ZORUNLU)',
                    'Pasaport ilk sayfa',
                ],
            ],
            [
                'title' => 'Adım 4: Ödeme + Süreç',
                'items' => [
                    'Online kredi kartı veya SEPA transfer (Almanya banka hesabı gerek)',
                    'Sosyal Kredi: BID\'leri kullanarak indirim alabilirsin (öğrenci derneği üyeliği)',
                    'Standart süreç: 4-6 hafta',
                    'Hızlı süreç (€60 ek): 2-3 hafta',
                    'VPD PDF olarak email + portal hesabına düşer — öğrenciye direkt verilir',
                ],
            ],
        ],
        'fields' => [
            'first_name'       => ['label' => 'Ad',                  'desc' => 'Pasaport'],
            'last_name'        => ['label' => 'Soyad',               'desc' => 'Pasaport'],
            'email'            => ['label' => 'E-posta',             'desc' => 'Mein Konto'],
            'birth_date'       => ['label' => 'Doğum Tarihi',        'desc' => 'GG.AA.YYYY'],
            'birth_city'       => ['label' => 'Doğum Yeri',          'desc' => 'Pasaport'],
            'nationality'      => ['label' => 'Uyruk',               'desc' => 'TR'],
            'passport_number'  => ['label' => 'Pasaport No',         'desc' => ''],
        ],
        'documents' => [
            ['code' => 'DOC-DIPL', 'name' => 'Lise Diploması + Tercüme',         'required' => true],
            ['code' => 'DOC-TRNS', 'name' => 'Lise Transkripti + Tercüme',       'required' => true],
            ['code' => 'DOC-YKSP', 'name' => 'YKS/AYT Sonuç Belgesi + Tercüme',  'required' => true],
            ['code' => 'DOC-APS',  'name' => 'APS Sertifikası',                  'required' => true],
            ['code' => 'DOC-UNWN', 'name' => 'Üniversite Kazandı Belgesi (varsa)', 'required' => false],
            ['code' => 'DOC-PASS', 'name' => 'Pasaport Kopyası',                 'required' => true],
        ],
        'tips' => [
            '🚨 **APS olmadan VPD başvurusu KABUL EDİLMEZ** — önce APS\'yi tamamla',
            '💡 1 dönem için tek VPD yeterli — aynı dönemde 5 farklı üni\'ye başvurursan ek ücret yok',
            '💡 VPD üniversiteye değil ÖĞRENCİYE verilir — sen üniversite portalına yükle',
            '⚠ Kış dönemi son tarihi 15 Temmuz — APS 4-6 hafta sürer, MART\'tan başla',
            '⚠ Hızlı işleme ücreti €60 ek — sadece son dakika başvurularda mantıklı',
        ],
    ],
];
