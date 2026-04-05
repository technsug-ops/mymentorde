<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. 3 Canonical Tier Tanımları ─────────────────────────────────────
        $tiers = [
            [
                'code'        => 'lead_generation',
                'name_tr'     => 'Sadece İsim ve İletişim Paylaşımı (Lead Generation)',
                'name_de'     => 'Lead-Generierung',
                'name_en'     => 'Lead Generation',
                'description_tr' => 'Öğrenci adını ve iletişim bilgisini MentörDE\'ye ileten, geri kalan tüm süreci firmaya bırakan partner.',
                'description_de' => 'Partner, der nur Name und Kontaktdaten weitergibt.',
                'description_en' => 'Partner who only shares student name and contact details with MentörDE.',
                'permissions' => json_encode([
                    'tier'                   => 1,
                    'canViewStudentDetails'  => false,
                    'canViewDocuments'       => false,
                    'canUploadDocuments'     => false,
                    'canMessageStudent'      => false,
                    'canViewProcessDetails'  => false,
                    'canViewFinancials'      => true,
                    'canViewTerritoryStats'  => false,
                    'dashboardLevel'         => 'basic',
                    'canAccessCalculator'    => true,
                    'canAccessTraining'      => true,
                    'nonCompeteMonths'       => 0,
                    'contractDurationMonths' => 12,
                    'minimumLeadsPerMonth'   => 0,
                ]),
                'default_commission_config' => json_encode([
                    'type'        => 'percentage',
                    'percentage'  => 7.5,
                    'fixedAmount' => null,
                    'currency'    => 'EUR',
                    'note'        => 'Net komisyonun %5-10\'u — finalize edilecek',
                ]),
                'is_active'  => true,
                'sort_order' => 10,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'        => 'freelance_danisman',
                'name_tr'     => 'Aktif Yönlendirme ve Ön İkna (Freelance Danışmanlık)',
                'name_de'     => 'Freiberufliche Beratung',
                'name_en'     => 'Freelance Consultant',
                'description_tr' => 'Öğrenciyi kendisi araştırarak, aktif danışmanlık vererek, karar vermiş şekilde MentörDE\'ye getiren partner.',
                'description_de' => 'Partner, der aktiv berät und vorqualifizierte Studenten bringt.',
                'description_en' => 'Partner who actively consults students and brings pre-qualified leads.',
                'permissions' => json_encode([
                    'tier'                   => 2,
                    'canViewStudentDetails'  => true,
                    'canViewDocuments'       => false,
                    'canUploadDocuments'     => false,
                    'canMessageStudent'      => false,
                    'canViewProcessDetails'  => true,
                    'canViewFinancials'      => true,
                    'canViewTerritoryStats'  => false,
                    'dashboardLevel'         => 'standard',
                    'canAccessCalculator'    => true,
                    'canAccessTraining'      => true,
                    'nonCompeteMonths'       => 12,
                    'contractDurationMonths' => 12,
                    'minimumLeadsPerMonth'   => 5,
                ]),
                'default_commission_config' => json_encode([
                    'type'        => 'percentage',
                    'percentage'  => 20.0,
                    'fixedAmount' => null,
                    'currency'    => 'EUR',
                    'note'        => 'Net komisyonun %15-25\'i — finalize edilecek',
                ]),
                'is_active'  => true,
                'sort_order' => 20,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'        => 'b2b_partner',
                'name_tr'     => 'Profesyonel Partnerlik (B2B)',
                'name_de'     => 'Professionelle B2B-Partnerschaft',
                'name_en'     => 'Professional B2B Partner',
                'description_tr' => 'Kendi öğrenci portföyü, territory ve exclusive hakları olan kurumsal B2B partner.',
                'description_de' => 'Institutioneller B2B-Partner mit eigenem Portfolio und Territory.',
                'description_en' => 'Institutional B2B partner with own student portfolio, territory and exclusive rights.',
                'permissions' => json_encode([
                    'tier'                   => 3,
                    'canViewStudentDetails'  => true,
                    'canViewDocuments'       => true,
                    'canUploadDocuments'     => true,
                    'canMessageStudent'      => true,
                    'canViewProcessDetails'  => true,
                    'canViewFinancials'      => true,
                    'canViewTerritoryStats'  => true,
                    'dashboardLevel'         => 'advanced',
                    'canAccessCalculator'    => true,
                    'canAccessTraining'      => true,
                    'nonCompeteMonths'       => 24,
                    'contractDurationMonths' => 24,
                    'minimumLeadsPerMonth'   => 0,
                ]),
                'default_commission_config' => json_encode([
                    'type'        => 'contract',
                    'percentage'  => null,
                    'fixedAmount' => null,
                    'currency'    => 'EUR',
                    'note'        => 'Mevcut B2B sözleşmeye göre belirlenir',
                ]),
                'is_active'  => true,
                'sort_order' => 30,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($tiers as $tier) {
            DB::table('dealer_types')->updateOrInsert(
                ['code' => $tier['code']],
                $tier
            );
        }

        // ── 2. Eski tipleri pasif yap (legacy) ───────────────────────────────
        DB::table('dealer_types')
            ->whereIn('code', ['referrer', 'operational'])
            ->update(['is_active' => false, 'updated_at' => $now]);

        // ── 3. Mevcut dealer'ları yeni tiplere migrate et ────────────────────
        DB::table('dealers')->where('dealer_type_code', 'operational')
            ->update(['dealer_type_code' => 'b2b_partner', 'updated_at' => $now]);

        DB::table('dealers')->where('dealer_type_code', 'referrer')
            ->update(['dealer_type_code' => 'lead_generation', 'updated_at' => $now]);

        // ── 4. Revenue Milestones — 3 tier için ──────────────────────────────
        $milestones = [
            // Tier 1: Lead Generation — öğrenci ödeyince %7.5
            [
                'external_id'            => 'tier1_student_confirmed',
                'name_tr'                => 'Öğrenci Onaylandı & Ödedi (Tier 1)',
                'name_de'                => 'Student bestätigt & bezahlt (Tier 1)',
                'name_en'                => 'Student Confirmed & Paid (Tier 1)',
                'trigger_type'           => 'manual',
                'trigger_condition'      => json_encode([
                    'event'          => 'student_confirmed',
                    'requires_payment' => true,
                    'requires_school_confirm' => true,
                ]),
                'revenue_type'           => 'percentage',
                'percentage'             => 7.50,
                'fixed_amount'           => null,
                'fixed_currency'         => null,
                'applicable_dealer_types' => json_encode(['lead_generation']),
                'sort_order'             => 10,
                'is_active'              => true,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            // Tier 2: Freelance — öğrenci ödeyince %20
            [
                'external_id'            => 'tier2_student_confirmed',
                'name_tr'                => 'Öğrenci Onaylandı & Ödedi (Tier 2)',
                'name_de'                => 'Student bestätigt & bezahlt (Tier 2)',
                'name_en'                => 'Student Confirmed & Paid (Tier 2)',
                'trigger_type'           => 'manual',
                'trigger_condition'      => json_encode([
                    'event'                          => 'student_confirmed',
                    'requires_payment'               => true,
                    'requires_school_confirm'        => true,
                    'requires_consultation_verified' => true,
                ]),
                'revenue_type'           => 'percentage',
                'percentage'             => 20.00,
                'fixed_amount'           => null,
                'fixed_currency'         => null,
                'applicable_dealer_types' => json_encode(['freelance_danisman']),
                'sort_order'             => 20,
                'is_active'              => true,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            // Tier 3: B2B — sözleşmeye göre
            [
                'external_id'            => 'tier3_contract_based',
                'name_tr'                => 'B2B Sözleşme Bazlı Komisyon (Tier 3)',
                'name_de'                => 'B2B-Vertragsbasierte Provision (Tier 3)',
                'name_en'                => 'B2B Contract-Based Commission (Tier 3)',
                'trigger_type'           => 'manual',
                'trigger_condition'      => json_encode([
                    'event'              => 'student_confirmed',
                    'requires_payment'   => true,
                    'requires_school_confirm' => true,
                    'rate_from_contract' => true,
                ]),
                'revenue_type'           => 'percentage',
                'percentage'             => 10.00,
                'fixed_amount'           => null,
                'fixed_currency'         => null,
                'applicable_dealer_types' => json_encode(['b2b_partner']),
                'sort_order'             => 30,
                'is_active'              => true,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        ];

        foreach ($milestones as $ms) {
            DB::table('dealer_revenue_milestones')->updateOrInsert(
                ['external_id' => $ms['external_id']],
                $ms
            );
        }

        // ── 5. Eski milestone'ları pasif yap ─────────────────────────────────
        DB::table('dealer_revenue_milestones')
            ->whereNotIn('external_id', [
                'tier1_student_confirmed',
                'tier2_student_confirmed',
                'tier3_contract_based',
            ])
            ->update(['is_active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('dealer_types')->whereIn('code', [
            'lead_generation', 'freelance_danisman', 'b2b_partner',
        ])->delete();

        DB::table('dealer_types')->whereIn('code', ['referrer', 'operational'])
            ->update(['is_active' => true]);

        DB::table('dealers')->where('dealer_type_code', 'b2b_partner')
            ->update(['dealer_type_code' => 'operational']);

        DB::table('dealers')->where('dealer_type_code', 'lead_generation')
            ->update(['dealer_type_code' => 'referrer']);

        DB::table('dealer_revenue_milestones')
            ->whereIn('external_id', [
                'tier1_student_confirmed',
                'tier2_student_confirmed',
                'tier3_contract_based',
            ])->delete();

        DB::table('dealer_revenue_milestones')
            ->update(['is_active' => true]);
    }
};
