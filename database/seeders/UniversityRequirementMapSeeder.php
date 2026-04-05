<?php

namespace Database\Seeders;

use App\Models\UniversityRequirementMap;
use Illuminate\Database\Seeder;

class UniversityRequirementMapSeeder extends Seeder
{
    public function run(): void
    {
        $maps = [

            // ── TU Berlin — Informatik Master ─────────────────────────────────
            [
                'university_code'            => 'TU_BERLIN',
                'department_code'            => 'INFORMATIK',
                'degree_type'                => 'master',
                'semester'                   => 'WS',
                'portal_name'                => 'uni_assist',
                'deadline_month_ws'          => 1,    // 15 Ocak
                'deadline_day_ws'            => 15,
                'deadline_month_ss'          => null,
                'deadline_day_ss'            => null,
                'required_document_codes'    => [
                    'APP-CV',
                    'APP-DIPLOMA',
                    'APP-TRANS-DE',
                    'APP-MOT',
                    'APP-APS',
                    'APP-LANG-DE-DSH',
                    'APP-PASSPORT',
                ],
                'recommended_document_codes' => [
                    'APP-REF1',
                    'APP-WORK-CERT',
                ],
                'language_requirement'       => 'DSH-2 oder TestDaF 4x4 oder Goethe C1',
                'min_gpa'                    => null,
                'notes'                      => 'Uni-Assist üzerinden başvuru. APS sertifikası zorunlu. '
                    . 'Program kapasitesi 40 kişi. Başvuru öncesi üniversite websitesini kontrol edin.',
                'is_active'                  => true,
            ],

            // ── Universität Bremen — Informatik Master ─────────────────────────
            [
                'university_code'            => 'UNI_BREMEN',
                'department_code'            => 'INFORMATIK',
                'degree_type'                => 'master',
                'semester'                   => 'WS',
                'portal_name'                => 'uni_assist',
                'deadline_month_ws'          => 7,    // 15 Temmuz
                'deadline_day_ws'            => 15,
                'deadline_month_ss'          => null,
                'deadline_day_ss'            => null,
                'required_document_codes'    => [
                    'APP-CV',
                    'APP-DIPLOMA',
                    'APP-TRANS-DE',
                    'APP-MOT',
                    'APP-APS',
                    'APP-LANG-DE-DSH',
                    'APP-PASSPORT',
                    'APP-PHOTO',
                ],
                'recommended_document_codes' => [
                    'APP-REF1',
                    'APP-REF2',
                ],
                'language_requirement'       => 'DSH-2 veya TestDaF 4x4',
                'min_gpa'                    => null,
                'notes'                      => 'Uni-Assist üzerinden başvuru. '
                    . 'Kış dönemi için Temmuz 15 son başvuru tarihi. '
                    . 'Yaz dönemi (SS) başvurusu kabul edilmiyor.',
                'is_active'                  => true,
            ],

            // ── Philipps-Universität Marburg — Informatik Master ───────────────
            [
                'university_code'            => 'UNI_MARBURG',
                'department_code'            => 'INFORMATIK',
                'degree_type'                => 'master',
                'semester'                   => 'WS',
                'portal_name'                => 'uni_assist',
                'deadline_month_ws'          => 1,    // 15 Ocak
                'deadline_day_ws'            => 15,
                'deadline_month_ss'          => null,
                'deadline_day_ss'            => null,
                'required_document_codes'    => [
                    'APP-CV',
                    'APP-DIPLOMA',
                    'APP-TRANS-DE',
                    'APP-MOT',
                    'APP-APS',
                    'APP-LANG-DE-DSH',
                    'APP-PASSPORT',
                ],
                'recommended_document_codes' => [
                    'APP-REF1',
                    'APP-WORK-CERT',
                ],
                'language_requirement'       => 'DSH-2 veya eşdeğer (TestDaF, Goethe C1)',
                'min_gpa'                    => null,
                'notes'                      => 'Uni-Assist üzerinden başvuru. '
                    . 'Küçük üniversite, kişisel ilgi yüksek. '
                    . 'Motivasyon mektubunun program odaklı olması önemli.',
                'is_active'                  => true,
            ],

            // ── TU Dortmund — Informatik Master ────────────────────────────────
            [
                'university_code'            => 'TU_DORTMUND',
                'department_code'            => 'INFORMATIK',
                'degree_type'                => 'master',
                'semester'                   => 'WS',
                'portal_name'                => 'direct',
                'deadline_month_ws'          => 3,    // 15 Mart
                'deadline_day_ws'            => 15,
                'deadline_month_ss'          => 9,    // 15 Eylül
                'deadline_day_ss'            => 15,
                'required_document_codes'    => [
                    'APP-CV',
                    'APP-DIPLOMA',
                    'APP-TRANS-DE',
                    'APP-MOT',
                    'APP-APS',
                    'APP-LANG-DE-DSH',
                    'APP-PASSPORT',
                ],
                'recommended_document_codes' => [
                    'APP-REF1',
                    'APP-TRANS-EN',
                ],
                'language_requirement'       => 'DSH-2 veya TestDaF 4x4',
                'min_gpa'                    => null,
                'notes'                      => 'Direkt başvuru (Uni-Assist yok). '
                    . 'Kış ve Yaz dönemi başvurusu mevcut. '
                    . 'Online başvuru formu TU Dortmund campus.de üzerinden.',
                'is_active'                  => true,
            ],

            // ── Universität Duisburg-Essen — Informatik Master ─────────────────
            [
                'university_code'            => 'UNI_DUE',
                'department_code'            => 'INFORMATIK',
                'degree_type'                => 'master',
                'semester'                   => 'WS',
                'portal_name'                => 'uni_assist',
                'deadline_month_ws'          => 3,    // 15 Mart
                'deadline_day_ws'            => 15,
                'deadline_month_ss'          => null,
                'deadline_day_ss'            => null,
                'required_document_codes'    => [
                    'APP-CV',
                    'APP-DIPLOMA',
                    'APP-DIPLOMA-NOTARIZED',
                    'APP-TRANS-DE',
                    'APP-MOT',
                    'APP-APS',
                    'APP-LANG-DE-DSH',
                    'APP-PASSPORT',
                    'APP-PHOTO',
                ],
                'recommended_document_codes' => [
                    'APP-REF1',
                    'APP-WORK-CERT',
                ],
                'language_requirement'       => 'DSH-2 veya TestDaF 4x4',
                'min_gpa'                    => null,
                'notes'                      => 'Uni-Assist üzerinden başvuru. '
                    . 'Noterli diploma kopyası zorunlu. '
                    . 'İki kampüs: Duisburg ve Essen — başvuruda kampüs tercihi belirtilir.',
                'is_active'                  => true,
            ],
        ];

        foreach ($maps as $data) {
            UniversityRequirementMap::updateOrCreate(
                [
                    'university_code' => $data['university_code'],
                    'department_code' => $data['department_code'],
                    'degree_type'     => $data['degree_type'],
                    'semester'        => $data['semester'],
                ],
                $data
            );
        }

        $this->command->info('UniversityRequirementMap: 5 kayıt eklendi (Informatik Master).');
    }
}
