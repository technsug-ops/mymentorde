<?php

return [
    'on_uni_acceptance' => [
        'trigger'     => 'process_outcome.acceptance',
        'description' => 'Üniversite kabulü geldiğinde',
        'is_active'   => true,
        'actions'     => [
            ['type' => 'notify_student',   'template' => 'Tebrikler {{student_name}}, {{university}} kabul mektubunuz geldi!'],
            ['type' => 'create_checklist', 'items' => ['Vize dosyası hazırla', 'Bloke hesap aç', 'Sağlık sigortası başvurusu', 'Konaklama araştırması']],
            ['type' => 'create_task',      'title' => 'Vize sürecini başlat — {{student_name}}', 'priority' => 'high', 'due_days' => 7],
            ['type' => 'make_visible',     'auto' => true],
        ],
    ],

    'on_all_docs_approved' => [
        'trigger'     => 'all_documents_approved',
        'description' => 'Tüm belgeler onaylandığında',
        'is_active'   => true,
        'actions'     => [
            ['type' => 'add_note',        'content' => 'Tüm belgeler onaylandı. Sözleşme hazırlık aşamasına geçilebilir.'],
            ['type' => 'create_task',     'title' => 'Sözleşme hazırlığı — {{student_name}}', 'priority' => 'normal', 'due_days' => 3],
            ['type' => 'update_pipeline', 'step' => 'uni_assist'],
        ],
    ],

    'on_visa_approved' => [
        'trigger'     => 'institution_document.VIS-ERTEIL',
        'description' => 'Vize onay belgesi eklendiğinde',
        'is_active'   => true,
        'actions'     => [
            ['type' => 'notify_student',   'template' => 'Harika haber {{student_name}}, vizeniz onaylandı! 🎉'],
            ['type' => 'create_checklist', 'items' => ['Uçak bileti al', 'Konaklama kesinleştir', 'Anmeldung randevusu planla']],
            ['type' => 'create_task',      'title' => 'Almanya varış hazırlığı — {{student_name}}', 'priority' => 'normal', 'due_days' => 14],
            ['type' => 'make_visible',     'auto' => true],
        ],
    ],

    'on_student_inactive_14d' => [
        'trigger'     => 'student_inactive_14_days',
        'description' => 'Öğrenci 14 gündür inaktif olduğunda',
        'is_active'   => true,
        'actions'     => [
            ['type' => 'add_note',    'content' => 'Öğrenci 14 gündür inaktif — iletişim gerekli.', 'priority' => 'high'],
            ['type' => 'create_task', 'title' => 'İletişime geç — {{student_name}}', 'priority' => 'high', 'due_days' => 1],
        ],
    ],
];
