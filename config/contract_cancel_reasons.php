<?php

/**
 * Sözleşme iptal nedenleri katalogu — satış sistematiğinde kayıp analizi için kullanılır.
 * Yapı: [ category_key => [ 'label', 'icon', 'reasons' => [ code => label ] ] ]
 */
return [

    'student' => [
        'label'   => 'Öğrenci Kaynaklı Nedenler',
        'icon'    => '👤',
        'reasons' => [
            'AKAD_YETER'   => 'Akademik Yetersizlik — Lise/Üniversite not ortalaması veya YKS sonucunun yetersiz kalması',
            'DIL_EKSIK'    => 'Dil Belgesi Eksikliği — İstenen sürede Almanca/İngilizce sertifikanın alınamaması',
            'FINANS'       => 'Finansal Sorunlar — Bloke hesap meblağının karşılanamaması veya garantör bulunamaması',
            'VAZGEC'       => 'Kişisel Vazgeçiş — Öğrencinin fikrini değiştirmesi veya süreci yarıda bırakması',
            'SAHTE_EVRAK'  => 'Sahte Evrak — Başvuru sürecinde yanıltıcı veya sahte belge sunulması',
        ],
    ],

    'institution' => [
        'label'   => 'Resmi Kurum Kaynaklı Nedenler',
        'icon'    => '🏛',
        'reasons' => [
            'VIZE_REDDI'   => 'Vize Reddi — Alman Konsolosluğu\'nun ulusal vize başvurusunu reddetmesi',
            'KABUL_ALAMAMA'=> 'Kabul Alamama — Başvurulan Alman üniversitelerinden ret cevabı gelmesi',
            'MEVZUAT'      => 'Mevzuat Değişikliği — Denklik şartlarında veya göç yasasında ani değişiklikler',
        ],
    ],

    'firm' => [
        'label'   => 'Danışmanlık Firması Kaynaklı Nedenler',
        'icon'    => '🏢',
        'reasons' => [
            'TARIH_KACIR'  => 'Tarihlerin Kaçırılması — Başvuru deadlinelerinin firma hatasıyla kaçırılması',
            'HIZMET_IHLAL' => 'Hizmet İhlali — Sözleşmede vadedilen danışmanlık hizmetlerinin eksik yapılması',
        ],
    ],

    'legal' => [
        'label'   => 'Hukuki ve Finansal Nedenler',
        'icon'    => '⚖️',
        'reasons' => [
            'ODEME_AKSATMA' => 'Ödemelerin Aksatılması — Danışmanın taksitleri veya hizmet bedelini ödememesi',
            'MUCBIR_SEBEP'  => 'Mücbir Sebepler — Salgın hastalık, seyahat kısıtlamaları veya savaş gibi olağanüstü durumlar',
        ],
    ],

];
