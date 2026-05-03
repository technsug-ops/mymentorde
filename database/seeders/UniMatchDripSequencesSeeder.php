<?php

namespace Database\Seeders;

use App\Models\Marketing\EmailDripSequence;
use App\Models\Marketing\EmailDripStep;
use Illuminate\Database\Seeder;

class UniMatchDripSequencesSeeder extends Seeder
{
    /**
     * UniMatch için 2 drip sequence yaratır:
     *
     * 1. UniMatch Drop-off Recovery — 3 step
     *    - 3 gün sonra (72h)  → Sonuçlarını incelemedin (drip_1)
     *    - 7 gün sonra (96h)  → 5 yaygın hata (drip_2)
     *    - 14 gün sonra (168h)→ Son şans 30dk görüşme (drip_3)
     *
     * 2. UniMatch Resume Reminder — 1 step
     *    - 2 saat sonra → Yarıda kaldın, kaldığın yerden devam et
     *
     * Idempotent — firstOrCreate, mevcut sequence'leri update etmiyor
     * (manager admin runtime'da subject/delay düzenleyebilir).
     */
    public function run(): void
    {
        // ── 1. Drop-off Recovery ──
        $dropOff = EmailDripSequence::firstOrCreate(
            ['trigger_event' => 'unimatch_lead_captured'],
            [
                'name'        => 'UniMatch Drop-off Recovery',
                'description' => 'Email/WhatsApp bilgisi bırakıp convert etmeyen UniMatch lead\'lere 3-aşamalı drip mail. Convert olunca otomatik cancel.',
                'is_active'   => true,
                'created_by'  => null,
                'created_at'  => now(),
            ]
        );

        $dropOffSteps = [
            [
                'step_order'       => 1,
                'delay_hours'      => 72, // 3 gün
                'view_path'        => 'emails.unimatch.drip_1',
                'subject_override' => '🎯 UniMatch sonuçların seni bekliyor',
                'is_active'        => true,
            ],
            [
                'step_order'       => 2,
                'delay_hours'      => 96, // +4 gün (toplam 7 gün)
                'view_path'        => 'emails.unimatch.drip_2',
                'subject_override' => '5 yaygın Almanya başvuru hatası',
                'is_active'        => true,
            ],
            [
                'step_order'       => 3,
                'delay_hours'      => 168, // +7 gün (toplam 14 gün)
                'view_path'        => 'emails.unimatch.drip_3',
                'subject_override' => '⏰ Son şans — 30 dk ücretsiz görüşme',
                'is_active'        => true,
            ],
        ];

        foreach ($dropOffSteps as $stepData) {
            EmailDripStep::firstOrCreate(
                [
                    'drip_sequence_id' => $dropOff->id,
                    'step_order'       => $stepData['step_order'],
                ],
                $stepData + ['drip_sequence_id' => $dropOff->id]
            );
        }

        // ── 2. Resume Reminder ──
        $resume = EmailDripSequence::firstOrCreate(
            ['trigger_event' => 'unimatch_wizard_abandoned'],
            [
                'name'        => 'UniMatch Resume Reminder',
                'description' => 'Wizard\'ı yarıda bırakan ve email/WhatsApp veren lead\'e 2 saat sonra "kaldığın yerden devam et" mail.',
                'is_active'   => true,
                'created_by'  => null,
                'created_at'  => now(),
            ]
        );

        EmailDripStep::firstOrCreate(
            [
                'drip_sequence_id' => $resume->id,
                'step_order'       => 1,
            ],
            [
                'drip_sequence_id' => $resume->id,
                'step_order'       => 1,
                'delay_hours'      => 2,
                'view_path'        => 'emails.unimatch.resume_reminder',
                'subject_override' => '⏸️ UniMatch\'a kaldığın yerden devam et',
                'is_active'        => true,
            ]
        );

        $this->command?->info('UniMatch drip sequences seeded:');
        $this->command?->info("  - {$dropOff->name} (id {$dropOff->id}) — 3 step");
        $this->command?->info("  - {$resume->name} (id {$resume->id}) — 1 step");
    }
}
