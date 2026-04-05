<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sadece boşsa ekle
        if (DB::table('automation_enrollments')->count() > 0) {
            return;
        }

        // Workflow 1 (Hoş Geldin Serisi) — ilk kayıt
        $workflow = DB::table('automation_workflows')
            ->where('name', 'Hoş Geldin Serisi')
            ->first();

        if (! $workflow) {
            return;
        }

        // İlk birkaç guest_application'ı al — en az 1 olması yeterli
        $guests = DB::table('guest_applications')
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email']);

        if ($guests->isEmpty()) {
            // Hiç guest yoksa dev fallback olarak ilk user'ı kontrol et
            // (FK ihlali oluşmaması için guest olmadan enrollment ekleyemeyiz)
            return;
        }

        // Kaç guest varsa o kadar — döngüsel kullan (1 guest bile yeterlI)
        $guestList = $guests->values();

        // Workflow node ID'lerini sıraya göre çek
        $nodes = DB::table('automation_workflow_nodes')
            ->where('workflow_id', $workflow->id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->toArray();

        $now = now();

        // Her guest için farklı senaryolu bir enrollment oluştur
        $scenarios = [
            [
                'status'       => 'completed',
                'node_idx'     => count($nodes) - 1, // son node
                'enrolled_at'  => $now->copy()->subDays(10),
                'next_check'   => null,
                'completed_at' => $now->copy()->subDays(3),
                'exit_reason'  => null,
                'metadata'     => [
                    'welcome_email_sent'     => true,
                    'document_reminder_sent' => true,
                    'advisor_notified'       => true,
                    'score_added'            => 10,
                ],
            ],
            [
                'status'      => 'waiting',
                'node_idx'    => 1, // wait node (3 gün bekle)
                'enrolled_at' => $now->copy()->subDays(2),
                'next_check'  => $now->copy()->addDay(),
                'completed_at' => null,
                'exit_reason' => null,
                'metadata'    => [
                    'welcome_email_sent' => true,
                    'waiting_until'      => $now->copy()->addDay()->toDateTimeString(),
                ],
            ],
            [
                'status'      => 'active',
                'node_idx'    => 2, // condition node
                'enrolled_at' => $now->copy()->subHours(6),
                'next_check'  => $now->copy()->addHours(2),
                'completed_at' => null,
                'exit_reason' => null,
                'metadata'    => [
                    'welcome_email_sent'      => true,
                    'documents_uploaded_count' => 0,
                ],
            ],
            [
                'status'      => 'errored',
                'node_idx'    => 0, // ilk node
                'enrolled_at' => $now->copy()->subDays(5),
                'next_check'  => null,
                'completed_at' => null,
                'exit_reason' => 'email_send_failed',
                'metadata'    => [
                    'error'   => 'SMTP connection timeout',
                    'retries' => 3,
                ],
            ],
            [
                'status'      => 'exited',
                'node_idx'    => 3, // send_email node
                'enrolled_at' => $now->copy()->subDays(7),
                'next_check'  => null,
                'completed_at' => null,
                'exit_reason' => 'unsubscribed',
                'metadata'    => [
                    'welcome_email_sent'     => true,
                    'unsubscribed_at'        => $now->copy()->subDays(4)->toDateTimeString(),
                ],
            ],
        ];

        foreach ($scenarios as $i => $scenario) {
            // Kaç guest varsa döngüsel kullan (1 guest için 5 enrollment da olabilir)
            $guest  = $guestList[$i % $guestList->count()];
            $nodeId = isset($nodes[$scenario['node_idx']]) ? $nodes[$scenario['node_idx']] : ($nodes[0] ?? null);

            DB::table('automation_enrollments')->insert([
                'workflow_id'          => $workflow->id,
                'guest_application_id' => $guest->id,
                'current_node_id'      => $nodeId,
                'status'               => $scenario['status'],
                'enrolled_at'          => $scenario['enrolled_at'],
                'next_check_at'        => $scenario['next_check'],
                'completed_at'         => $scenario['completed_at'],
                'exit_reason'          => $scenario['exit_reason'],
                'metadata'             => json_encode($scenario['metadata']),
                'created_at'           => $scenario['enrolled_at'],
                'updated_at'           => $now,
            ]);
        }

        // Her enrollment için örnek log kayıtları ekle
        $enrollments = DB::table('automation_enrollments')
            ->where('workflow_id', $workflow->id)
            ->get(['id', 'status', 'enrolled_at', 'current_node_id']);

        foreach ($enrollments as $enr) {
            DB::table('automation_enrollment_logs')->insert([
                'enrollment_id' => $enr->id,
                'node_id'       => $nodes[0] ?? null,
                'action'        => 'entered',
                'result'        => json_encode(['node_type' => 'send_email', 'label' => 'Hoş Geldin E-postası']),
                'executed_at'   => $enr->enrolled_at,
            ]);

            if (in_array($enr->status, ['completed', 'waiting', 'active', 'exited'])) {
                DB::table('automation_enrollment_logs')->insert([
                    'enrollment_id' => $enr->id,
                    'node_id'       => $nodes[0] ?? null,
                    'action'        => 'executed',
                    'result'        => json_encode(['sent' => true, 'template_key' => 'welcome']),
                    'executed_at'   => \Illuminate\Support\Carbon::parse($enr->enrolled_at)->addMinutes(2),
                ]);
            }

            if ($enr->status === 'completed') {
                DB::table('automation_enrollment_logs')->insert([
                    'enrollment_id' => $enr->id,
                    'node_id'       => end($nodes) ?: null,
                    'action'        => 'executed',
                    'result'        => json_encode(['reason' => 'workflow_completed']),
                    'executed_at'   => \Illuminate\Support\Carbon::parse($enr->enrolled_at)->addDays(7),
                ]);
            }
        }
    }

    public function down(): void
    {
        $workflow = DB::table('automation_workflows')
            ->where('name', 'Hoş Geldin Serisi')
            ->first();

        if (! $workflow) {
            return;
        }

        $enrollmentIds = DB::table('automation_enrollments')
            ->where('workflow_id', $workflow->id)
            ->pluck('id');

        DB::table('automation_enrollment_logs')->whereIn('enrollment_id', $enrollmentIds)->delete();
        DB::table('automation_enrollments')->where('workflow_id', $workflow->id)->delete();
    }
};
