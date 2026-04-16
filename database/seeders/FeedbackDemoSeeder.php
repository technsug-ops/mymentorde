<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Feedback demo verisi: son 30 günü kapsayan guest + student submission'ları.
 * `/manager/feedback-analytics` sayfasının KPI/chart/filter'larını test etmek için.
 *
 * Çalıştırma:
 *   php artisan db:seed --class=FeedbackDemoSeeder
 */
class FeedbackDemoSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $types = ['general', 'process', 'senior', 'portal', 'nps'];
        $steps = ['application_prep', 'uni_assist', 'visa_application', 'language_course', 'residence', 'official_services'];

        $comments = [
            'Çok memnunum, danışmanım her adımda yanımdaydı.',
            'Sürecin bazı kısımları karışıktı ama sonuç iyi.',
            'Evrak hazırlığında biraz yavaş kaldık.',
            'Portal kullanımı gayet pratik, her şeyi kolayca buluyorum.',
            'Dil kursu yönlendirmesi için teşekkürler!',
            'Uni-Assist başvurusunda küçük bir yanlış anlaşılma oldu.',
            'Vize randevu bilgisi zamanında geldi, teşekkürler.',
            'Daha fazla video içerik olsa süper olur.',
            'İkamet tescil sürecinde destek çok iyiydi.',
            'Resmi hizmetler kısmını daha sadeleştirebilirsiniz.',
            'Hızlı cevap verdiğiniz için teşekkürler.',
            'Mesajlara bazen geç dönülüyor.',
            'Her şey net ve anlaşılır, mentorluk kalitesi yüksek.',
            'Ödeme planı esnek, artı puan.',
            'Dashboard bazen yavaş açılıyor.',
            '',
            '',
            'Arkadaşlarıma kesinlikle tavsiye ediyorum.',
            'Aradığım yanıtları rehberlerde buldum.',
            '',
        ];

        $guestIds = DB::table('guest_applications')->limit(20)->pluck('id')->all();
        if (empty($guestIds)) {
            $this->command?->warn('FeedbackDemoSeeder: guest_applications boş, önce guest demo seeder çalıştırılmalı.');
            return;
        }
        $studentIds = DB::table('guest_applications')
            ->whereNotNull('converted_student_id')
            ->pluck('converted_student_id')
            ->all();
        if (empty($studentIds)) {
            $studentIds = ['STU-DANA-R-0017', 'STU-BERK-A-0019', 'STU-CAN-Y-0021'];
        }

        $guestInserted = 0;
        for ($i = 0; $i < 35; $i++) {
            $type = $types[array_rand($types)];
            $step = in_array($type, ['process', 'nps']) ? $steps[array_rand($steps)] : null;
            $rating = $type === 'nps' ? null : random_int(1, 5);
            $nps = $type === 'nps' || random_int(0, 3) === 0 ? random_int(0, 10) : null;
            $comment = $comments[array_rand($comments)];
            $daysAgo = random_int(0, 29);
            $hour = random_int(8, 22);
            $minute = random_int(0, 59);
            $createdAt = Carbon::now()->subDays($daysAgo)->setTime($hour, $minute, 0);

            DB::table('guest_feedback')->insert([
                'guest_application_id' => $guestIds[array_rand($guestIds)],
                'company_id'           => $companyId,
                'feedback_type'        => $type,
                'process_step'         => $step,
                'rating'               => $rating,
                'nps_score'            => $nps,
                'comment'              => $comment,
                'created_at'           => $createdAt,
            ]);
            $guestInserted++;
        }

        $studentInserted = 0;
        for ($i = 0; $i < 20; $i++) {
            $type = $types[array_rand($types)];
            $step = in_array($type, ['process', 'nps']) ? $steps[array_rand($steps)] : null;
            $rating = $type === 'nps' ? null : random_int(2, 5);
            $nps = $type === 'nps' || random_int(0, 3) === 0 ? random_int(3, 10) : null;
            $comment = $comments[array_rand($comments)];
            $daysAgo = random_int(0, 29);
            $hour = random_int(8, 22);
            $minute = random_int(0, 59);
            $createdAt = Carbon::now()->subDays($daysAgo)->setTime($hour, $minute, 0);

            DB::table('student_feedback')->insert([
                'student_id'    => $studentIds[array_rand($studentIds)],
                'company_id'    => $companyId,
                'feedback_type' => $type,
                'process_step'  => $step,
                'rating'        => $rating,
                'nps_score'     => $nps,
                'comment'       => $comment,
                'created_at'    => $createdAt,
            ]);
            $studentInserted++;
        }

        $this->command?->info("FeedbackDemoSeeder: $guestInserted guest + $studentInserted student feedback inserted.");
    }
}
