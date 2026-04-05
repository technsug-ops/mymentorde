<?php

return [
    /*
     | Her rozet: code, label, description, icon (emoji), points, condition
     | condition: ['type' => ..., 'threshold' => ...]
     | type: docs_uploaded | tickets_closed | materials_read | checklist_done
     |       profile_complete | appointment_count | feedback_given | first_login
     */
    'badges' => [
        [
            'code'        => 'first_login',
            'label'       => 'İlk Adım',
            'description' => 'Portala ilk giriş yapıldı.',
            'icon'        => '🚀',
            'points'      => 10,
            'condition'   => ['type' => 'first_login', 'threshold' => 1],
        ],
        [
            'code'        => 'profile_complete',
            'label'       => 'Profil Tamamlandı',
            'description' => 'Profil bilgileri eksiksiz dolduruldu.',
            'icon'        => '👤',
            'points'      => 20,
            'condition'   => ['type' => 'profile_complete', 'threshold' => 1],
        ],
        [
            'code'        => 'first_doc',
            'label'       => 'İlk Belge',
            'description' => 'İlk belge sisteme yüklendi.',
            'icon'        => '📄',
            'points'      => 15,
            'condition'   => ['type' => 'docs_uploaded', 'threshold' => 1],
        ],
        [
            'code'        => 'doc_hero',
            'label'       => 'Belge Kahramanı',
            'description' => '5 veya daha fazla belge yüklendi.',
            'icon'        => '📂',
            'points'      => 30,
            'condition'   => ['type' => 'docs_uploaded', 'threshold' => 5],
        ],
        [
            'code'        => 'checklist_starter',
            'label'       => 'Görev Avcısı',
            'description' => 'İlk yapılacak görevi tamamlandı.',
            'icon'        => '✅',
            'points'      => 10,
            'condition'   => ['type' => 'checklist_done', 'threshold' => 1],
        ],
        [
            'code'        => 'checklist_master',
            'label'       => 'Görev Ustası',
            'description' => '10 veya daha fazla görev tamamlandı.',
            'icon'        => '🏆',
            'points'      => 50,
            'condition'   => ['type' => 'checklist_done', 'threshold' => 10],
        ],
        [
            'code'        => 'feedback_giver',
            'label'       => 'Görüş Paylaştı',
            'description' => 'Bir geri bildirim formu dolduruldu.',
            'icon'        => '💬',
            'points'      => 15,
            'condition'   => ['type' => 'feedback_given', 'threshold' => 1],
        ],
        [
            'code'        => 'material_reader',
            'label'       => 'Öğrenme Azmi',
            'description' => '5 veya daha fazla materyal okundu.',
            'icon'        => '📚',
            'points'      => 25,
            'condition'   => ['type' => 'materials_read', 'threshold' => 5],
        ],
    ],
];
