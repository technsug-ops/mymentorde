<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sadece boşsa ekle
        if (DB::table('automation_workflows')->count() > 0) {
            return;
        }

        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;
        $now     = now();

        // ── Workflow 1: Hoş Geldin Serisi ────────────────────────────────
        $w1 = DB::table('automation_workflows')->insertGetId([
            'name'             => 'Hoş Geldin Serisi',
            'description'      => 'Yeni kayıt olan guest adaylarına otomatik karşılama akışı. Kayıt sonrası hoş geldin e-postası, 3 gün sonra belge hatırlatması, 7 gün sonra danışman atama bildirimi gönderir.',
            'status'           => 'active',
            'trigger_type'     => 'guest_registered',
            'trigger_config'   => json_encode(['filter' => 'all']),
            'is_recurring'     => 0,
            'enrollment_limit' => null,
            'created_by'       => $adminId,
            'approved_by'      => $adminId,
            'approved_at'      => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $w1Nodes = [
            [
                'workflow_id'  => $w1,
                'node_type'    => 'send_email',
                'node_config'  => json_encode([
                    'template_key' => 'welcome',
                    'subject_tr'   => 'Almanya yolculuğuna hoş geldin, {{name}}!',
                    'delay_minutes'=> 0,
                    'label'        => 'Hoş Geldin E-postası',
                ]),
                'position_x'   => 300,
                'position_y'   => 100,
                'sort_order'   => 1,
                'connections'  => json_encode(['next' => 2]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'wait',
                'node_config'  => json_encode([
                    'duration'  => 3,
                    'unit'      => 'days',
                    'label'     => '3 Gün Bekle',
                ]),
                'position_x'   => 300,
                'position_y'   => 220,
                'sort_order'   => 2,
                'connections'  => json_encode(['next' => 3]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'condition',
                'node_config'  => json_encode([
                    'field'     => 'documents_uploaded_count',
                    'operator'  => 'equals',
                    'value'     => 0,
                    'label'     => 'Belge yüklendi mi?',
                ]),
                'position_x'   => 300,
                'position_y'   => 340,
                'sort_order'   => 3,
                'connections'  => json_encode(['yes' => 4, 'no' => 5]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'send_email',
                'node_config'  => json_encode([
                    'template_key' => 'reminder',
                    'subject_tr'   => '{{name}}, belgelerini yüklemeyi unutma!',
                    'delay_minutes'=> 0,
                    'label'        => 'Belge Hatırlatma E-postası',
                ]),
                'position_x'   => 150,
                'position_y'   => 460,
                'sort_order'   => 4,
                'connections'  => json_encode(['next' => 5]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'wait',
                'node_config'  => json_encode([
                    'duration'  => 7,
                    'unit'      => 'days',
                    'label'     => '7 Gün Bekle',
                ]),
                'position_x'   => 300,
                'position_y'   => 580,
                'sort_order'   => 5,
                'connections'  => json_encode(['next' => 6]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'send_notification',
                'node_config'  => json_encode([
                    'channel'   => 'inApp',
                    'message'   => 'Danışmanınız {{advisor_name}} sizinle iletişime geçecek.',
                    'label'     => 'Danışman Atama Bildirimi',
                ]),
                'position_x'   => 300,
                'position_y'   => 700,
                'sort_order'   => 6,
                'connections'  => json_encode(['next' => 7]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'add_score',
                'node_config'  => json_encode([
                    'score'     => 10,
                    'reason'    => 'welcome_series_completed',
                    'label'     => 'Lead Skoru +10',
                ]),
                'position_x'   => 300,
                'position_y'   => 820,
                'sort_order'   => 7,
                'connections'  => json_encode(['next' => 8]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'workflow_id'  => $w1,
                'node_type'    => 'exit',
                'node_config'  => json_encode([
                    'reason'    => 'workflow_completed',
                    'label'     => 'Akış Tamamlandı',
                ]),
                'position_x'   => 300,
                'position_y'   => 940,
                'sort_order'   => 8,
                'connections'  => json_encode([]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        DB::table('automation_workflow_nodes')->insert($w1Nodes);

        // ── Workflow 2: Belge Deadline Hatırlatıcı ───────────────────────
        $w2 = DB::table('automation_workflows')->insertGetId([
            'name'             => 'Belge Deadline Hatırlatıcı',
            'description'      => 'Başvuru belgelerini yüklemeyen adaylara deadline 7 gün kalmadan hatırlatma gönderir. Hâlâ yüklemediyse 3 gün sonra tekrar bildirim yapar.',
            'status'           => 'active',
            'trigger_type'     => 'document_deadline_approaching',
            'trigger_config'   => json_encode(['days_before' => 7]),
            'is_recurring'     => 1,
            'enrollment_limit' => null,
            'created_by'       => $adminId,
            'approved_by'      => $adminId,
            'approved_at'      => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        DB::table('automation_workflow_nodes')->insert([
            [
                'workflow_id' => $w2, 'node_type' => 'send_email',
                'node_config' => json_encode(['template_key'=>'reminder','subject_tr'=>'{{name}}, {{missing_count}} belgen eksik — son 7 günün kaldı','label'=>'7 Gün Uyarısı']),
                'position_x' => 300, 'position_y' => 100, 'sort_order' => 1,
                'connections' => json_encode(['next' => 2]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w2, 'node_type' => 'wait',
                'node_config' => json_encode(['duration'=>3,'unit'=>'days','label'=>'3 Gün Bekle']),
                'position_x' => 300, 'position_y' => 220, 'sort_order' => 2,
                'connections' => json_encode(['next' => 3]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w2, 'node_type' => 'condition',
                'node_config' => json_encode(['field'=>'documents_uploaded_count','operator'=>'equals','value'=>0,'label'=>'Hâlâ eksik belge var mı?']),
                'position_x' => 300, 'position_y' => 340, 'sort_order' => 3,
                'connections' => json_encode(['yes' => 4, 'no' => 5]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w2, 'node_type' => 'send_notification',
                'node_config' => json_encode(['channel'=>'inApp','message'=>'Belgelerini yüklemeden başvurun tamamlanamaz. Lütfen acil yükle.','label'=>'Son Uyarı Bildirimi']),
                'position_x' => 150, 'position_y' => 460, 'sort_order' => 4,
                'connections' => json_encode(['next' => 5]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w2, 'node_type' => 'exit',
                'node_config' => json_encode(['reason'=>'deadline_reminder_done','label'=>'Akış Tamamlandı']),
                'position_x' => 300, 'position_y' => 580, 'sort_order' => 5,
                'connections' => json_encode([]), 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // ── Workflow 3: Re-Engagement (Taslak) ───────────────────────────
        $w3 = DB::table('automation_workflows')->insertGetId([
            'name'             => 'Re-Engagement Kampanyası',
            'description'      => '14 gündür hareketsiz adaylara kişiselleştirilmiş re-engagement serisi. Önce e-posta, tıklamazsa WhatsApp, hâlâ cevap vermezse danışmana task oluşturur.',
            'status'           => 'draft',
            'trigger_type'     => 'lead_inactive',
            'trigger_config'   => json_encode(['days_inactive' => 14, 'min_score' => 20]),
            'is_recurring'     => 0,
            'enrollment_limit' => 500,
            'created_by'       => $adminId,
            'approved_by'      => null,
            'approved_at'      => null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        DB::table('automation_workflow_nodes')->insert([
            [
                'workflow_id' => $w3, 'node_type' => 'send_email',
                'node_config' => json_encode(['template_key'=>'re_engagement','subject_tr'=>'{{name}}, Almanya hedeflerin hâlâ seni bekliyor','label'=>'Re-Engagement E-postası']),
                'position_x' => 300, 'position_y' => 100, 'sort_order' => 1,
                'connections' => json_encode(['next' => 2]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w3, 'node_type' => 'wait',
                'node_config' => json_encode(['duration'=>3,'unit'=>'days','label'=>'3 Gün Bekle']),
                'position_x' => 300, 'position_y' => 220, 'sort_order' => 2,
                'connections' => json_encode(['next' => 3]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w3, 'node_type' => 'condition',
                'node_config' => json_encode(['field'=>'email_opened','operator'=>'equals','value'=>false,'label'=>'E-posta açıldı mı?']),
                'position_x' => 300, 'position_y' => 340, 'sort_order' => 3,
                'connections' => json_encode(['yes' => 5, 'no' => 4]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w3, 'node_type' => 'send_notification',
                'node_config' => json_encode(['channel'=>'whatsapp','message'=>'Merhaba {{name}}, Almanya başvurun için sizi bekliyoruz. Danışmanınızla konuşmak ister misiniz?','label'=>'WhatsApp Mesajı']),
                'position_x' => 150, 'position_y' => 460, 'sort_order' => 4,
                'connections' => json_encode(['next' => 5]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w3, 'node_type' => 'wait',
                'node_config' => json_encode(['duration'=>5,'unit'=>'days','label'=>'5 Gün Bekle']),
                'position_x' => 300, 'position_y' => 580, 'sort_order' => 5,
                'connections' => json_encode(['next' => 6]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w3, 'node_type' => 'create_task',
                'node_config' => json_encode(['title'=>'Re-engagement manuel temas: {{name}}','assigned_to'=>'senior','priority'=>'high','label'=>'Danışmana Task Oluştur']),
                'position_x' => 300, 'position_y' => 700, 'sort_order' => 6,
                'connections' => json_encode(['next' => 7]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'workflow_id' => $w3, 'node_type' => 'exit',
                'node_config' => json_encode(['reason'=>'re_engagement_done','label'=>'Akış Tamamlandı']),
                'position_x' => 300, 'position_y' => 820, 'sort_order' => 7,
                'connections' => json_encode([]), 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        $ids = DB::table('automation_workflows')
            ->whereIn('name', ['Hoş Geldin Serisi', 'Belge Deadline Hatırlatıcı', 'Re-Engagement Kampanyası'])
            ->pluck('id');

        DB::table('automation_workflow_nodes')->whereIn('workflow_id', $ids)->delete();
        DB::table('automation_workflows')->whereIn('id', $ids)->delete();
    }
};
