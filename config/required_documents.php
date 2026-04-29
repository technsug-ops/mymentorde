<?php

/*
|--------------------------------------------------------------------------
| Zorunlu Belge Tanımları — Config Fallback
|--------------------------------------------------------------------------
| GuestRequiredDocument tablosunda kayıt yoksa bu config devreye girer.
| Her application_type için stage (guest | student) bazlı liste.
|
| Alan formatı:
|   document_code, category_code, top_category_code, name,
|   description, is_required, accepted, max_mb
*/

return [

    // ── Guest aşaması (GuestApplication kaydı henüz student'a dönüşmeden) ──────
    'guest' => [

        '_default' => [
            ['document_code' => 'GSTP-001', 'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Geçerli pasaport veya kimlik belgesi',          'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTP-002', 'category_code' => 'diploma_lise',       'top_category_code' => 'egitim_belgeleri','name' => 'Lise Diploması',               'description' => 'Lise veya dengi mezuniyet belgesi',             'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTP-003', 'category_code' => 'transkript_lise',    'top_category_code' => 'egitim_belgeleri','name' => 'Lise Transkripti',             'description' => 'Notlu ve mühürlü lise transkripti',             'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTP-004', 'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Dil Belgesi (Almanca/İngilizce)', 'description' => 'TestDaF, DSH, IELTS, TOEFL veya muadili',  'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTP-005', 'category_code' => 'banka_dekontu',      'top_category_code' => 'mali_belgeler',   'name' => 'Mali Yeterlilik Belgesi',      'description' => 'Banka hesap özeti veya bloke hesap belgesi',    'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            // ── Vize öncesi belge ihtiyaçları (manager talep edebilir, opsiyonel) ──
            ['document_code' => 'GSTP-006', 'category_code' => 'wohnsitz_belgesi',   'top_category_code' => 'vize',  'name' => 'Wohnsitzbescheinigung (Nüfus / İkametgah)', 'description' => 'E-Devlet\'ten alınmış nüfus kayıt örneği veya ikametgah belgesi (Almanca tercüme önerilir)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],

        'bachelor' => [
            ['document_code' => 'GSTB-001', 'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Geçerli pasaport veya kimlik belgesi',          'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-002', 'category_code' => 'diploma_lise',       'top_category_code' => 'egitim_belgeleri','name' => 'Lise Diploması',               'description' => 'Lise mezuniyet belgesi (apostil önerilir)',      'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-003', 'category_code' => 'transkript_lise',    'top_category_code' => 'egitim_belgeleri','name' => 'Lise Transkripti',             'description' => 'Notlu, mühürlü, apostilli lise transkripti',    'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-004', 'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Almanca Dil Belgesi',          'description' => 'B1+ Almanca dil belgesi (TestDaF, DSH, Goethe)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-005', 'category_code' => 'motivasyon_mektubu', 'top_category_code' => 'basvuru_dosyasi', 'name' => 'Motivasyon Mektubu',           'description' => 'Almanca yazılmış motivasyon mektubu',            'is_required' => false, 'accepted' => 'pdf,docx',    'max_mb' => 5],
            // ── Bachelor için akademik denklik + vize belgeleri ──
            ['document_code' => 'GSTB-006', 'category_code' => 'aps_sertifikasi',    'top_category_code' => 'uni_assist','name' => 'APS Sertifikası',             'description' => 'Akademik Tetkik Merkezi (Almanya konsolosluğu) — Türk lise mezunları için zorunlu, lisans öncesi alınır', 'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-007', 'category_code' => 'vpd_anerkennung',    'top_category_code' => 'uni_assist','name' => 'VPD (Vorprüfungsdokumentation)','description' => 'Uni-Assist tarafından hazırlanan denklik dökümanı; bazı üniversiteler doğrudan VPD ister', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-008', 'category_code' => 'sperrkonto_belgesi', 'top_category_code' => 'vize',   'name' => 'Sperrkonto Bestätigung',      'description' => 'Bloke hesap onay belgesi (Coracle/Fintiba/Expatrio/Deutsche Bank) — vize başvurusu için kritik', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTB-009', 'category_code' => 'kranken_sigorta',    'top_category_code' => 'vize',  'name' => 'Krankenversicherung (Sağlık Sigortası)', 'description' => 'Mawista, Care Concept veya muadili — min. 30.000 € kapsamlı poliçe (vize için zorunlu)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],

        'master' => [
            ['document_code' => 'GSTM-001', 'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Geçerli pasaport veya kimlik belgesi',          'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTM-002', 'category_code' => 'diploma_lisans',     'top_category_code' => 'egitim_belgeleri','name' => 'Lisans Diploması',             'description' => 'Lisans mezuniyet belgesi + apostil',            'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTM-003', 'category_code' => 'transkript_lisans',  'top_category_code' => 'egitim_belgeleri','name' => 'Lisans Transkripti',           'description' => 'Lisans transkripti (TR → DE tercümeli önerilir)','is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTM-004', 'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Dil Belgesi',                  'description' => 'B2+ Almanca veya C1 İngilizce dil belgesi',     'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTM-005', 'category_code' => 'motivasyon_mektubu', 'top_category_code' => 'basvuru_dosyasi', 'name' => 'Motivasyon Mektubu',           'description' => 'Almanca yazılmış motivasyon mektubu (1-2 sayfa)','is_required' => true,  'accepted' => 'pdf,docx',    'max_mb' => 5],
            ['document_code' => 'GSTM-006', 'category_code' => 'referans_mektubu',   'top_category_code' => 'basvuru_dosyasi', 'name' => 'Referans Mektubu',             'description' => 'En az 1 akademik/profesyonel referans mektubu', 'is_required' => false, 'accepted' => 'pdf,docx',    'max_mb' => 5],
            ['document_code' => 'GSTM-007', 'category_code' => 'ozgecmis',           'top_category_code' => 'basvuru_dosyasi', 'name' => 'Özgeçmiş (CV)',               'description' => 'Almanca veya İngilizce güncel CV',               'is_required' => true,  'accepted' => 'pdf,docx',    'max_mb' => 5],
            // ── Master için akademik denklik + vize belgeleri ──
            ['document_code' => 'GSTM-008', 'category_code' => 'vpd_anerkennung',    'top_category_code' => 'uni_assist','name' => 'VPD (Vorprüfungsdokumentation)','description' => 'Uni-Assist tarafından hazırlanan denklik dökümanı; bazı üniversiteler doğrudan VPD ister', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTM-009', 'category_code' => 'sperrkonto_belgesi', 'top_category_code' => 'vize',   'name' => 'Sperrkonto Bestätigung',      'description' => 'Bloke hesap onay belgesi (Coracle/Fintiba/Expatrio/Deutsche Bank) — vize başvurusu için kritik', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTM-010', 'category_code' => 'kranken_sigorta',    'top_category_code' => 'vize',  'name' => 'Krankenversicherung (Sağlık Sigortası)', 'description' => 'Mawista, Care Concept veya muadili — min. 30.000 € kapsamlı poliçe (vize için zorunlu)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],

        'ausbildung' => [
            ['document_code' => 'GSTA-001', 'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Geçerli pasaport veya kimlik belgesi',          'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTA-002', 'category_code' => 'diploma_lise',       'top_category_code' => 'egitim_belgeleri','name' => 'Lise Diploması',               'description' => 'Lise veya dengi mezuniyet belgesi',             'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTA-003', 'category_code' => 'transkript_lise',    'top_category_code' => 'egitim_belgeleri','name' => 'Lise Transkripti',             'description' => 'Notlu lise transkripti',                        'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTA-004', 'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Almanca Dil Belgesi (en az B1)', 'description' => 'Ausbildung için B1-B2 Almanca zorunlu',       'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTA-005', 'category_code' => 'ozgecmis',           'top_category_code' => 'basvuru_dosyasi', 'name' => 'Özgeçmiş (CV)',               'description' => 'Almanca güncel CV',                             'is_required' => true,  'accepted' => 'pdf,docx',    'max_mb' => 5],
            // ── Ausbildung için adli sicil + vize belgeleri ──
            ['document_code' => 'GSTA-006', 'category_code' => 'adli_sicil',         'top_category_code' => 'vize',  'name' => 'Adli Sicil Kaydı',            'description' => 'E-Devlet\'ten alınan adli sicil belgesi + Almanca tercüme — Ausbildung/iş vizesi için zorunlu', 'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTA-007', 'category_code' => 'sperrkonto_belgesi', 'top_category_code' => 'vize',   'name' => 'Sperrkonto Bestätigung',      'description' => 'Bloke hesap onay belgesi (Coracle/Fintiba/Expatrio/Deutsche Bank)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTA-008', 'category_code' => 'kranken_sigorta',    'top_category_code' => 'vize',  'name' => 'Krankenversicherung (Sağlık Sigortası)', 'description' => 'Min. 30.000 € kapsamlı poliçe (vize için zorunlu)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],

        'sprachkurs' => [
            ['document_code' => 'GSTS-001', 'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Geçerli pasaport veya kimlik belgesi',          'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTS-002', 'category_code' => 'diploma_lise',       'top_category_code' => 'egitim_belgeleri','name' => 'Lise Diploması veya öğrenci belgesi', 'description' => 'Mevcut eğitim durumunu gösterir belge',  'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTS-003', 'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Mevcut Dil Belgesi (varsa)',   'description' => 'Herhangi bir seviyede mevcut Almanca belgesi',   'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            // ── Sprachkurs için kabul mektubu + vize belgeleri ──
            ['document_code' => 'GSTS-004', 'category_code' => 'dil_okul_kayit',     'top_category_code' => 'dil_okulu',       'name' => 'Anmeldebestätigung Sprachkurs (Dil Okulu Kayıt Yazısı)', 'description' => 'Almanca dil okulundan alınan resmi kayıt onay yazısı — vize için zorunlu (studienvorbereitend)', 'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTS-005', 'category_code' => 'sperrkonto_belgesi', 'top_category_code' => 'vize',   'name' => 'Sperrkonto Bestätigung',      'description' => 'Bloke hesap onay belgesi (Coracle/Fintiba/Expatrio)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'GSTS-006', 'category_code' => 'kranken_sigorta',    'top_category_code' => 'vize',  'name' => 'Krankenversicherung (Sağlık Sigortası)', 'description' => 'Min. 30.000 € kapsamlı poliçe (vize için zorunlu)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],
    ],

    // ── Student aşaması (GuestApplication → student'a dönüştükten sonra) ───────
    'student' => [

        '_default' => [
            ['document_code' => 'STUD-001', 'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Güncel pasaport veya kimlik belgesi',           'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STUD-002', 'category_code' => 'diploma',            'top_category_code' => 'egitim_belgeleri','name' => 'Diploma',                      'description' => 'Mezuniyet diploması + apostil',                 'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STUD-003', 'category_code' => 'transkript',         'top_category_code' => 'egitim_belgeleri','name' => 'Transkript',                   'description' => 'Notlu transkript + apostil',                    'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STUD-004', 'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Dil Belgesi',                  'description' => 'Almanca/İngilizce yeterlilik belgesi',           'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            // ── Student aşamasında vize randevu hazırlığı belgeleri ──
            ['document_code' => 'STUD-005', 'category_code' => 'sperrkonto_belgesi', 'top_category_code' => 'vize',   'name' => 'Sperrkonto Bestätigung',      'description' => 'Bloke hesap onay belgesi (vize randevusu öncesi hazırlanır)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STUD-006', 'category_code' => 'kranken_sigorta',    'top_category_code' => 'vize',  'name' => 'Krankenversicherung (Sağlık Sigortası)', 'description' => 'Mawista / Care Concept poliçesi (min. 30.000 € kapsam)', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STUD-007', 'category_code' => 'idata_dekont',       'top_category_code' => 'vize',  'name' => 'iDATA / Konsolosluk Ödeme Dekontu', 'description' => 'Vize ücreti (75 €) ödeme makbuzu — iDATA üzerinden yapılır', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STUD-008', 'category_code' => 'wohnsitz_belgesi',   'top_category_code' => 'vize',  'name' => 'Wohnsitzbescheinigung (Nüfus / İkametgah)', 'description' => 'E-Devlet\'ten alınmış nüfus kayıt örneği veya ikametgah belgesi', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],

        'bachelor' => [
            ['document_code' => 'STB-001',  'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Güncel pasaport veya kimlik belgesi',           'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STB-002',  'category_code' => 'diploma_lise',       'top_category_code' => 'egitim_belgeleri','name' => 'Lise Diploması + Apostil',     'description' => 'Resmi apostilli lise diploması',                'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STB-003',  'category_code' => 'transkript_lise',    'top_category_code' => 'egitim_belgeleri','name' => 'Lise Transkripti + Apostil',   'description' => 'Resmi apostilli notlu transkript',               'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STB-004',  'category_code' => 'tercume_diploma',    'top_category_code' => 'tercumeler',      'name' => 'Diploma Tercümesi (Almanca)',  'description' => 'Yeminli tercüman onaylı Almanca çeviri',        'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STB-005',  'category_code' => 'tercume_transkript', 'top_category_code' => 'tercumeler',      'name' => 'Transkript Tercümesi (Almanca)', 'description' => 'Yeminli tercüman onaylı Almanca transkript çevirisi', 'is_required' => true, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STB-006',  'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Almanca Dil Belgesi (B2/C1)', 'description' => 'TestDaF 4x4 / DSH-2 veya muadili',              'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STB-007',  'category_code' => 'motivasyon_mektubu', 'top_category_code' => 'basvuru_dosyasi', 'name' => 'Motivasyon Mektubu (DE)',     'description' => 'Almanca yazılmış motivasyon mektubu',            'is_required' => false, 'accepted' => 'pdf,docx',    'max_mb' => 5],
            ['document_code' => 'STB-008',  'category_code' => 'ozgecmis',           'top_category_code' => 'basvuru_dosyasi', 'name' => 'CV (Almanca)',                'description' => 'Almanca güncel özgeçmiş (Lebenslauf)',           'is_required' => false, 'accepted' => 'pdf,docx',    'max_mb' => 5],
        ],

        'master' => [
            ['document_code' => 'STM-001',  'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Güncel pasaport veya kimlik belgesi',           'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STM-002',  'category_code' => 'diploma_lisans',     'top_category_code' => 'egitim_belgeleri','name' => 'Lisans Diploması + Apostil',   'description' => 'Resmi apostilli lisans diploması',              'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STM-003',  'category_code' => 'transkript_lisans',  'top_category_code' => 'egitim_belgeleri','name' => 'Lisans Transkripti + Apostil', 'description' => 'Resmi apostilli notlu transkript',               'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STM-004',  'category_code' => 'tercume_diploma',    'top_category_code' => 'tercumeler',      'name' => 'Diploma Tercümesi (Almanca)',  'description' => 'Yeminli tercüman onaylı Almanca çeviri',        'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STM-005',  'category_code' => 'tercume_transkript', 'top_category_code' => 'tercumeler',      'name' => 'Transkript Tercümesi (Almanca)', 'description' => 'Yeminli tercüman onaylı Almanca transkript çevirisi', 'is_required' => true, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STM-006',  'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Dil Belgesi (B2+/C1)',        'description' => 'Almanca B2+ veya İngilizce C1 belgesi',         'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STM-007',  'category_code' => 'motivasyon_mektubu', 'top_category_code' => 'basvuru_dosyasi', 'name' => 'Motivasyon Mektubu (DE)',     'description' => 'Almanca yazılmış motivasyon mektubu (2 sayfa)',  'is_required' => true,  'accepted' => 'pdf,docx',    'max_mb' => 5],
            ['document_code' => 'STM-008',  'category_code' => 'referans_mektubu',   'top_category_code' => 'basvuru_dosyasi', 'name' => 'Referans Mektubu',            'description' => 'Akademik referans mektubu',                     'is_required' => false, 'accepted' => 'pdf,docx',    'max_mb' => 5],
            ['document_code' => 'STM-009',  'category_code' => 'ozgecmis',           'top_category_code' => 'basvuru_dosyasi', 'name' => 'CV (Almanca/İngilizce)',      'description' => 'Güncel Almanca veya İngilizce özgeçmiş',        'is_required' => true,  'accepted' => 'pdf,docx',    'max_mb' => 5],
            ['document_code' => 'STM-010',  'category_code' => 'banka_dekontu',      'top_category_code' => 'mali_belgeler',   'name' => 'Mali Yeterlilik / Sperrkonto', 'description' => 'Bloke hesap belgesi veya mali yeterlilik kanıtı', 'is_required' => false, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
        ],

        'ausbildung' => [
            ['document_code' => 'STA-001',  'category_code' => 'kimlik_pasaport',    'top_category_code' => 'kimlik',          'name' => 'Pasaport / Kimlik',            'description' => 'Güncel pasaport veya kimlik belgesi',           'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STA-002',  'category_code' => 'diploma_lise',       'top_category_code' => 'egitim_belgeleri','name' => 'Lise Diploması',               'description' => 'Mezuniyet belgesi',                             'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STA-003',  'category_code' => 'transkript_lise',    'top_category_code' => 'egitim_belgeleri','name' => 'Lise Transkripti',             'description' => 'Notlu transkript',                              'is_required' => true,  'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STA-004',  'category_code' => 'dil_belgesi',        'top_category_code' => 'dil_belgeleri',   'name' => 'Almanca Dil Belgesi (B2)',     'description' => 'B2 Almanca yeterlilik belgesi (Ausbildung için zorunlu)', 'is_required' => true, 'accepted' => 'pdf,jpg,png', 'max_mb' => 10],
            ['document_code' => 'STA-005',  'category_code' => 'ozgecmis',           'top_category_code' => 'basvuru_dosyasi', 'name' => 'CV (Almanca)',                'description' => 'Almanca güncel özgeçmiş',                       'is_required' => true,  'accepted' => 'pdf,docx',    'max_mb' => 5],
        ],

    ],

];
