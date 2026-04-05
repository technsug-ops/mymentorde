<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\MarketingTask;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Örnek görevler — process_type ve workflow_stage alanlarını doldurur.
 * Çalıştır: php artisan db:seed --class=ExampleProcessTaskSeeder
 */
class ExampleProcessTaskSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('is_active', true)->orderBy('id')->first();
        $companyId = $company?->id ?? 1;

        $assignee = User::query()
            ->whereIn('role', ['marketing_admin', 'manager', 'operations_admin'])
            ->orderBy('id')
            ->first();
        $assigneeId = $assignee?->id ?? 1;

        $now = now();

        $tasks = [
            // ── Danışan Kabul ────────────────────────────────────────
            [
                'title'          => 'Yeni başvuru: Ahmet K. — ön görüşme planla',
                'process_type'   => 'guest_intake',
                'workflow_stage' => 'intake_received',
                'status'         => 'todo',
                'priority'       => 'high',
                'department'     => 'sales',
                'due_date'       => $now->copy()->addDays(2),
            ],
            [
                'title'          => 'Zeynep T. — ihtiyaç analizi toplantısı',
                'process_type'   => 'guest_intake',
                'workflow_stage' => 'needs_assessment',
                'status'         => 'in_progress',
                'priority'       => 'medium',
                'department'     => 'sales',
                'due_date'       => $now->copy()->addDays(3),
            ],
            [
                'title'          => 'Can E. — sözleşme taslağı hazırla',
                'process_type'   => 'guest_intake',
                'workflow_stage' => 'contract_prep',
                'status'         => 'in_review',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDay(),
            ],
            [
                'title'          => 'Mehmet S. — onboarding paketi gönder',
                'process_type'   => 'guest_intake',
                'workflow_stage' => 'onboarding',
                'status'         => 'done',
                'priority'       => 'medium',
                'department'     => 'operations',
                'due_date'       => $now->copy()->subDays(2),
            ],

            // ── Evrak Yönetimi ───────────────────────────────────────
            [
                'title'          => 'Fatma Y. — transkript ve diploma talep et',
                'process_type'   => 'document_management',
                'workflow_stage' => 'doc_collection',
                'status'         => 'todo',
                'priority'       => 'medium',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(5),
            ],
            [
                'title'          => 'Ali B. — diploma apostil kontrolü',
                'process_type'   => 'document_management',
                'workflow_stage' => 'doc_review',
                'status'         => 'in_progress',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(4),
            ],
            [
                'title'          => 'Elif R. — eksik evrak düzeltme talebi',
                'process_type'   => 'document_management',
                'workflow_stage' => 'doc_correction',
                'status'         => 'blocked',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(1),
            ],

            // ── Dil Kursu ────────────────────────────────────────────
            [
                'title'          => 'Baran K. — Goethe B1 kursu kaydı',
                'process_type'   => 'language_course',
                'workflow_stage' => 'lang_enrollment',
                'status'         => 'todo',
                'priority'       => 'low',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addWeek(),
            ],
            [
                'title'          => 'Selin A. — dil kursu ödeme makbuzu kontrolü',
                'process_type'   => 'language_course',
                'workflow_stage' => 'lang_payment',
                'status'         => 'in_progress',
                'priority'       => 'medium',
                'department'     => 'finance',
                'due_date'       => $now->copy()->addDays(3),
            ],
            [
                'title'          => 'Ozan M. — B2 sınav sonucu geldi mi kontrol et',
                'process_type'   => 'language_course',
                'workflow_stage' => 'lang_exam',
                'status'         => 'in_review',
                'priority'       => 'medium',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(2),
            ],

            // ── Uni Assist ───────────────────────────────────────────
            [
                'title'          => 'Hande T. — uni-assist başvuru paketi hazırla',
                'process_type'   => 'uni_assist',
                'workflow_stage' => 'ua_package_prep',
                'status'         => 'in_progress',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(6),
            ],
            [
                'title'          => 'Emre D. — VPD belgeleri karşıya yükle',
                'process_type'   => 'uni_assist',
                'workflow_stage' => 'ua_submitted',
                'status'         => 'done',
                'priority'       => 'medium',
                'department'     => 'operations',
                'due_date'       => $now->copy()->subDays(3),
            ],
            [
                'title'          => 'Kaan Y. — uni-assist sonuç geldi, üniversiteye ilet',
                'process_type'   => 'uni_assist',
                'workflow_stage' => 'ua_result',
                'status'         => 'todo',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(2),
            ],

            // ── Vize Başvurusu ───────────────────────────────────────
            [
                'title'          => 'Derya K. — vize randevusu ayarla (Berlin konsülerlik)',
                'process_type'   => 'visa_application',
                'workflow_stage' => 'visa_appointment',
                'status'         => 'todo',
                'priority'       => 'urgent',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(1),
            ],
            [
                'title'          => 'Barış T. — vize evrak dosyası kontrol et',
                'process_type'   => 'visa_application',
                'workflow_stage' => 'visa_docs_prepared',
                'status'         => 'in_progress',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(2),
            ],
            [
                'title'          => 'Tuğba M. — vize onayı geldi, pasaport teslimi planla',
                'process_type'   => 'visa_application',
                'workflow_stage' => 'visa_approved',
                'status'         => 'in_review',
                'priority'       => 'medium',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(4),
            ],

            // ── Oturum ve İkamet ─────────────────────────────────────
            [
                'title'          => 'Serkan A. — Anmeldung randevusu oluştur',
                'process_type'   => 'residence_permit',
                'workflow_stage' => 'registration',
                'status'         => 'todo',
                'priority'       => 'medium',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(10),
            ],
            [
                'title'          => 'Yağmur B. — oturma izni başvurusu için randevu takibi',
                'process_type'   => 'residence_permit',
                'workflow_stage' => 'permit_application',
                'status'         => 'on_hold',
                'priority'       => 'high',
                'department'     => 'operations',
                'due_date'       => $now->copy()->addDays(7),
            ],
        ];

        foreach ($tasks as $data) {
            MarketingTask::create(array_merge($data, [
                'company_id'          => $companyId,
                'assigned_user_id'    => $assigneeId,
                'created_by_user_id'  => $assigneeId,
            ]));
        }

        $this->command->info('✓ ' . count($tasks) . ' örnek görev oluşturuldu.');
    }
}
