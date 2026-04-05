<?php

/**
 * Kurumlardan gelen belge katalogu.
 * Senior tarafindan ogrenci bazli belge takibinde kullanilir.
 *
 * Yapi: categories[category_key] => [label_tr, label_de, label_en, icon, documents[code => [tr, de]]]
 */
return [
    'categories' => [

        'uni_assist' => [
            'label_tr' => 'Uni-Assist',
            'label_de' => 'Uni-Assist',
            'label_en' => 'Uni-Assist',
            'icon'     => 'UA',
            'documents' => [
                'UA-EINGANG'  => ['tr' => 'Eingangsbestätigung (Basvuru alindi onayi)', 'de' => 'Eingangsbestätigung'],
                'UA-STATUS'   => ['tr' => 'Bearbeitungsstatus (Portal/mail ciktisi)', 'de' => 'Bearbeitungsstatus'],
                'UA-NACHF'    => ['tr' => 'Nachforderung (Eksik belge talebi)', 'de' => 'Nachforderung'],
                'UA-ZAHLUNG'  => ['tr' => 'Zahlungsbestätigung (Ucret alindi)', 'de' => 'Zahlungsbestätigung'],
                'UA-PRUEF'    => ['tr' => 'Prüfungsergebnis (Sinav/degerl. sonucu)', 'de' => 'Prüfungsergebnis'],
                'UA-VPD'      => ['tr' => 'VPD – Vorprüfungsdokument (On inceleme belgesi)', 'de' => 'Vorprüfungsdokument (VPD)'],
                'UA-BEW'      => ['tr' => 'Bewerbungsübersicht PDF', 'de' => 'Bewerbungsübersicht'],
                'UA-WEITER'   => ['tr' => 'Hinweise zur Weiterleitung (Iletim bildirimi)', 'de' => 'Weiterleitung'],
            ],
        ],

        'university' => [
            'label_tr' => 'Üniversite',
            'label_de' => 'Hochschule / Universität',
            'label_en' => 'University',
            'icon'     => 'UNI',
            'documents' => [
                'UNI-BEWTG'    => ['tr' => 'Bewerbungsbestätigung (Basvuru alindi)', 'de' => 'Bewerbungsbestätigung'],
                'UNI-ZULAS'    => ['tr' => 'Zulassungsbescheid (Kabul mektubu)', 'de' => 'Zulassungsbescheid'],
                'UNI-ABLEH'    => ['tr' => 'Ablehnungsbescheid (Ret mektubu)', 'de' => 'Ablehnungsbescheid'],
                'UNI-VORB'     => ['tr' => 'Zulassung unter Vorbehalt (Sartli kabul)', 'de' => 'Zulassung unter Vorbehalt'],
                'UNI-FRIST'    => ['tr' => 'Fristenschreiben (Son tarih hatirlatma)', 'de' => 'Fristenschreiben'],
                'UNI-IMMA'     => ['tr' => 'Immatrikulationsunterlagen (Kayit yonergesi paketi)', 'de' => 'Immatrikulationsunterlagen'],
                'UNI-ANTRAG'   => ['tr' => 'Immatrikulationsantrag (Kayit formu PDF)', 'de' => 'Immatrikulationsantrag'],
                'UNI-SEMB'     => ['tr' => 'Semesterbeitrag-Rechnung (Harc faturasi)', 'de' => 'Semesterbeitrag-Rechnung'],
                'UNI-SEMZAHL'  => ['tr' => 'Zahlungsbestätigung Semesterbeitrag (Harc odendi)', 'de' => 'Zahlungsbestätigung'],
                'UNI-STUDBSCH' => ['tr' => 'Immatrikulationsbescheinigung (Ogrenci belgesi)', 'de' => 'Immatrikulationsbescheinigung'],
                'UNI-MATR'     => ['tr' => 'Matrikelnummer bildirimi', 'de' => 'Matrikelnummer'],
                'UNI-RUECKN'   => ['tr' => 'Rücknahmebestätigung (Basvuru geri cekildi)', 'de' => 'Rücknahmebestätigung'],
                'UNI-PORTAL'   => ['tr' => 'Portal mesaji ciktisi (HISinOne/Stud.IP vb.)', 'de' => 'Portalnachricht'],
                'UNI-CHIP'     => ['tr' => 'Deutschlandsemesterticket / Chipkarte bilgisi', 'de' => 'Semesterticket / Chipkarte'],
            ],
        ],

        'hochschulstart' => [
            'label_tr' => 'Hochschulstart',
            'label_de' => 'Hochschulstart / Stiftung für Hochschulzulassung',
            'label_en' => 'Hochschulstart',
            'icon'     => 'HS',
            'documents' => [
                'HS-REGBEST'  => ['tr' => 'Bewerbungs-/Registrierungsbestätigung', 'de' => 'Registrierungsbestätigung'],
                'HS-ANGEBOT'  => ['tr' => 'Zulassungsangebot / Angebot (Kabul teklifi)', 'de' => 'Zulassungsangebot'],
                'HS-KOORD'    => ['tr' => 'Koordinierungsregeln bildirimi', 'de' => 'Koordinierungsregeln'],
                'HS-FRIST'    => ['tr' => 'Fristen / Ausschlussfrist yazisi', 'de' => 'Ausschlussfrist'],
            ],
        ],

        'visa' => [
            'label_tr' => 'Vize Konsolosluğu',
            'label_de' => 'Visumsstelle / Konsulat',
            'label_en' => 'Visa / Consulate',
            'icon'     => 'VIS',
            'documents' => [
                'VIS-ANTR'    => ['tr' => 'Antragsbestätigung / Özet PDF (Basvuru onay)', 'de' => 'Antragsbestätigung'],
                'VIS-KORR'    => ['tr' => 'Korrekturaufforderung / Nachforderung (Duzeltme talebi)', 'de' => 'Nachforderung'],
                'VIS-TERM'    => ['tr' => 'Terminbestätigung (Randevu teyidi)', 'de' => 'Terminbestätigung'],
                'VIS-VERS'    => ['tr' => 'Terminverschiebung / Absage (Randevu degisikligi/iptali)', 'de' => 'Terminabsage'],
                'VIS-GEB'     => ['tr' => 'Gebührenbescheid / Zahlungsaufforderung (Odeme talimati)', 'de' => 'Gebührenbescheid'],
                'VIS-GEBZAHL' => ['tr' => 'Zahlungsbestätigung / Quittung (Vize ucreti odendi)', 'de' => 'Zahlungsquittung'],
                'VIS-EINGANG' => ['tr' => 'Eingangsbestätigung Unterlagen (Evrak teslim alindi)', 'de' => 'Eingangsbestätigung'],
                'VIS-REMONS'  => ['tr' => 'Remonstrationshinweis / Ablehnung (Ret/itiraz)', 'de' => 'Ablehnung'],
                'VIS-ERTEIL'  => ['tr' => 'Visum-Erteilungsmitteilung (Vize verildi bildirimi)', 'de' => 'Visumsmitteilung'],
                'VIS-PASS'    => ['tr' => 'Passport collection / Abholung bilgilendirmesi', 'de' => 'Abholungsinfo'],
            ],
        ],

        'sperrkonto' => [
            'label_tr' => 'Sperrkonto (Blokeli Hesap)',
            'label_de' => 'Sperrkonto',
            'label_en' => 'Blocked Account',
            'icon'     => 'SPK',
            'documents' => [
                'SPK-EROEFF'  => ['tr' => 'Eröffnungsbestätigung (Hesap acilis onay)', 'de' => 'Eröffnungsbestätigung'],
                'SPK-SPERR'   => ['tr' => 'Sperrbestätigung / Blocking confirmation (Bloke onay)', 'de' => 'Sperrbestätigung'],
                'SPK-EINZ'    => ['tr' => 'Einzahlungsaufforderung (Para yatirma talimati)', 'de' => 'Einzahlungsaufforderung'],
                'SPK-GELD'    => ['tr' => 'Geldeingang / Deposit confirmation (Para ulasti)', 'de' => 'Geldeingangsbestätigung'],
                'SPK-IBAN'    => ['tr' => 'Kontodaten / IBAN bilgisi yazisi', 'de' => 'Kontodaten'],
                'SPK-FREIG'   => ['tr' => 'Freigabeplan / Auszahlungsplan (Aylik limit bilgisi)', 'de' => 'Auszahlungsplan'],
                'SPK-AKTIV'   => ['tr' => 'Aktivierungsbestätigung (Almanya\'da aktive edildi)', 'de' => 'Aktivierungsbestätigung'],
            ],
        ],

        'health_insurance' => [
            'label_tr' => 'Sağlık Sigortası',
            'label_de' => 'Krankenversicherung',
            'label_en' => 'Health Insurance',
            'icon'     => 'KV',
            'documents' => [
                'KV-TRAVEL'   => ['tr' => 'Seyahat sigortasi Police/Confirmation (kisa sureli, vize icin)', 'de' => 'Reiseversicherungsbestätigung'],
                'KV-TRVPOL'   => ['tr' => 'Seyahat sigortasi Police PDF', 'de' => 'Police PDF'],
                'KV-TRVZAHL'  => ['tr' => 'Seyahat sigortasi Zahlungsbestätigung', 'de' => 'Zahlungsbestätigung'],
                'KV-MITGL'    => ['tr' => 'Mitgliedsbescheinigung (TK/Barmer/AOK uyelik belgesi)', 'de' => 'Mitgliedsbescheinigung'],
                'KV-M10'      => ['tr' => 'Elektronische Meldung an Hochschule (M10 okul bildirimi)', 'de' => 'M10-Meldung'],
            ],
        ],

        'language_course' => [
            'label_tr' => 'Dil Kursu',
            'label_de' => 'Sprachkurs',
            'label_en' => 'Language Course',
            'icon'     => 'DK',
            'documents' => [
                'DK-ANMELD'   => ['tr' => 'Anmeldebestätigung (Kayit onay)', 'de' => 'Anmeldebestätigung'],
                'DK-ZAHLUNG'  => ['tr' => 'Zahlungsbestätigung (Kurs ucreti odendi)', 'de' => 'Zahlungsbestätigung'],
                'DK-RECHNUNG' => ['tr' => 'Rechnung (Fatura)', 'de' => 'Rechnung'],
                'DK-VERTRAG'  => ['tr' => 'Kursvertrag / Teilnahmebedingungen (Sozlesme)', 'de' => 'Kursvertrag'],
                'DK-STUNDENP' => ['tr' => 'Stundenplan (Ders programi)', 'de' => 'Stundenplan'],
                'DK-EINSTUF'  => ['tr' => 'Einstufungstest sonucu (Placement test)', 'de' => 'Einstufungstest'],
                'DK-TEILN'    => ['tr' => 'Teilnahmebestätigung (Kurs devam ediyor)', 'de' => 'Teilnahmebestätigung'],
                'DK-ZERT'     => ['tr' => 'Zertifikat / Teilnahmezertifikat (Kurs tamamlandi)', 'de' => 'Zertifikat'],
            ],
        ],

        'accommodation' => [
            'label_tr' => 'Yurt / Konaklama',
            'label_de' => 'Wohnheim / Unterkunft',
            'label_en' => 'Accommodation',
            'icon'     => 'WH',
            'documents' => [
                'WH-BEWBEST'  => ['tr' => 'Bewerbungsbestätigung (Yurt basvurusu alindi)', 'de' => 'Bewerbungsbestätigung'],
                'WH-WARTE'    => ['tr' => 'Wartelistenbestätigung (Bekleme listesi)', 'de' => 'Wartelistenbestätigung'],
                'WH-ANGEBOT'  => ['tr' => 'Zimmerangebot (Oda teklifi)', 'de' => 'Zimmerangebot'],
                'WH-VERTRAG'  => ['tr' => 'Mietvertrag / Wohnheimvertrag (Kira sozlesmesi)', 'de' => 'Mietvertrag'],
                'WH-KAUTION'  => ['tr' => 'Kaution-Rechnung / Zahlungsaufforderung (Depozito talebi)', 'de' => 'Kautionsrechnung'],
                'WH-KAUTZAHL' => ['tr' => 'Zahlungsbestätigung Kaution (Depozito odendi)', 'de' => 'Zahlungsbestätigung'],
                'WH-EINZUG'   => ['tr' => 'Einzugsbestätigung / Übergabeprotokoll (Giris tutanagi)', 'de' => 'Übergabeprotokoll'],
                'WH-HAUSORD'  => ['tr' => 'Hausordnung / AGB (Yurt kurallari)', 'de' => 'Hausordnung'],
            ],
        ],

        'municipality' => [
            'label_tr' => 'Belediye & Bürokrasi',
            'label_de' => 'Amt & Bürokratie',
            'label_en' => 'Municipality & Bureaucracy',
            'icon'     => 'AMT',
            'documents' => [
                'AMT-MELDE'   => ['tr' => 'Meldebestätigung / Anmeldebestätigung (Ikamet kaydi)', 'de' => 'Meldebestätigung'],
                'AMT-AUFTERM' => ['tr' => 'Aufenthaltstitel Terminbestätigung (Oturum randevusu)', 'de' => 'Terminbestätigung'],
                'AMT-FIKTION' => ['tr' => 'Fiktionsbescheinigung (Oturum karti beklenirken)', 'de' => 'Fiktionsbescheinigung'],
                'AMT-AUFBSCH' => ['tr' => 'Aufenthaltstitel Bescheid (Oturum karti karari)', 'de' => 'Aufenthaltstitel Bescheid'],
                'AMT-EAT'     => ['tr' => 'eAT Abholbrief (Oturum karti teslim alma)', 'de' => 'eAT Abholbrief'],
                'AMT-STEUER'  => ['tr' => 'Steuer-ID mektubu (Vergi kimlik numarasi)', 'de' => 'Steuer-ID'],
                'AMT-RUNDF'   => ['tr' => 'Rundfunkbeitrag mektubu', 'de' => 'Rundfunkbeitrag'],
            ],
        ],

        'other' => [
            'label_tr' => 'Diğer',
            'label_de' => 'Sonstiges',
            'label_en' => 'Other',
            'icon'     => 'DGR',
            'documents' => [
                'OTH-APS'   => ['tr' => 'APS Sertifikasi / Dogulama yazisi', 'de' => 'APS-Zertifikat'],
                'OTH-DENKL' => ['tr' => 'Denklik / Zeugnisbewertung', 'de' => 'Zeugnisbewertung'],
                'OTH-TESTAS'=> ['tr' => 'TestAS / DSH / TestDaF / Goethe / telc sonucu', 'de' => 'Testergebnis'],
                'OTH-NOTER' => ['tr' => 'Noter / Tercume faturasi', 'de' => 'Notarrechnung'],
                'OTH-KARGO' => ['tr' => 'Kargo / Posta takip ciktisi', 'de' => 'Paketnachweis'],
            ],
        ],

    ],
];
