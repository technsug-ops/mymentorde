<?php
return [
    'munich' => [
        // === VERY DETAILED - München ===
        'slug'        => 'munich',
        'name'        => 'München',
        'state'       => 'Bayern',
        'emoji'       => '🏔',
        'tagline'     => 'Teknoloji, finans ve prestijin başkenti',
        'hero_color'  => 'linear-gradient(135deg,#1d4ed8,#7c3aed)',
        'hero_video_id' => 'LzLOhMsjpsw',     // YouTube video ID (boşsa gösterilmez)
        'hero_video_thumb' => 'https://images.unsplash.com/photo-1595867818082-083862f3d630?w=800&q=80',              // Özel kapak resmi URL'i (boşsa YouTube'dan alınır). Ör: '/img/cities/munich-cover.jpg'
        'cost_index'  => 5, // 1-5 pahalılık
        'student_pop' => '120.000+',
        'population'  => '1,56 milyon',

        'overview' => 'Almanya\'nın üçüncü büyük şehri, ekonomik açıdan en güçlüsü. BMW, Siemens, Allianz gibi dev şirketlerin merkezi. TU München dünya sıralamasında ilk 50\'de. Pahalı ama kariyer fırsatları açısından eşsiz.',

        'location' => [
            'region'      => 'Güney Almanya, Bavyera eyaleti',
            'coordinates' => '48.1351° N, 11.5820° E',
            'altitude_m'  => 520,
            'border'      => 'Avusturya\'ya 100 km, İsviçre\'ye 180 km',
            'airport'     => 'MUC — Franz Josef Strauß (Avrupa\'nın 3. büyüğü)',
            'train_hubs'  => ['ICE ile Frankfurt 3 saat', 'ICE ile Berlin 4 saat', 'EC ile Viyana 4 saat'],
            'city_transport' => 'U-Bahn (metro), S-Bahn, Tram, Bus — Deutschlandticket €29/ay',
            'geography'   => 'Isar nehri kıyısında, Alplere 60 km mesafe. Kış sporları erişimi mükemmel.',
        ],

        'culture' => [
            'personality'  => 'Geleneksel Bavyera kültürü ile modern kozmopolitliğin birleşimi',
            'languages'    => ['Almanca (standart)', 'Bayerisch (yerel lehçe)', 'İngilizce (teknoloji sektöründe yaygın)'],
            'notable_for'  => ['Oktoberfest', 'BMW & Otomotiv', 'Alp sporları', 'Bira kültürü', 'Klasik müzik'],
            'student_life' => 'Hareketli ama pahalı. Englischer Garten\'da piknik öğrenci geleneği. Mensa kültürü güçlü, öğle yemeği €2-5.',
            'nightlife'    => 'Glockenbachviertel ve Schwabing semtleri aktif. Bar, canlı müzik, küçük kulüpler.',
            'events'       => [
                'Oktoberfest (Eylül-Ekim)' => 'Dünyanın en büyük halk festivali',
                'Tollwood' => 'Yaz ve kış kültür festivali',
                'Auer Dult' => 'Geleneksel Bavyera pazarı',
                'IHM' => 'Uluslararası El Sanatları Fuarı',
                'EXPO REAL' => 'Avrupa\'nın en büyük gayrimenkul fuarı',
            ],
            'turkish_community' => 'Yaklaşık 50.000 Türk kökenli Münih sakinleri. Hauptbahnhof çevresinde Türk mahalleleri. Türk süpermarketleri, restoranlar, kültür dernekleri mevcut.',
        ],

        'universities' => [
            [
                'name'          => 'Technische Universität München (TUM)',
                'short'         => 'TUM',
                'type'          => 'Teknik Üniversite',
                'founded'       => 1868,
                'students'      => 50000,
                'qs_ranking'    => 37,
                'the_ranking'   => 30,
                'strengths'     => ['Makine Mühendisliği', 'Bilgisayar Bilimi', 'Fizik', 'Biyoteknoloji', 'İşletme (TUM School of Management)'],
                'english_programs' => true,
                'uni_assist'    => true,
                'nc_required'   => false,
                'campuses'      => ['Garching (fen/müh)', 'Innenstadt (merkez)', 'Weihenstephan (biyoloji/ziraat)', 'Heilbronn (yönetim)'],
                'acceptance_rate' => '%~40 (programa göre değişir)',
                'tuition'       => '€0 (dönem katkısı ~€144)',
                'notable_alumni' => ['Carl von Linde (soğutma icadı)', 'Rudolf Diesel (dizel motor)'],
                'industry_links' => 'BMW, Siemens, MAN ile güçlü endüstri bağlantısı. TUM Venture Lab aktif startup ekosistemi.',
                'note'          => 'Almanya\'nın en prestijli mühendislik üniversitesi. Başvuru oldukça rekabetçi.',
            ],
            [
                'name'          => 'Ludwig-Maximilians-Universität München (LMU)',
                'short'         => 'LMU',
                'type'          => 'Araştırma Üniversitesi',
                'founded'       => 1472,
                'students'      => 52000,
                'qs_ranking'    => 59,
                'the_ranking'   => 32,
                'strengths'     => ['Hukuk', 'Tıp', 'Felsefe', 'Ekonomi', 'İnsan Bilimleri'],
                'english_programs' => true,
                'uni_assist'    => true,
                'nc_required'   => true, // Tıp/Hukuk için
                'acceptance_rate' => 'Bölüme göre değişir',
                'tuition'       => '€0 (dönem katkısı ~€144)',
                'note'          => 'Almanya\'nın en eski ve köklü üniversitelerinden. Nobel ödüllü 43 mezunu var.',
            ],
            [
                'name'          => 'Hochschule München (HM)',
                'short'         => 'HM',
                'type'          => 'Fachhochschule (FH)',
                'founded'       => 1971,
                'students'      => 18000,
                'qs_ranking'    => null,
                'strengths'     => ['Mühendislik', 'İşletme', 'Tasarım', 'Sosyal Çalışma'],
                'english_programs' => true,
                'uni_assist'    => false,
                'note'          => 'Pratik eğitim, zorunlu staj, endüstri entegrasyonu güçlü.',
            ],
        ],

        'attractions' => [
            ['name' => 'Englischer Garten',       'type' => 'park',    'price' => 'Ücretsiz', 'note' => 'Central Park\'tan büyük. Sörf kanalı, bira bahçeleri, çıplak güneşlenme alanları.'],
            ['name' => 'Marienplatz & Glockenspiel', 'type' => 'tarihi', 'price' => 'Ücretsiz', 'note' => 'Şehrin kalbi. Neues Rathaus (Yeni Belediye Binası) ve canlı zil gösterisi.'],
            ['name' => 'BMW Müzesi',               'type' => 'müze',    'price' => '€10',      'note' => 'BMW fabrika turu ile birlikte yapılabilir. Otomotiv meraklısı öğrenciler için mutlaka.'],
            ['name' => 'Deutsches Museum',         'type' => 'müze',    'price' => '€15',      'note' => 'Dünyanın en büyük bilim ve teknoloji müzesi. Mühendislik öğrencisi için cennet.'],
            ['name' => 'Nymphenburg Sarayı',       'type' => 'tarihi',  'price' => '€8',       'note' => 'Bavyera krallık sarayı ve bahçeleri. Fotoğraf noktası.'],
            ['name' => 'Viktualienmarkt',          'type' => 'pazar',   'price' => 'Ücretsiz', 'note' => 'Açık hava gıda pazarı. Yerel ürünler, Bavyera lezzetleri.'],
            ['name' => 'Alpler (Zugspitze)',       'type' => 'doğa',    'price' => '€60+',     'note' => 'Almanya\'nın en yüksek noktası. Tren + teleferik ile gidilir.'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Çok Pahalı',
            'overall_index' => 5,
            'rent' => [
                'wg_room'     => '€700-1200',
                'studio'      => '€1100-1800',
                'studentenwohnheim' => '€250-500 (bekleme listesi var)',
            ],
            'food' => [
                'mensa_lunch'     => '€3-6',
                'grocery_monthly' => '€200-280',
                'restaurant_meal' => '€15-25',
                'kebab'           => '€5-7',
                'coffee'          => '€3.5-5',
            ],
            'transport' => [
                'deutschlandticket' => '€29/ay',
                'mvv_monthly'       => '€57/ay (bölgesel)',
                'bike'              => 'Önerilir — bisiklet altyapısı mükemmel',
            ],
            'leisure' => [
                'cinema'     => '€12-16',
                'beer_bar'   => '€6-9',
                'biergarten' => '€4-7 (1L bira)',
                'gym'        => '€25-60/ay',
            ],
            'monthly_total_estimate' => '€1.100-1.500 (tipik öğrenci)',
        ],

        'job_market' => [
            'overview' => 'Almanya\'nın en güçlü iş piyasası. İşsizlik oranı %3.5 (ulusal ortalamanın altında). Teknoloji, finans ve mühendislik sektörleri öne çıkıyor.',
            'dominant_sectors' => [
                [
                    'name'        => 'Otomotiv & Makine',
                    'collar'      => 'her ikisi',
                    'intensity'   => 5,
                    'description' => 'BMW merkezi Münih\'te. MAN Truck, Linde, MTU Aero Engines bölgede. Mühendislik mezunları için altın şehir. Hem üretim (mavi yaka) hem Ar-Ge (beyaz yaka) yoğun.',
                    'companies'   => ['BMW AG', 'MAN Truck & Bus', 'MTU Aero Engines', 'Knorr-Bremse'],
                ],
                [
                    'name'        => 'Finans & Sigorta',
                    'collar'      => 'beyaz yaka',
                    'intensity'   => 5,
                    'description' => 'Allianz (dünyanın en büyük sigorta şirketi), Munich Re, Bayerische LB, UniCredit Almanya merkezi. Finans ve ekonomi mezunları için Frankfurt kadar güçlü.',
                    'companies'   => ['Allianz SE', 'Munich Re', 'Bayerische Landesbank', 'UniCredit'],
                ],
                [
                    'name'        => 'Teknoloji & Yazılım',
                    'collar'      => 'beyaz yaka',
                    'intensity'   => 4,
                    'description' => 'Siemens, Microsoft Almanya, Google Almanya, Rohde & Schwarz. TUM mezunları için güçlü kariyer bağlantısı. Start-up ekosistemi büyüyor.',
                    'companies'   => ['Siemens AG', 'Microsoft DE', 'Google DE', 'Rohde & Schwarz', 'Celonis'],
                ],
                [
                    'name'        => 'Havacılık & Savunma',
                    'collar'      => 'her ikisi',
                    'intensity'   => 4,
                    'description' => 'Airbus headquarters Münih yakınında. MBDA, Cassidian. Havacılık mühendisliği için Avrupa\'nın merkezlerinden.',
                    'companies'   => ['Airbus (Ottobrunn)', 'MBDA Deutschland', 'Diehl Aviation'],
                ],
            ],
            'student_jobs'    => 'Gastro, perakende, BMW/Siemens Werkstudent pozisyonları bol. Saatlik €12-16 ortalama.',
            'avg_salary'      => '€52.000/yıl brüt (Almanya ortalamasının %25 üstü)',
            'unemployment'    => '%3.5',
            'after_graduation' => 'TUM/LMU mezunları için iş bulma süresi ortalama 3-4 ay. Almanya\'nın en kısa süresi.',
        ],

        'student_tips' => [
            'Bisiklet al' => 'Şehir içi ulaşımın %30\'u bisikletle. Kiralık €20-50/ay veya ikinci el €100-200.',
            'MVV uygulaması' => 'Toplu taşıma uygulaması. Deutschlandticket €29/ay ile tüm şehir ulaşımı.',
            'Studentenwerk yurdu' => 'Başvuruyu kabul mektubundan önce yapın — bekleme 6-12 ay.',
            'WG-Gesucht' => 'Oda arama sitesi. Güvenilir — gerçek ilanlar, fotoğraflı.',
            'Mensa kartı' => 'TUM/LMU öğrenci kartı ile mensada €2-5\'e sıcak yemek.',
            'Almanca öğren' => 'İngilizce ile hayatı idare edebilirsiniz ama günlük işler (Anmeldung, doktor) için B1 şart.',
            'Alp gezileri' => 'Öğrenci tren biletleriyle hafta sonu gezileri ucuz. Bayern Ticket €29 (9 kişiye kadar).',
        ],

        'pros_cons' => [
            'pros' => [
                'Dünya sınıfı üniversiteler (TUM top 37)',
                'BMW/Siemens/Allianz — güçlü kariyer',
                'Alpler yakın — kış sporları',
                'Güvenli ve temiz şehir',
                'Mükemmel toplu taşıma',
                'Uluslararası hava limanı',
            ],
            'cons' => [
                'Almanya\'nın en pahalı şehri',
                'Konut bulmak çok zor',
                'Yüksek yaşam maliyeti',
                'Bavyera lehçesi — standart Almanca farklı',
                'Frankfurt\'a kıyasla daha tutucu sosyal ortam',
            ],
        ],

        // 📹 Video İçerikleri — youtube_id alanını doldurun
        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'München\'de Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'München\'de bir öğrencinin günlük yaşamını anlatan vlog.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'TU München Kampüs Turu', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'TUM kampüslerinin (Garching, Innenstadt) detaylı turu.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'LMU München — Her Şey Hakkında', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Ludwig-Maximilians-Universität tanıtım ve öğrenci deneyimleri.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'München\'de Konut & Yurt Bulmak', 'category' => 'yaşam', 'duration' => '8:24', 'description' => 'Studentenwerk yurtları, WG arama, kira bütçesi.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Oktoberfest Rehberi — Öğrenci Gözünden', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Oktoberfest\'e nasıl gidilir, bütçe nasıl tutturulur.'],
        ],
    ],

    'berlin' => [
        'slug'        => 'berlin',
        'name'        => 'Berlin',
        'state'       => 'Berlin',
        'emoji'       => '🐻',
        'tagline'     => 'Sanat, teknoloji ve tarihin başkenti',
        'hero_color'  => 'linear-gradient(135deg,#2563eb,#0891b2)',
        'hero_video_id' => 'LzLOhMsjpsw',
        'hero_video_thumb' => 'https://images.unsplash.com/photo-1560969184-10fe8719e047?w=800&q=80',
        'cost_index'  => 3,
        'student_pop' => '180.000+',
        'population'  => '3,7 milyon',

        'overview' => 'Avrupa\'nın en büyük öğrenci şehirlerinden. 4 büyük üniversite, canlı sanat sahnesi, görece uygun fiyatlar. Soğuk Savaş tarihi, duvar kalıntıları. Start-up ekosistemi hızla büyüyor.',

        'location' => [
            'region'      => 'Kuzey-doğu Almanya, Brandenburg eyaletiyle çevrili',
            'airport'     => 'BER — Berlin Brandenburg Havalimanı (2020 açıldı)',
            'train_hubs'  => ['ICE ile Hamburg 1.5 saat', 'ICE ile Münih 4 saat', 'EC ile Varşova 5.5 saat'],
            'city_transport' => 'U-Bahn, S-Bahn, Tram, Bus — Deutschlandticket €29/ay',
            'geography'   => 'Spree ve Havel nehirleri arasında. Düz arazi, yoğun orman ve göl alanları.',
        ],

        'culture' => [
            'personality'  => 'Bohemian, çok kültürlü, sanatçı dostu, özgür ruhlu',
            'notable_for'  => ['Berlin Duvarı tarihi', 'Tekno müzik sahnesi (Berghain)', 'Galeri ve sanat', 'Start-up ekosistemi', 'LGBTQ+ dostu'],
            'student_life' => 'En canlı öğrenci şehri. Ucuz barlar, sanat galerileri, ücretsiz etkinlikler. Prenzlauer Berg ve Mitte populer öğrenci semtleri.',
            'events'       => [
                'Berlinale (Şubat)' => 'Dünyanın en prestijli film festivallerinden',
                'Berlin Müzik Haftası' => 'Canlı konserler, sahne sanatları',
                'IFA (Eylül)' => 'Dünyanın en büyük tüketici elektroniği fuarı',
                'ITB (Mart)' => 'Dünyanın en büyük turizm fuarı',
            ],
            'turkish_community' => 'Almanya\'nın en büyük Türk topluluğu. Neukölln ve Kreuzberg — "Klein Istanbul". Türkçe hizmet her yerde.',
        ],

        'universities' => [
            [
                'name'       => 'Technische Universität Berlin (TU Berlin)',
                'short'      => 'TU Berlin',
                'type'       => 'Teknik Üniversite',
                'students'   => 35000,
                'strengths'  => ['Mühendislik', 'Bilgisayar Bilimi', 'Mimarlık', 'Planlama'],
                'qs_ranking' => '~150',
                'note'       => 'Siemens, Deutsche Bahn ile güçlü endüstri bağlantısı.',
            ],
            [
                'name'       => 'Freie Universität Berlin (FU Berlin)',
                'short'      => 'FU Berlin',
                'type'       => 'Araştırma Üniversitesi',
                'students'   => 34000,
                'strengths'  => ['İnsan Bilimleri', 'Hukuk', 'Siyaset Bilimi', 'Tıp'],
                'qs_ranking' => '~125',
                'note'       => 'Uluslararası öğrenci oranı yüksek, İngilizce programlar güçlü.',
            ],
            [
                'name'       => 'Humboldt-Universität zu Berlin (HU Berlin)',
                'short'      => 'HU Berlin',
                'type'       => 'Araştırma Üniversitesi',
                'students'   => 33000,
                'strengths'  => ['Doğa Bilimleri', 'Hukuk', 'Tarih', 'Felsefe'],
                'qs_ranking' => '~120',
                'note'       => 'Almanya\'nın en köklü üniversitelerinden. Einstein ve Marx burada çalıştı.',
            ],
        ],

        'attractions' => [
            ['name' => 'Berlin Duvarı / East Side Gallery', 'type' => 'tarihi', 'price' => 'Ücretsiz'],
            ['name' => 'Brandenburger Tor', 'type' => 'tarihi', 'price' => 'Ücretsiz'],
            ['name' => 'Reichstag (Parlamento)', 'type' => 'tarihi', 'price' => 'Ücretsiz (rezervasyon)'],
            ['name' => 'Müzeler Adası', 'type' => 'müze', 'price' => '€12-18'],
            ['name' => 'Tiergarten', 'type' => 'park', 'price' => 'Ücretsiz'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Orta',
            'overall_index' => 3,
            'rent' => ['wg_room' => '€500-800', 'studio' => '€900-1400', 'studentenwohnheim' => '€200-400'],
            'food' => ['mensa_lunch' => '€2-5', 'grocery_monthly' => '€200-260', 'restaurant_meal' => '€12-20'],
            'monthly_total_estimate' => '€900-1.200',
        ],

        'job_market' => [
            'overview' => 'Start-up ekosistemi Avrupa\'da 3. büyük. Teknoloji, medya, turizm öne çıkıyor. Kamu sektörü de büyük.',
            'dominant_sectors' => [
                ['name' => 'Teknoloji & Start-up', 'collar' => 'beyaz yaka', 'intensity' => 5, 'description' => 'Zalando, HelloFresh, Delivery Hero Berlin merkezli. 600+ start-up. "Alman Silikon Vadisi" adayı.', 'companies' => ['Zalando', 'HelloFresh', 'Delivery Hero', 'N26', 'Wefox']],
                ['name' => 'Medya & Yaratıcı Endüstri', 'collar' => 'beyaz yaka', 'intensity' => 4, 'description' => 'Bertelsmann, Springer, televizyon kanalları. Tasarım, film, müzik sektörleri güçlü.', 'companies' => ['Axel Springer', 'RBB', 'Universal Music DE']],
                ['name' => 'Turizm & Hizmet', 'collar' => 'mavi yaka', 'intensity' => 4, 'description' => '14 milyon turist/yıl. Otel, restoran, müze işletmeciliği. Öğrenci part-time için ideal.', 'companies' => []],
            ],
            'avg_salary'      => '€42.000/yıl brüt',
            'unemployment'    => '%7.5',
            'student_jobs'    => 'Bol: gastro, turizm, teknoloji Werkstudent. Saatlik €12-15.',
        ],

        'pros_cons' => [
            'pros' => ['Almanya\'nın en ucuz büyük şehri', 'Dev öğrenci topluluğu', 'Canlı kültür-sanat sahnesi', 'Türk topluluğu (Neukölln/Kreuzberg)', 'Start-up fırsatları', 'Tarihi zenginlik'],
            'cons' => ['Konut bulmak giderek zorlaşıyor', 'Bürokratik süreçler yavaş', 'Hava soğuk ve yağmurlu', 'Maaşlar Münih/Frankfurt\'tan düşük'],
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Berlin\'de Öğrenci Olmak', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Almanya\'nın en büyük öğrenci şehrinde yaşam deneyimi.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'TU Berlin Kampüs Turu', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'TU Berlin bölümleri, kütüphane, Mensa ve kampüs hayatı.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'FU & HU Berlin — Hangisini Seçmeli?', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Freie Universität ve Humboldt Universität karşılaştırması.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Berlin\'de Ucuz Yaşamanın Sırları', 'category' => 'yaşam', 'duration' => '8:24', 'description' => 'Bütçe dostu market, mensa, ücretsiz etkinlikler.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Neukölln & Kreuzberg — Türk Mahallesi', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Berlin\'deki Türk topluluğu ve Türkçe hizmetler.'],
        ],
    ],

    'hamburg' => [
        'slug'        => 'hamburg',
        'name'        => 'Hamburg',
        'state'       => 'Hamburg',
        'emoji'       => '⚓',
        'tagline'     => 'Liman şehri, ticaret ve lojistiğin kalbi',
        'hero_color'  => 'linear-gradient(135deg,#dc2626,#d97706)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1552751753-0fc84ae3a766?w=800&q=80',
        'cost_index'  => 3,
        'student_pop' => '90.000+',
        'population'  => '1,85 milyon',

        'overview' => 'Almanya\'nın en büyük liman şehri ve ikinci büyük kenti. Dünyanın 3. büyük Airbus fabrikası burada. Lojistik, dış ticaret ve medya sektörleri güçlü.',

        'location' => [
            'region'      => 'Kuzey Almanya, Elbe nehri ağzı',
            'airport'     => 'HAM — Hamburg Havalimanı',
            'train_hubs'  => ['ICE ile Berlin 1.5 saat', 'ICE ile Frankfurt 3.5 saat', 'IC ile Amsterdam 5 saat'],
            'city_transport' => 'U-Bahn, S-Bahn, Bus, Fähre (feribot) — Deutschlandticket €29/ay',
        ],

        'culture' => [
            'personality'  => 'Hanseatic (tüccar) geleneği, kozmopolit, denizci kültürü',
            'notable_for'  => ['Speicherstadt (UNESCO mirası)', 'Reeperbahn (eğlence bölgesi)', 'Elbphilharmonie (konser salonu)', 'Fischmarkt'],
            'student_life' => 'Güzel ama pahalıca. Altona ve Eimsbüttel popüler öğrenci semtleri.',
            'turkish_community' => 'Güçlü Türk topluluğu, Altona bölgesinde yoğunlaşmış.',
        ],

        'universities' => [
            ['name' => 'Universität Hamburg', 'short' => 'UHH', 'type' => 'Araştırma Üniversitesi', 'students' => 43000, 'strengths' => ['Hukuk', 'Ekonomi', 'Doğa Bilimleri', 'Tıp'], 'qs_ranking' => '~250', 'note' => 'Kuzey Almanya\'nın en büyük üniversitesi.'],
            ['name' => 'HAW Hamburg', 'short' => 'HAW', 'type' => 'Fachhochschule', 'students' => 17000, 'strengths' => ['Mühendislik', 'İşletme', 'Tasarım', 'Sosyal Çalışma'], 'qs_ranking' => null, 'note' => 'Airbus ve liman sektörüyle güçlü endüstri bağlantısı.'],
        ],

        'attractions' => [
            ['name' => 'Speicherstadt & Miniatur Wunderland', 'type' => 'tarihi/müze', 'price' => '€20 (Wunderland)'],
            ['name' => 'Elbphilharmonie', 'type' => 'kültür', 'price' => 'Ücretsiz (teras) / €15+ (konser)'],
            ['name' => 'Alster Gölü', 'type' => 'doğa', 'price' => 'Ücretsiz'],
            ['name' => 'Fischmarkt (Pazar günü sabah)', 'type' => 'pazar', 'price' => 'Ücretsiz'],
            ['name' => 'Reeperbahn', 'type' => 'eğlence', 'price' => 'Değişken'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Orta-Yüksek', 'overall_index' => 3,
            'rent' => ['wg_room' => '€550-850', 'studio' => '€950-1500', 'studentenwohnheim' => '€250-450'],
            'food' => ['mensa_lunch' => '€3-6', 'grocery_monthly' => '€200-260'],
            'monthly_total_estimate' => '€950-1.250',
        ],

        'job_market' => [
            'overview' => 'Avrupa\'nın büyük ticaret merkezlerinden. Lojistik ve dış ticaret sektöründe iş bulmak nispeten kolay.',
            'dominant_sectors' => [
                ['name' => 'Lojistik & Liman İşletmeciliği', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Hamburg Limanı Avrupa\'nın 3. büyüğü. HHLA, Hapag-Lloyd dünyanın en büyük konteyner taşımacısı. Her seviyede istihdam.', 'companies' => ['Hapag-Lloyd', 'HHLA', 'Kühne+Nagel']],
                ['name' => 'Havacılık (Airbus)', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Finkenwerder\'de Airbus\'un en büyük üretim tesisi. A320 ailesi burada monte ediliyor. Havacılık mühendisliği için öncelikli şehir.', 'companies' => ['Airbus', 'Lufthansa Technik', 'SR Technics']],
                ['name' => 'Medya & Reklam', 'collar' => 'beyaz yaka', 'intensity' => 4, 'description' => 'Spiegel, Zeit, Stern dergileri Hamburg merkezli. Reklam ajansları yoğun.', 'companies' => ['Der Spiegel', 'Die Zeit', 'Gruner+Jahr']],
            ],
            'avg_salary' => '€44.000/yıl brüt',
            'unemployment' => '%6.0',
            'student_jobs' => 'Liman, gastro, medya. Saatlik €12-15.',
        ],

        'pros_cons' => [
            'pros' => ['Airbus ve liman sektörü kariyer fırsatları', 'Deniz atmosferi, güzel mimari', 'İyi toplu taşıma', 'Uluslararası ticaret ortamı'],
            'cons' => ['Çok yağmurlu ve bulutlu hava', 'Görece pahalı', 'Münih/Berlin\'e kıyasla daha az eğlenceli', 'Rüzgarlı'],
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Hamburg\'da Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Liman şehrinde üniversite yaşamı ve öğrenci semtleri.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'HAW Hamburg Tanıtımı', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Hamburg Uygulamalı Bilimler Üniversitesi — bölümler ve öğrenci hayatı.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Hamburg Liman Turu & Speicherstadt', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Miniatur Wunderland, liman ve UNESCO mirasındaki depo binalar.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Airbus Hamburg\'da Staj Deneyimi', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Havacılık mühendisliği öğrencisi Airbus staj deneyimini anlatıyor.'],
        ],
    ],

    'frankfurt' => [
        'slug'        => 'frankfurt',
        'name'        => 'Frankfurt',
        'state'       => 'Hessen',
        'emoji'       => '🏦',
        'tagline'     => 'Avrupa\'nın finans merkezi',
        'hero_color'  => 'linear-gradient(135deg,#0891b2,#16a34a)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1577185748577-b842fd77e9c7?w=800&q=80',
        'cost_index'  => 4,
        'student_pop' => '60.000+',
        'population'  => '770.000',

        'overview' => 'Avrupa Merkez Bankası\'nın merkezi. NYSE Euronext ve Deutsche Börse. Skyline\'ıyla "Mainhattan" lakabı. Finans ve ekonomi öğrencileri için Avrupa\'nın en güçlü şehri.',

        'location' => [
            'region'      => 'Orta Almanya, Main nehri kıyısı, Rhein-Main bölgesi',
            'airport'     => 'FRA — Frankfurt Havalimanı (Avrupa\'nın 2. en büyüğü, dünyada 10. en yoğun)',
            'train_hubs'  => ['ICE ile Berlin 3 saat', 'ICE ile Münih 3 saat', 'ICE ile Paris 3.5 saat'],
            'city_transport' => 'U-Bahn, S-Bahn, Tram, Bus — Deutschlandticket €29/ay',
        ],

        'culture' => [
            'personality'  => 'Kozmopolit, iş odaklı, modern, uluslararası',
            'notable_for'  => ['Römer (ortaçağ belediye binası)', 'Museumsufer (nehir kıyısı müzeler)', 'Sachsenhausen (apple wine kültürü)', 'Goethe\'nin doğduğu şehir'],
            'student_life' => 'Nispeten sakin. Sachsenhausen\'da ucuz barlar. Westend öğrenci semti.',
            'events'       => ['Frankfurt Kitap Fuarı (Ekim)' => 'Dünyanın en büyük kitap fuarı', 'IAA Mobility' => 'Otomotiv fuarı (dönüşümlü)', 'Museumsufer Festivali' => 'Yaz kültür festivali'],
        ],

        'universities' => [
            ['name' => 'Goethe-Universität Frankfurt', 'short' => 'GUF', 'type' => 'Araştırma Üniversitesi', 'students' => 45000, 'strengths' => ['Ekonomi & Finans', 'Hukuk', 'Tıp', 'Doğa Bilimleri'], 'qs_ranking' => '~250', 'note' => 'Avrupa finans sektörüyle doğrudan bağlantı. Frankfurt Okulu (eleştirel teori) buradan çıktı.'],
            ['name' => 'Frankfurt School of Finance & Management', 'short' => 'FS', 'type' => 'Özel Üniversite', 'students' => 7000, 'strengths' => ['Finans', 'Banking', 'MBA'], 'qs_ranking' => '~350 (iş okulu)', 'note' => 'Özel — ücretli ama EZB çalışanları buradan mezun. Güçlü network.'],
        ],

        'attractions' => [
            ['name' => 'Römerberg (Tarihi kent merkezi)', 'type' => 'tarihi', 'price' => 'Ücretsiz'],
            ['name' => 'EZB (Avrupa Merkez Bankası) binası', 'type' => 'mimari', 'price' => 'Ücretsiz (dışarıdan)'],
            ['name' => 'Museumsufer (12 müze)', 'type' => 'müze', 'price' => '€8-15'],
            ['name' => 'Palmengarten', 'type' => 'park', 'price' => '€7'],
            ['name' => 'Sachsenhausen (apple wine barları)', 'type' => 'eğlence', 'price' => 'Değişken'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Pahalı', 'overall_index' => 4,
            'rent' => ['wg_room' => '€650-1000', 'studio' => '€1100-1700', 'studentenwohnheim' => '€280-500'],
            'food' => ['mensa_lunch' => '€3-6', 'grocery_monthly' => '€210-270'],
            'monthly_total_estimate' => '€1.050-1.400',
        ],

        'job_market' => [
            'overview' => 'Finans sektöründe Almanya\'nın başkenti. Deutsche Börse ve EZB burada. Brexit sonrası Londra\'dan çok şirket taşındı.',
            'dominant_sectors' => [
                ['name' => 'Bankacılık & Finans', 'collar' => 'beyaz yaka', 'intensity' => 5, 'description' => 'Deutsche Bank, Commerzbank, EZB, Deutsche Börse, DZ Bank. Finans mezunları için Avrupa\'nın 1 numaralı şehri. Brexit sonrası büyüme devam ediyor.', 'companies' => ['Deutsche Bank', 'Commerzbank', 'DZ Bank', 'DekaBank', 'BNP Paribas DE']],
                ['name' => 'Havacılık (Lufthansa)', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Lufthansa merkezi Frankfurt. Frankfurt Havalimanı 80.000 çalışan. Pilot, kabin, teknik, lojistik istihdam.', 'companies' => ['Lufthansa', 'Fraport AG', 'DHL Hub Frankfurt']],
                ['name' => 'Danışmanlık & Hukuk', 'collar' => 'beyaz yaka', 'intensity' => 4, 'description' => 'Big4 (Deloitte, KPMG, EY, PwC) Frankfurt ofisleri büyük. Uluslararası hukuk firmaları.', 'companies' => ['McKinsey DE', 'BCG DE', 'Deloitte DE', 'KPMG DE']],
            ],
            'avg_salary' => '€52.000/yıl brüt',
            'unemployment' => '%5.0',
            'student_jobs' => 'Havalimanı, finans sektörü intern, gastro. Saatlik €13-17.',
        ],

        'pros_cons' => [
            'pros' => ['Avrupa finans merkezi — güçlü kariyer', 'Mükemmel uluslararası bağlantılar', 'Lufthansa hub — ucuz uçuşlar', 'Kozmopolit ortam'],
            'cons' => ['Pahalı', 'Küçük kent merkezi', 'Sanayi şehri havası', 'Öğrenci yaşamı görece sınırlı'],
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Frankfurt\'ta Finans Kariyeri Başlatmak', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Deutsche Bank ve EZB\'de staj/iş fırsatları.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Goethe Üniversitesi Frankfurt Tanıtımı', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Campus Westend ve ekonomi bölümü turu.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Frankfurt\'ta Öğrenci Olmak', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Sachsenhausen, Museumsufer ve öğrenci bütçesiyle Frankfurt.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Frankfurt Havalimanı\'nda Çalışmak', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Öğrenci part-time iş fırsatları Fraport AG.'],
        ],
    ],

    'cologne' => [
        'slug'        => 'cologne',
        'name'        => 'Köln',
        'state'       => 'Nordrhein-Westfalen',
        'emoji'       => '⛪',
        'tagline'     => 'Medya, kültür ve uygun fiyatlı yaşam',
        'hero_color'  => 'linear-gradient(135deg,#7c3aed,#0891b2)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1598892886985-a6e2b28a5a22?w=800&q=80',
        'cost_index'  => 2,
        'student_pop' => '90.000+',
        'population'  => '1,08 milyon',

        'overview' => 'Ren kıyısında, NRW\'nin kültür başkenti. RTL, WDR gibi büyük medya şirketleri. Uygun fiyatlı yaşam ve büyük üniversite topluluğuyla öğrencilerin favorisi.',

        'location' => [
            'region'      => 'Batı Almanya, Ren nehri kıyısı, NRW eyaleti',
            'airport'     => 'CGN — Köln/Bonn Havalimanı',
            'train_hubs'  => ['ICE ile Frankfurt 1 saat', 'ICE ile Düsseldorf 30 dakika', 'Thalys ile Paris 2.5 saat'],
            'city_transport' => 'U-Bahn, S-Bahn, Tram — Deutschlandticket €29/ay',
        ],

        'culture' => [
            'personality'  => 'Açık, eğlenceli, Karnaval ruhu, Ren enerjisi',
            'notable_for'  => ['Köln Katedrali (UNESCO)', 'Karneval (Avrupa\'nın en büyüğü)', 'Medya ve reklamcılık', 'Ren kıyısı yaşamı'],
            'student_life' => 'Çok canlı. Zülpicher Viertel ve Belgisches Viertel öğrenci semtleri. Uygun barlar, kafeler.',
            'turkish_community' => 'Almanya\'nın en büyük Türk nüfuslarından. Ehrenfeld ve Mülheim bölgelerinde yoğun.',
        ],

        'universities' => [
            ['name' => 'Universität zu Köln', 'short' => 'UzK', 'type' => 'Araştırma Üniversitesi', 'students' => 50000, 'strengths' => ['Ekonomi & Sosyal Bilimler', 'Hukuk', 'Tıp', 'İnsan Bilimleri'], 'qs_ranking' => '~300', 'note' => 'Almanya\'nın en büyük üniversitelerinden. Medya ve iletişim güçlü.'],
            ['name' => 'TH Köln', 'short' => 'TH Köln', 'type' => 'Fachhochschule', 'students' => 26000, 'strengths' => ['Mühendislik', 'Tasarım', 'Medya Teknolojisi', 'Sosyal Çalışma'], 'qs_ranking' => null, 'note' => 'Pratik eğitim, tasarım ve medya güçlü.'],
        ],

        'attractions' => [
            ['name' => 'Köln Katedrali (Dom)', 'type' => 'tarihi', 'price' => 'Ücretsiz (içeri) / €6 kule'],
            ['name' => 'Altes Rathaus & Ren Kıyısı', 'type' => 'tarihi', 'price' => 'Ücretsiz'],
            ['name' => 'Museum Ludwig (Modern Sanat)', 'type' => 'müze', 'price' => '€13'],
            ['name' => 'Ren Nehri Gezisi', 'type' => 'doğa', 'price' => '€15-20 (gezi teknesi)'],
            ['name' => 'Karneval (Şubat)', 'type' => 'etkinlik', 'price' => 'Ücretsiz (sokak)'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Uygun', 'overall_index' => 2,
            'rent' => ['wg_room' => '€450-700', 'studio' => '€800-1300', 'studentenwohnheim' => '€220-420'],
            'food' => ['mensa_lunch' => '€2-5', 'grocery_monthly' => '€190-250'],
            'monthly_total_estimate' => '€850-1.150',
        ],

        'job_market' => [
            'overview' => 'Medya sektörü güçlü. NRW sanayi bölgesine (Düsseldorf, Dortmund) yakın.',
            'dominant_sectors' => [
                ['name' => 'Medya & Yayıncılık', 'collar' => 'beyaz yaka', 'intensity' => 5, 'description' => 'RTL (Avrupa\'nın en büyük özel TV), WDR (kamu yayıncı), Gruner+Jahr. Almanya\'nın medya başkenti.', 'companies' => ['RTL Deutschland', 'WDR', 'Telekom (eski)']],
                ['name' => 'Sigorta & Finans', 'collar' => 'beyaz yaka', 'intensity' => 3, 'description' => 'AXA Almanya, Generali DE. Frankfurt\'a yakın olması finansçılar için avantaj.', 'companies' => ['AXA DE', 'Generali DE']],
                ['name' => 'Ticaret & Perakende', 'collar' => 'her ikisi', 'intensity' => 4, 'description' => 'REWE, dm (Drogerie Markt) Köln merkezli. Büyük perakende istihdamı.', 'companies' => ['REWE Group', 'dm-drogerie markt']],
            ],
            'avg_salary' => '€40.000/yıl brüt',
            'unemployment' => '%7.0',
            'student_jobs' => 'Medya, perakende, gastro. Saatlik €12-14.',
        ],

        'pros_cons' => [
            'pros' => ['En uygun fiyatlı büyük şehir', 'Büyük öğrenci topluluğu', 'Medya kariyer fırsatları', 'Canlı kültür hayatı', 'Frankfurt\'a 1 saat'],
            'cons' => ['Münih/Frankfurt kadar prestijli üniversite yok', 'Hava gri ve yağmurlu', 'Karneval dışında turizm çok yoğun değil'],
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Köln\'de Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Zülpicher Viertel, Mensa, uygun fiyatlı Köln yaşamı.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Universität zu Köln Tanıtımı', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Almanya\'nın en büyük üniversitelerinden birinin kampüs turu.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Köln Katedrali ve Ren Kıyısı Turu', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'UNESCO mirası Dom\'dan Severin köprüsüne şehir yürüyüşü.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'RTL & WDR: Medya Kariyeri Köln\'de', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Medya ve yayıncılık sektöründe staj deneyimi.'],
        ],
    ],

    'stuttgart' => [
        'slug'        => 'stuttgart',
        'name'        => 'Stuttgart',
        'state'       => 'Baden-Württemberg',
        'emoji'       => '🚗',
        'tagline'     => 'Otomotiv ve mühendisliğin kalbi',
        'hero_color'  => 'linear-gradient(135deg,#dc2626,#7c3aed)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1583079889956-c0c4dd6f61c1?w=800&q=80',
        'cost_index'  => 4,
        'student_pop' => '70.000+',
        'population'  => '635.000',

        'overview' => 'Mercedes-Benz ve Porsche\'nin beşiği. Almanya\'nın en düşük işsizlik oranına sahip şehri. Mühendislik ve otomotiv tutkusu için birinci adres.',

        'location' => [
            'region'      => 'Güney-batı Almanya, Baden-Württemberg, Neckar vadisi',
            'airport'     => 'STR — Stuttgart Havalimanı',
            'train_hubs'  => ['ICE ile Frankfurt 1 saat', 'ICE ile Münih 2 saat', 'ICE ile Zürich 3 saat'],
            'city_transport' => 'U-Bahn (Stadtbahn), S-Bahn, Bus — Deutschlandticket €29/ay',
        ],

        'culture' => [
            'personality'  => 'Çalışkan, muhafazakâr Swabian kültürü, kalite odaklı',
            'notable_for'  => ['Mercedes-Benz ve Porsche müzeleri', 'Swabian mutfağı (Maultaschen, Spätzle)', 'Şarap vadileri', 'Stuttgart Ballet (Avrupa\'nın en iyilerinden)'],
            'student_life' => 'Daha sakin, daha çalışkan. Karlsplatz çevresi öğrenci hangout. Pahalı ama maaşlar yüksek.',
            'turkish_community' => 'Güçlü Türk topluluğu. Bad Cannstatt bölgesinde yoğun. Türkçe esnaf yaygın.',
        ],

        'universities' => [
            ['name' => 'Universität Stuttgart', 'short' => 'UniS', 'type' => 'Teknik Araştırma Üniversitesi', 'students' => 26000, 'strengths' => ['Makine Mühendisliği', 'Havacılık', 'İnşaat', 'Bilgisayar Mühendisliği', 'Mimarlık'], 'qs_ranking' => '~350', 'note' => 'Mercedes, Bosch, Porsche ile güçlü endüstri bağlantısı. Araştırma fonu yüksek.'],
            ['name' => 'Hochschule für Technik Stuttgart (HFT)', 'short' => 'HFT', 'type' => 'Fachhochschule', 'students' => 5500, 'strengths' => ['Bilgisayar Bilimi', 'Geodezi', 'Mimarlık', 'İnşaat'], 'qs_ranking' => null, 'note' => 'Daha küçük, daha uygulamalı. Staj olanakları iyi.'],
            ['name' => 'DHBW Stuttgart', 'short' => 'DHBW', 'type' => 'Duale Hochschule (Çift Uygulamalı)', 'students' => 9000, 'strengths' => ['İşletme', 'Mühendislik', 'Sosyal Çalışma'], 'qs_ranking' => null, 'note' => 'Almanya\'ya özgü "dual" sistem — okul ve şirket eş zamanlı. Maaşlı okumak mümkün!'],
        ],

        'attractions' => [
            ['name' => 'Mercedes-Benz Müzesi', 'type' => 'müze', 'price' => '€12'],
            ['name' => 'Porsche Müzesi', 'type' => 'müze', 'price' => '€10'],
            ['name' => 'Schlossplatz (Saray Meydanı)', 'type' => 'tarihi', 'price' => 'Ücretsiz'],
            ['name' => 'Württemberg Şarap Vadisi', 'type' => 'doğa/kültür', 'price' => 'Ücretsiz (yürüyüş)'],
            ['name' => 'Cannstatter Volksfest (Eylül-Ekim)', 'type' => 'etkinlik', 'price' => 'Ücretsiz (giriş) / €7+ (bira)'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Pahalı', 'overall_index' => 4,
            'rent' => ['wg_room' => '€600-950', 'studio' => '€1000-1600', 'studentenwohnheim' => '€260-460'],
            'food' => ['mensa_lunch' => '€3-6', 'grocery_monthly' => '€200-260'],
            'monthly_total_estimate' => '€1.000-1.350',
        ],

        'job_market' => [
            'overview' => 'Almanya\'nın en düşük işsizlik oranı (%3). Otomotiv ve mühendislik mezunları için cennet.',
            'dominant_sectors' => [
                ['name' => 'Otomotiv (Mavi & Beyaz Yaka)', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Mercedes-Benz (Untertürkheim fabrikası, 75.000 çalışan), Porsche (Zuffenhausen, 36.000 çalışan), Bosch (Gerlingen, 80.000 çalışan). Her seviyede istihdam — fabrikadan Ar-Ge\'ye.', 'companies' => ['Mercedes-Benz AG', 'Porsche AG', 'Robert Bosch GmbH', 'Mahle', 'ZF Friedrichshafen']],
                ['name' => 'Makine & Endüstri Teknolojisi', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Trumpf (lazer teknolojisi), Festo, KUKA robotik (Augsburg yakın). "Hidden Champion" şirketleri yoğun.', 'companies' => ['Trumpf', 'Festo', 'Sick AG', 'Lapp Group']],
                ['name' => 'Yazılım & Otomasyon', 'collar' => 'beyaz yaka', 'intensity' => 4, 'description' => 'SAP\'ın güçlü partner ekosistemi. Embedded sistemler, otomasyon yazılımı. Endüstri 4.0 yatırımları yüksek.', 'companies' => ['SAP partnerler', 'Daimler Truck Tech', 'Bosch Digital']],
            ],
            'avg_salary' => '€48.000/yıl brüt',
            'unemployment' => '%3.0',
            'student_jobs' => 'Fabrika Werkstudent (Mercedes/Porsche), teknoloji. Saatlik €13-18.',
        ],

        'pros_cons' => [
            'pros' => ['Almanya\'nın en düşük işsizliği', 'Mercedes & Porsche staj/iş fırsatları', 'Güçlü mühendislik eğitimi', 'Yakın şarap bölgeleri', 'Frankfurt/Münih\'e kolay erişim'],
            'cons' => ['Münih kadar pahalı', 'Swabian kültürü biraz kapalı olabilir', 'Küçük şehir hissi', 'Kültür hayatı Münih/Berlin kadar zengin değil'],
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Stuttgart\'ta Mühendislik Okumak', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Uni Stuttgart\'tan mezunların Mercedes ve Bosch\'taki kariyer yolculukları.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Uni Stuttgart Kampüs Turu', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Stadtmitte ve Vaihingen kampüslerinin detaylı tanıtımı.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Mercedes-Benz Müzesi & Staj Deneyimi', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Otomotiv tutkunu öğrenciler için Mercedes-Benz staj süreci.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Stuttgart Şarap Vadileri & Şehir Yaşamı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Neckar vadisi, bağlar, Schlossplatz ve öğrenci hangoutları.'],
        ],
    ],

    // ─── YENİ ŞEHİRLER ───────────────────────────────────────────────────────

    'dusseldorf' => [
        'slug'        => 'dusseldorf',
        'name'        => 'Düsseldorf',
        'state'       => 'Nordrhein-Westfalen',
        'emoji'       => '🎭',
        'tagline'     => 'Moda, tasarım ve uluslararası iş dünyasının kalbi',
        'hero_color'  => 'linear-gradient(135deg,#0891b2,#2563eb)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1624085568108-36410d0b87bd?w=800&q=80',
        'cost_index'  => 4,
        'student_pop' => '55.000+',
        'population'  => '640.000',

        'overview' => 'Japonya\'nın Avrupa başkenti ve moda dünyasının merkezi. Deichmann, Metro, Henkel gibi dev şirketlerin genel merkezi. Köln\'e sadece 30 dakika uzaklıkta. Uluslararası iş ortamı ve tasarım odaklı eğitim için ideal.',

        'location' => [
            'region'      => 'Batı Almanya, Ren nehri kıyısı, NRW eyaleti',
            'airport'     => 'DUS — Düsseldorf Uluslararası Havalimanı',
            'train_hubs'  => ['ICE ile Frankfurt 1 saat', 'ICE ile Köln 30 dakika', 'ICE ile Amsterdam 2.5 saat'],
            'city_transport' => 'U-Bahn, S-Bahn, Tram — Deutschlandticket €29/ay',
            'geography'   => 'Ren nehri kıyısında, Köln\'ün kuzeyi. Ruhr bölgesine yakın, sanayi ve hizmet sektörü iç içe.',
        ],

        'culture' => [
            'personality'  => 'Şık, kozmopolit, iş odaklı; Karneval\'de eğlenceli',
            'notable_for'  => ['Japantown (Immermannstraße)', 'Moda haftası & tasarım', 'Königsallee (lüks alışveriş)', 'Ren kulesi ve panorama', 'Altstadt (Uzun bar)'],
            'student_life' => 'Bilk ve Flingern semtleri öğrenci yoğun. Görece pahalı ama kozmopolit atmosfer. Japonca yemek kültürü baskın.',
            'turkish_community' => 'Almanya\'nın güçlü Türk topluluklarından. Flingern ve Garath bölgelerinde yoğun.',
            'events' => [
                'Düsseldorf Karneval (Şubat)' => 'Köln\'den sonra en büyük Karneval kutlaması',
                'DRUPA (Haziran)' => 'Dünyanın en büyük baskı fuarı (4 yılda bir)',
                'EuroCIS' => 'Avrupa perakende teknolojisi fuarı',
            ],
        ],

        'universities' => [
            ['name' => 'Heinrich-Heine-Universität Düsseldorf (HHU)', 'short' => 'HHU', 'type' => 'Araştırma Üniversitesi', 'students' => 35000, 'strengths' => ['Tıp', 'Hukuk', 'Ekonomi', 'Kimya', 'Bilgi İşlem'], 'qs_ranking' => '~450', 'note' => 'Tıp ve eczacılık programları güçlü. Büyük Üniversite Hastanesi (UKD) bağlı.'],
            ['name' => 'Hochschule Düsseldorf (HSD)', 'short' => 'HSD', 'type' => 'Fachhochschule', 'students' => 11000, 'strengths' => ['Tasarım', 'Sosyal Çalışma', 'Medya', 'Mühendislik'], 'qs_ranking' => null, 'note' => 'Tasarım ve medya fakültesi Almanya\'nın en iyilerinden. Moda sektörüyle doğrudan bağ.'],
            ['name' => 'Düsseldorf Business School (DBS)', 'short' => 'DBS', 'type' => 'Özel MBA', 'students' => 500, 'strengths' => ['MBA', 'Executive Education'], 'qs_ranking' => null, 'note' => 'Özel MBA programı, uluslararası iş dünyasına odaklı.'],
        ],

        'attractions' => [
            ['name' => 'Königsallee (KÖ)', 'type' => 'alışveriş', 'price' => 'Ücretsiz (gezmek)', 'note' => 'Avrupa\'nın en şık alışveriş caddesi.'],
            ['name' => 'Rheinturm (Ren Kulesi)', 'type' => 'mimari', 'price' => '€9', 'note' => '170m yükseklikte panorama.'],
            ['name' => 'Altstadt (Uzun Bar)', 'type' => 'eğlence', 'price' => 'Değişken', 'note' => 'Dünyanın en uzun bar caddesi olarak bilinir (300 bar).'],
            ['name' => 'K20 & K21 Müzeleri', 'type' => 'müze', 'price' => '€12', 'note' => 'Modern ve çağdaş sanat koleksiyonları.'],
            ['name' => 'Japantown (Immermannstraße)', 'type' => 'kültür', 'price' => 'Ücretsiz', 'note' => 'Avrupa\'nın en büyük Japon mahallesi.'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Pahalı', 'overall_index' => 4,
            'rent' => ['wg_room' => '€550-850', 'studio' => '€950-1500', 'studentenwohnheim' => '€250-450'],
            'food' => ['mensa_lunch' => '€3-6', 'grocery_monthly' => '€200-260'],
            'monthly_total_estimate' => '€950-1.300',
        ],

        'job_market' => [
            'overview' => 'NRW\'nin iş merkezi. Uluslararası şirket yoğunluğu çok yüksek. Japonya bağlantısı benzersiz.',
            'dominant_sectors' => [
                ['name' => 'Moda & Tasarım', 'collar' => 'beyaz yaka', 'intensity' => 5, 'description' => 'Almanya\'nın moda merkezi. Hugo Boss, Peek & Cloppenburg, Esprit. Uluslararası moda haftaları.', 'companies' => ['Peek & Cloppenburg', 'Esprit', 'Hugo Boss DE']],
                ['name' => 'Japonya İş Dünyası', 'collar' => 'beyaz yaka', 'intensity' => 5, 'description' => '400+ Japon şirketi Düsseldorf\'ta. Japonca bilenler için eşsiz fırsat. Toyota, NEC, Fujitsu ofisleri.', 'companies' => ['Toyota DE', 'NEC Europe', 'Fujitsu DE', 'Kirin DE']],
                ['name' => 'Perakende & Lojistik', 'collar' => 'her ikisi', 'intensity' => 4, 'description' => 'Metro AG ve Deichmann merkezi. Büyük perakende istihdamı.', 'companies' => ['Metro AG', 'Deichmann', 'Henkel AG']],
            ],
            'avg_salary' => '€44.000/yıl brüt',
            'unemployment' => '%8.5',
            'student_jobs' => 'Gastro, perakende, tasarım stajı. Saatlik €12-15.',
        ],

        'pros_cons' => [
            'pros' => ['Uluslararası iş ortamı', 'Moda ve tasarım kariyeri', 'Köln\'e 30 dk yakın', 'Japonya bağlantısı eşsiz', 'Canlı kültür hayatı'],
            'cons' => ['Görece pahalı', 'Üniversite prestiji Münih/Berlin kadar yüksek değil', 'Hava gri ve yağmurlu'],
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Düsseldorf\'ta Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Japantown, Königsallee ve öğrenci semti Bilk.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'HHU Düsseldorf Kampüs Turu', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Heinrich-Heine Üniversitesi kampüsü ve tıp fakültesi.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Tasarım ve Moda Kariyeri Düsseldorf', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'HSD mezunlarının moda sektöründeki kariyer hikayeleri.'],
        ],
    ],

    'dresden' => [
        'slug'        => 'dresden',
        'name'        => 'Dresden',
        'state'       => 'Sachsen',
        'emoji'       => '🏰',
        'tagline'     => 'Güçlü mühendislik eğitimi, uygun yaşam maliyeti',
        'hero_color'  => 'linear-gradient(135deg,#7c3aed,#16a34a)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1609856878074-cf31e21ccb6d?w=800&q=80',
        'cost_index'  => 2,
        'student_pop' => '45.000+',
        'population'  => '560.000',

        'overview' => 'Elbe nehri kıyısında, "Kuzey\'in Floransası" olarak anılan kültür kenti. TU Dresden güçlü mühendislik programlarıyla dünyaca tanınmış. Almanya\'nın en uygun fiyatlı büyük şehirlerinden: kira €400-700.',

        'location' => [
            'region'      => 'Doğu Almanya, Sachsen eyaleti, Çek sınırına yakın',
            'airport'     => 'DRS — Dresden Havalimanı',
            'train_hubs'  => ['ICE ile Berlin 2 saat', 'IC ile Prag 2.5 saat', 'ICE ile Frankfurt 4 saat'],
            'city_transport' => 'Tram, S-Bahn, Bus — Deutschlandticket €29/ay. Bisiklet yolları mükemmel.',
            'geography'   => 'Elbe vadisinde, Saksonya İsviçresi milli parkına yakın. Doğa ile iç içe.',
        ],

        'culture' => [
            'personality'  => 'Kültür odaklı, sessiz, doğa severler, akademik',
            'notable_for'  => ['Zwinger Sarayı', 'Frauenkirche', 'Semperoper', 'Elbe nehri bisiklet yolu', 'II. Dünya Savaşı tarihi'],
            'student_life' => 'Sakin ama kaliteli. Neustadt semti genç ve canlı. Ucuz kafeler ve alternatif kültür. Çek Cumhuriyeti\'ne yakınlık avantaj.',
            'turkish_community' => 'Görece küçük ama büyüyen topluluk. Neustadt\'ta Türk restoranları ve market.',
            'events' => [
                'Striezelmarkt Noel Pazarı' => 'Almanya\'nın en eski ve en ünlü Noel pazarı',
                'Filmnächte am Elbufer' => 'Elbe kıyısında açık hava sinema festivali',
            ],
        ],

        'universities' => [
            ['name' => 'Technische Universität Dresden (TU Dresden)', 'short' => 'TUD', 'type' => 'Teknik Üniversite (Exzellenzuniversität)', 'students' => 32000, 'strengths' => ['Elektrik Mühendisliği', 'Makine Mühendisliği', 'Biyomedikal Mühendisliği', 'Mimarlık', 'Bilgisayar Bilimi'], 'qs_ranking' => '~350', 'note' => 'Almanya\'nın "Exzellenzuniversität" seçilen 11 üniversitesinden biri. Araştırma fonu çok yüksek. Volkswagen, Infineon ile güçlü endüstri bağı.'],
            ['name' => 'HTW Dresden', 'short' => 'HTW', 'type' => 'Fachhochschule', 'students' => 5000, 'strengths' => ['Tasarım', 'İşletme', 'Çevre Mühendisliği'], 'qs_ranking' => null, 'note' => 'Pratik odaklı, küçük sınıflar, kişisel ilgi.'],
        ],

        'attractions' => [
            ['name' => 'Frauenkirche', 'type' => 'tarihi', 'price' => 'Ücretsiz', 'note' => 'II. Dünya Savaşı\'ndan sonra yeniden inşa edilen sembolik kilise.'],
            ['name' => 'Zwinger Sarayı', 'type' => 'müze/tarihi', 'price' => '€14', 'note' => 'Barok mimari başyapıtı, içinde porselenler ve sanat koleksiyonu.'],
            ['name' => 'Semperoper', 'type' => 'kültür', 'price' => '€15-80', 'note' => 'Almanya\'nın en prestijli opera binalarından.'],
            ['name' => 'Elbe Bisiklet Yolu', 'type' => 'doğa', 'price' => 'Ücretsiz', 'note' => 'Nehir boyunca saatlik bisiklet turu.'],
            ['name' => 'Saksonya İsviçresi Milli Parkı', 'type' => 'doğa', 'price' => 'Ücretsiz', 'note' => 'Dramatik kaya oluşumları, yürüyüş ve kaya tırmanışı.'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Uygun', 'overall_index' => 2,
            'rent' => ['wg_room' => '€350-600', 'studio' => '€650-1000', 'studentenwohnheim' => '€180-350'],
            'food' => ['mensa_lunch' => '€2-4', 'grocery_monthly' => '€170-230'],
            'monthly_total_estimate' => '€750-1.000',
        ],

        'job_market' => [
            'overview' => 'Doğu Almanya\'nın en güçlü iş piyasası. Mikro-çip ve yarı iletken sektörü patlama yaşıyor.',
            'dominant_sectors' => [
                ['name' => 'Yarı İletken & Mikro-Elektronik', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => '"Silicon Saxony" — Avrupa\'nın en büyük mikro-çip üretim kümesi. Infineon, GlobalFoundries, Bosch Semiconductors. AB çip fabrikası yatırımları büyüyor.', 'companies' => ['Infineon Technologies', 'GlobalFoundries', 'Bosch Semiconductor', 'X-FAB']],
                ['name' => 'Biyoteknoloji & Tıp', 'collar' => 'beyaz yaka', 'intensity' => 4, 'description' => 'TU Dresden Tıp Fakültesi ile bağlantılı biyoteknoloji start-upları. Bioinova Dresden ekosistemi.', 'companies' => ['Bioinova Dresden', 'TU Dresden klinikleri']],
            ],
            'avg_salary' => '€38.000/yıl brüt',
            'unemployment' => '%5.5',
            'student_jobs' => 'Yarı iletken fabrika Werkstudent, kafe, kültür. Saatlik €12-14.',
        ],

        'pros_cons' => [
            'pros' => ['Almanya\'nın en uygun fiyatlı büyük şehri', 'Exzellenzuniversität kalitesi', 'Güzel tarihi mimari', 'Prag\'a 2.5 saat', 'Doğa ve kültür iç içe', '"Silicon Saxony" kariyer fırsatları'],
            'cons' => ['Doğu Almanya — bazı bölgelerde sağ eğilimli siyasi atmosfer', 'Uluslararası uçuş seçenekleri sınırlı', 'İş dünyası Batı Almanya kadar çeşitli değil'],
        ],

        'student_tips' => [
            'Prag\'ı Keşfet' => 'IC trenle 2.5 saatte Prag\'a ulaşabilirsin. Çok ucuz hafta sonu kaçamağı.',
            'Neustadt Semtini Sev' => 'Alternatif kafe, bar ve kültürel mekanların yoğun olduğu semttir.',
            'Elbe\'ye Çık' => 'Her mevsim Elbe kıyısında piknik veya bisiklet yapılabilir — ve ücretsiz.',
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Dresden\'de Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Neustadt, Elbe kıyısı ve öğrenci bütçesiyle Dresden.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'TU Dresden — Exzellenzuniversität', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'TU Dresden kampüsü ve mühendislik programları.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Silicon Saxony: Çip Endüstrisinde Kariyer', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Infineon ve GlobalFoundries Dresden staj deneyimi.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Frauenkirche ve Zwinger — Dresden Turu', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Tarihi merkezi keşfet.'],
        ],
    ],

    'hannover' => [
        'slug'        => 'hannover',
        'name'        => 'Hannover',
        'state'       => 'Niedersachsen',
        'emoji'       => '🌿',
        'tagline'     => 'Teknoloji, fuarlar ve sakin öğrenci yaşamı',
        'hero_color'  => 'linear-gradient(135deg,#16a34a,#0891b2)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1584267385494-9fdd9a71ad75?w=800&q=80',
        'cost_index'  => 2,
        'student_pop' => '50.000+',
        'population'  => '535.000',

        'overview' => 'Hannover Fuarı dünyanın en büyük endüstriyel fuarı. Volkswagen, Continental gibi dev şirketlerin ar-ge merkezi. Sakin, yeşil bir öğrenci şehri — kira uygun (~€450-750), üniversite kalitesi yüksek.',

        'location' => [
            'region'      => 'Orta Kuzey Almanya, Niedersachsen eyaleti',
            'airport'     => 'HAJ — Hannover Havalimanı',
            'train_hubs'  => ['ICE ile Hamburg 1 saat', 'ICE ile Berlin 1.5 saat', 'ICE ile Frankfurt 2 saat'],
            'city_transport' => 'U-Bahn (Stadtbahn), S-Bahn, Bus — Deutschlandticket €29/ay',
            'geography'   => 'Leine nehri kıyısında, Teutoburg Ormanı\'na yakın. Yeşil alan ve park oranı Almanya\'nın en yükseklerinden.',
        ],

        'culture' => [
            'personality'  => 'Sakin, çalışkan, yeşil yaşam odaklı, iş insanları',
            'notable_for'  => ['Hannover Fuarı (Messe)', 'Herrenhäuser Gärten (Barok bahçeler)', 'Maschsee Gölü', 'Niedersachsen Eyaleti Başkenti'],
            'student_life' => 'Sakin ama kaliteli. Linden semti genç ve canlı. Maschsee gölünde piknik vazgeçilmez. Berlin\'in gürültüsünden uzak.',
            'turkish_community' => 'Güçlü Türk topluluğu. Vahrenwald ve Mühlenberg bölgelerinde yoğun.',
            'events' => [
                'Hannover Messe (Nisan)' => 'Dünyanın en büyük endüstri fuarı',
                'CEBIT (tarihi)' => 'Eski dünya BT fuarı merkezi',
                'Maschseefest (Temmuz-Ağustos)' => 'Göl kenarında yaz festivali',
            ],
        ],

        'universities' => [
            ['name' => 'Leibniz Universität Hannover (LUH)', 'short' => 'LUH', 'type' => 'Araştırma Üniversitesi', 'students' => 26000, 'strengths' => ['Makine Mühendisliği', 'Elektrik Mühendisliği', 'Fen Bilimleri', 'Eğitim Bilimi', 'Mimarlık'], 'qs_ranking' => '~500', 'note' => 'Gottfried Wilhelm Leibniz adını taşıyan tarihî üniversite. Volkswagen ve Continental Ar-Ge ile bağlantı güçlü.'],
            ['name' => 'Hochschule Hannover (HsH)', 'short' => 'HsH', 'type' => 'Fachhochschule', 'students' => 10000, 'strengths' => ['Mühendislik', 'Medya', 'İşletme', 'Sosyal Hizmetler'], 'qs_ranking' => null, 'note' => 'Staj odaklı, pratik eğitim. Medya tasarımı güçlü.'],
            ['name' => 'Tierärztliche Hochschule Hannover (TiHo)', 'short' => 'TiHo', 'type' => 'Veterinerlik Üniversitesi', 'students' => 3000, 'strengths' => ['Veteriner Hekimliği'], 'qs_ranking' => null, 'note' => 'Almanya\'nın en iyi veterinerlik üniversitelerinden. Çok az öğrenci alır.'],
        ],

        'attractions' => [
            ['name' => 'Herrenhäuser Gärten', 'type' => 'park/tarihi', 'price' => '€8', 'note' => 'Avrupa\'nın en güzel Barok bahçelerinden. Büyük Bahçe ve Bergbahçe.'],
            ['name' => 'Maschsee Gölü', 'type' => 'doğa', 'price' => 'Ücretsiz', 'note' => 'Yapay göl, yürüyüş, bisiklet, kürek teknesi. Öğrenci buluşma noktası.'],
            ['name' => 'Hannover Messe Alanı', 'type' => 'endüstriyel', 'price' => 'Fuar döneminde bilet', 'note' => 'Dünyanın en büyük fuar merkezi. Yılda birkaç kez büyük etkinlik.'],
            ['name' => 'Neues Rathaus', 'type' => 'tarihi', 'price' => '€3 (kule asansörü)', 'note' => 'Şehrin sembolik binası, panorama manzarası.'],
            ['name' => 'Linden Semti', 'type' => 'kültür', 'price' => 'Ücretsiz', 'note' => 'Alternatif kafeler, küçük tiyatrolar, öğrenci barları.'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Uygun', 'overall_index' => 2,
            'rent' => ['wg_room' => '€400-650', 'studio' => '€750-1100', 'studentenwohnheim' => '€190-380'],
            'food' => ['mensa_lunch' => '€2-5', 'grocery_monthly' => '€180-240'],
            'monthly_total_estimate' => '€780-1.050',
        ],

        'job_market' => [
            'overview' => 'Endüstri ve otomotiv sektörü güçlü. Volkswagen Hannover fabrikası dev istihdam merkezi.',
            'dominant_sectors' => [
                ['name' => 'Otomotiv', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Volkswagen Nutzfahrzeuge (ticari araç) Hannover fabrikası. Continental AG genel merkezi. Ar-Ge ve üretim istihdam.', 'companies' => ['Volkswagen Nutzfahrzeuge', 'Continental AG', 'TÜV Nord']],
                ['name' => 'Fuar & Etkinlik', 'collar' => 'her ikisi', 'intensity' => 4, 'description' => 'Deutsche Messe AG (Hannover Messe organizatörü). Fuar dönemlerinde yoğun part-time iş fırsatları.', 'companies' => ['Deutsche Messe AG']],
                ['name' => 'Sigorta & Finans', 'collar' => 'beyaz yaka', 'intensity' => 3, 'description' => 'Talanx (HDI) ve Hannover Re sigorta şirketleri. Finans sektörü gelişiyor.', 'companies' => ['Talanx AG', 'Hannover Re', 'HDI Global']],
            ],
            'avg_salary' => '€40.000/yıl brüt',
            'unemployment' => '%6.5',
            'student_jobs' => 'Fuar dönemleri, gastro, perakende. Saatlik €12-14.',
        ],

        'pros_cons' => [
            'pros' => ['Uygun fiyatlı yaşam', 'Hızlı ulaşım (Berlin/Hamburg 1 saat)', 'Volkswagen & Continental kariyer', 'Sakin, güvenli şehir', 'Yeşil, yaşanabilir'],
            'cons' => ['Öğrenci hayatı Berlin/Köln kadar canlı değil', 'Uluslararası öğrenci topluluğu görece küçük', 'Hava yağmurlu'],
        ],

        'student_tips' => [
            'Fuar Döneminde Part-time' => 'Hannover Messe döneminde (Nisan) binlerce geçici iş çıkar. Erken başvur.',
            'Maschsee\'yi Kullan' => 'Göl kenarı piknik ve yürüyüş ücretsiz ve harika bir stres atma yolu.',
            'Linden\'da Kal' => 'En uygun ve en canlı semttir, öğrenci nüfusu yoğun.',
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Hannover\'da Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Linden, Maschsee ve öğrenci bütçesiyle Hannover.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Leibniz Üniversitesi Kampüs Turu', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'LUH kampüsünün ve mühendislik bölümlerinin tanıtımı.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Volkswagen & Continental Staj Deneyimi', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Hannover\'daki büyük otomotiv şirketlerinde staj.'],
        ],
    ],

    'nurnberg' => [
        'slug'        => 'nurnberg',
        'name'        => 'Nürnberg',
        'state'       => 'Bayern',
        'emoji'       => '🏛',
        'tagline'     => 'Tarihin ve Bavyera mühendisliğinin güçlü şehri',
        'hero_color'  => 'linear-gradient(135deg,#dc2626,#d97706)',
        'hero_video_id' => 'LzLOhMsjpsw',

        'hero_video_thumb' => 'https://images.unsplash.com/photo-1599566175617-5c0e95a15f4c?w=800&q=80',
        'cost_index'  => 3,
        'student_pop' => '45.000+',
        'population'  => '520.000',

        'overview' => 'Bavyera\'nın ikinci büyük şehri. FAU Erlangen-Nürnberg, Almanya\'nın köklü üniversitelerinden. Münih\'e alternatif, daha uygun yaşam maliyeti. Orta Çağ mimarisi, dünyaca ünlü Noel pazarı ve güçlü sanayi altyapısı.',

        'location' => [
            'region'      => 'Güney Almanya, Bayern eyaleti, Franken bölgesi',
            'airport'     => 'NUE — Nürnberg Albrecht Dürer Havalimanı',
            'train_hubs'  => ['ICE ile Münih 1 saat', 'ICE ile Frankfurt 2 saat', 'ICE ile Berlin 3.5 saat'],
            'city_transport' => 'U-Bahn, Tram, Bus — Deutschlandticket €29/ay',
            'geography'   => 'Pegnitz nehri kıyısında. Tarihi sur içi ve modern şehir iç içe. Orta Almanya\'nın ortasında konumlu.',
        ],

        'culture' => [
            'personality'  => 'Tarihsel, Frankonyalı, muhafazakâr ama sıcak, sanata düşkün',
            'notable_for'  => ['Kaiserburg (İmparator Kalesi)', 'Nürnberg Noel Pazarı (Christkindlesmarkt)', 'Nürnberg Mahkemesi tarihi', 'Albrecht Dürer (Rönesans sanatçısı)', 'Lego ve oyuncak üretimi'],
            'student_life' => 'Orta ölçekli, sakin ama kaliteli. Wöhrder Wiese nehir parkı öğrenci buluşma yeri. Münih\'e kolayca gidilir.',
            'turkish_community' => 'Almanya\'nın büyük Türk topluluklarından. Gostenhof (GoHo) semtinde yoğun.',
            'events' => [
                'Christkindlesmarkt (Aralık)' => 'Dünyanın en ünlü Noel pazarı (2 milyon ziyaretçi)',
                'Blaue Nacht (Mayıs)' => 'Kültür ve sanat gecesi',
                'Rock im Park (Haziran)' => 'Büyük müzik festivali',
            ],
        ],

        'universities' => [
            ['name' => 'Friedrich-Alexander-Universität Erlangen-Nürnberg (FAU)', 'short' => 'FAU', 'type' => 'Araştırma Üniversitesi', 'students' => 39000, 'strengths' => ['Tıp', 'Mühendislik', 'Fen Bilimleri', 'İşletme', 'Hukuk'], 'qs_ranking' => '~350', 'note' => 'Bavyera\'nın en eski üniversitelerinden (1743). Siemens ve MAN ile güçlü endüstri ilişkisi. İki kampüs: Erlangen + Nürnberg.'],
            ['name' => 'TH Nürnberg Georg Simon Ohm', 'short' => 'TH Nürnberg', 'type' => 'Fachhochschule', 'students' => 13000, 'strengths' => ['Mühendislik', 'İşletme', 'Bilgisayar Bilimi', 'Tasarım'], 'qs_ranking' => null, 'note' => 'Pratik odaklı, güçlü endüstri bağlantısı. Siemens ve Adidas Werkstudent imkanı.'],
        ],

        'attractions' => [
            ['name' => 'Kaiserburg (Nürnberg Kalesi)', 'type' => 'tarihi', 'price' => '€7', 'note' => 'Kutsal Roma İmparatorluğu\'nun Orta Çağ kalesi, şehre hâkim panorama.'],
            ['name' => 'Christkindlesmarkt (Aralık)', 'type' => 'etkinlik', 'price' => 'Ücretsiz (giriş)', 'note' => 'Dünyanın en ünlü Noel pazarı. Glühwein ve Lebkuchen.'],
            ['name' => 'Germanisches Nationalmuseum', 'type' => 'müze', 'price' => '€8', 'note' => 'Almanya\'nın en büyük kültür tarihi müzesi.'],
            ['name' => 'Memorium Nürnberger Prozesse', 'type' => 'tarihi', 'price' => '€8', 'note' => 'II. Dünya Savaşı Nürnberg Mahkemelerinin orijinal salonu.'],
            ['name' => 'Wöhrder Wiese', 'type' => 'doğa', 'price' => 'Ücretsiz', 'note' => 'Pegnitz nehri kıyısında yeşil rekreasyon alanı.'],
        ],

        'cost_of_living' => [
            'overall_label' => 'Orta', 'overall_index' => 3,
            'rent' => ['wg_room' => '€500-750', 'studio' => '€850-1300', 'studentenwohnheim' => '€230-420'],
            'food' => ['mensa_lunch' => '€2.5-5', 'grocery_monthly' => '€190-250'],
            'monthly_total_estimate' => '€870-1.150',
        ],

        'job_market' => [
            'overview' => 'Siemens\'in kalbi, Adidas merkezi. Enerji, sağlık teknolojisi ve otomotiv güçlü.',
            'dominant_sectors' => [
                ['name' => 'Enerji & Elektrik Teknolojisi', 'collar' => 'her ikisi', 'intensity' => 5, 'description' => 'Siemens AG genel merkezi ve en büyük fabrikası Erlangen/Nürnberg\'de. Enerji otomasyonu, sağlık teknolojisi, rüzgar türbini.', 'companies' => ['Siemens AG', 'Siemens Energy', 'Siemens Healthineers']],
                ['name' => 'Spor & Moda', 'collar' => 'beyaz yaka', 'intensity' => 4, 'description' => 'Adidas genel merkezi Herzogenaurach (Nürnberg yakını, 25 km). Puma da aynı bölgede. Spor teknolojisi ve tasarım.', 'companies' => ['Adidas AG', 'Puma SE']],
                ['name' => 'Lojistik & E-ticaret', 'collar' => 'her ikisi', 'intensity' => 4, 'description' => 'DHL ve UPS büyük dağıtım merkezleri. E-ticaret lojistiği büyüyor.', 'companies' => ['DHL', 'UPS', 'Zalando Lojistik']],
            ],
            'avg_salary' => '€41.000/yıl brüt',
            'unemployment' => '%4.5',
            'student_jobs' => 'Siemens Werkstudent, Adidas intern, kafe. Saatlik €12-15.',
        ],

        'pros_cons' => [
            'pros' => ['Münih\'e 1 saat, fiyat yarısı', 'Siemens & Adidas kariyer fırsatları', 'Dünyaca ünlü Noel pazarı', 'FAU köklü ve güçlü', 'Tarihi şehir merkezi büyüleyici'],
            'cons' => ['Münih\'ten daha az uluslararası', 'Frankonia lehçesi tuhaf gelebilir', 'Öğrenci hayatı Münih/Berlin\'e kıyasla sakin'],
        ],

        'student_tips' => [
            'Siemens\'e Erken Başvur' => 'Werkstudent pozisyonları çok aranan. Eylül başında başvur, ilkbaharda başla.',
            'Adidas Campus\'ı Ziyaret Et' => 'Herzogenaurach\'ta Adidas\'ın kendi kampüsü var. Mağaza indirimler var.',
            'Münih\'e Gün Turu' => 'Saatlik trenle 1 saatte Münih\'e ulaşırsın. Karlı hafta sonları için harika.',
        ],

        'videos' => [
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Nürnberg\'de Öğrenci Hayatı', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Tarihi kent, Wöhrder Wiese ve öğrenci bütçesiyle Nürnberg.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'FAU Erlangen-Nürnberg Kampüs Turu', 'category' => 'üniversite', 'duration' => '8:24', 'description' => 'Friedrich-Alexander Üniversitesi\'nin iki kampüsü.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Siemens Staj Deneyimi — Nürnberg', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Elektrik-elektronik mühendisliği öğrencisi Siemens stajını anlatıyor.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Adidas Merkezi & Herzogenaurach Turu', 'category' => 'kariyer', 'duration' => '8:24', 'description' => 'Adidas kampüsü ziyareti ve intern deneyimi.'],
            ['youtube_id' => 'LzLOhMsjpsw', 'title' => 'Nürnberg Noel Pazarı — Christkindlesmarkt', 'category' => 'şehir', 'duration' => '8:24', 'description' => 'Dünyanın en ünlü Noel pazarının içinden.'],
        ],
    ],
];
