<?php

return [
    'profile_started' => [
        'icon'     => '👤',
        'title_tr' => 'Profil Başladı',
        'desc_tr'  => 'Profilini doldurmaya başladın!',
        'condition' => 'profile_completion >= 30',
        'points'   => 5,
    ],
    'profile_complete' => [
        'icon'     => '🌟',
        'title_tr' => 'Profil Tam',
        'desc_tr'  => 'Profilin %100 tamamlandı!',
        'condition' => 'profile_completion >= 100',
        'points'   => 15,
    ],
    'first_document' => [
        'icon'     => '📄',
        'title_tr' => 'İlk Belge',
        'desc_tr'  => 'İlk belgeniz yüklendi!',
        'condition' => 'documents_uploaded >= 1',
        'points'   => 10,
    ],
    'all_docs_ready' => [
        'icon'     => '✅',
        'title_tr' => 'Belgeler Tamam',
        'desc_tr'  => 'Tüm zorunlu belgeler yüklendi!',
        'condition' => 'docs_ready = true',
        'points'   => 25,
    ],
    'package_selected' => [
        'icon'     => '📦',
        'title_tr' => 'Paket Seçildi',
        'desc_tr'  => 'Hizmet paketini seçtin!',
        'condition' => 'package_selected = true',
        'points'   => 10,
    ],
    'contract_requested' => [
        'icon'     => '📝',
        'title_tr' => 'Sözleşme Talep Edildi',
        'desc_tr'  => 'Sözleşme sürecini başlattın!',
        'condition' => 'contract_status_advanced = true',
        'points'   => 15,
    ],
    'contract_signed' => [
        'icon'     => '🎉',
        'title_tr' => 'Sözleşme İmzalandı',
        'desc_tr'  => 'İmzalı sözleşme yüklendi!',
        'condition' => 'contract_signed = true',
        'points'   => 20,
    ],
    'first_message' => [
        'icon'     => '💬',
        'title_tr' => 'İlk Mesaj',
        'desc_tr'  => 'Danışmanınla ilk mesajlaşmanı yaptın!',
        'condition' => 'dm_sent >= 1',
        'points'   => 5,
    ],
];
