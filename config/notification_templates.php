<?php

return [
    'non_dismissable' => ['contract_approved', 'task_escalation'],

    'contract_approved' => [
        'subject_tr' => 'Sözleşmeniz Onaylandı',
        'body_tr'    => 'Sayın {{student_name}}, sözleşmeniz onaylanmıştır. Öğrenci kaydınız oluşturuldu.',
        'channels'   => ['in_app', 'email'],
        'dismissable'=> false,
    ],
    'contract_rejected' => [
        'subject_tr' => 'Sözleşmeniz Reddedildi',
        'body_tr'    => 'Sözleşmeniz reddedildi. Detay: {{note}}. Lütfen tekrar başvurun.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'contract_reminder' => [
        'subject_tr' => 'Sözleşme İmza Hatırlatması',
        'body_tr'    => 'Sözleşmeniz {{days}} gündür imza bekliyor. Lütfen indirip imzalayarak yükleyin.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'document_rejected' => [
        'subject_tr' => 'Belgeniz Reddedildi',
        'body_tr'    => '{{document_name}} belgeniz reddedildi. Neden: {{review_note}}. Lütfen düzeltip yeniden yükleyin.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'document_approved' => [
        'subject_tr' => 'Belgeniz Onaylandı',
        'body_tr'    => '{{document_name}} belgeniz onaylandı.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'task_escalation' => [
        'subject_tr' => 'Görev Eskalasyonu',
        'body_tr'    => 'Görev "{{task_title}}" zamanında tamamlanmadı ve eskalasyon seviye {{level}} tetiklendi.',
        'channels'   => ['in_app'],
        'dismissable'=> false,
    ],
    'internal_message' => [
        'subject_tr' => 'Yeni Mesaj',
        'body_tr'    => '{{conversation_title}}: {{preview}}',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'system_alert' => [
        'subject_tr' => 'Sistem Bildirimi',
        'body_tr'    => '{{body}}',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'guest_contract_update' => [
        'subject_tr' => 'Sözleşme Durumu Güncellendi',
        'body_tr'    => 'Sözleşmenizin durumu güncellendi.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'lead_score_update' => [
        'subject_tr' => 'Lead Skoru Güncellendi',
        'body_tr'    => 'Lead skoru güncellendi: {{score}}',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'document_received' => [
        'subject_tr' => 'Belgeniz Alındı',
        'body_tr'    => '{{document_name}} belgeniz sisteme yüklendi ve inceleme sürecine alındı.',
        'channels'   => ['in_app', 'email'],
        'dismissable'=> true,
    ],
    'inactivity_reminder' => [
        'subject_tr' => 'Başvurunuzu Tamamlamayı Unutmayın',
        'body_tr'    => 'Sayın {{name}}, başvurunuzda {{days}} gündür işlem yapılmamış. Danışmanınız sizi bekliyor — şimdi devam edin.',
        'channels'   => ['in_app', 'email'],
        'dismissable'=> true,
    ],

    // ── HR Bildirimleri ───────────────────────────────────────────────────────

    'hr_leave_approved' => [
        'subject_tr' => 'İzin Talebiniz Onaylandı',
        'body_tr'    => 'Sayın {{employee_name}}, {{start_date}} – {{end_date}} tarihleri arasındaki {{days_count}} günlük {{leave_type}} talebiniz onaylandı.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'hr_leave_rejected' => [
        'subject_tr' => 'İzin Talebiniz Reddedildi',
        'body_tr'    => 'Sayın {{employee_name}}, {{start_date}} – {{end_date}} tarihleri arasındaki izin talebiniz reddedildi.{{rejection_note}}',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'hr_leave_new_request' => [
        'subject_tr' => 'Yeni İzin Talebi',
        'body_tr'    => '{{employee_name}} adlı çalışan {{start_date}} – {{end_date}} ({{days_count}} gün, {{leave_type}}) için izin talebi oluşturdu.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
    'hr_cert_expiring' => [
        'subject_tr' => 'Sertifika Sona Eriyor',
        'body_tr'    => '{{employee_name}} adlı çalışanın "{{cert_name}}" sertifikası {{days_left}} gün içinde sona erecek.',
        'channels'   => ['in_app'],
        'dismissable'=> true,
    ],
];
