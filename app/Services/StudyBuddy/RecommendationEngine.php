<?php

namespace App\Services\StudyBuddy;

use App\Models\Program;
use App\Models\StudyBuddyResponse;
use Illuminate\Support\Collection;

/**
 * Discovery Wizard rule-based recommendation engine (Faz 2 v0).
 *
 * Şu an: rule-based scoring (language match, degree match, finance fit,
 * field of study, location preference, public-only filter).
 *
 * Gelecek (Faz 3): historical similarity (acceptance_records ile k-NN).
 *
 * Score 0-100 — yüksek = daha iyi match.
 */
class RecommendationEngine
{
    /** Top-N recommendation döndür. */
    public function recommend(StudyBuddyResponse $response, int $limit = 10): array
    {
        $a = is_array($response->answers) ? $response->answers : [];

        // Hard filters — adayın cevaplarına aykırı programları bile sıralamadan dışla
        $query = Program::query()->active();

        // Degree filter
        if (! empty($a['target_degree'])) {
            $query->where('degree_type', $a['target_degree']);
        }

        // Language filter (de/en/both/flexible)
        if (! empty($a['study_language']) && $a['study_language'] !== 'flexible') {
            $query->whereIn('language', [$a['study_language'], 'both']);
        }

        // Public-only (öğrenci tercih ettiyse)
        if (($a['private_ok'] ?? null) === 'public_only') {
            $query->where(function ($q) {
                $q->whereNull('tuition_eur_per_semester')
                  ->orWhere('tuition_eur_per_semester', '<=', 500); // Devlet üniversitelerde semestral ücret ~300
            });
        }

        // Stochastic limit — 100x candidate'ten skor hesapla, top-N seç
        $candidates = $query->limit(max(200, $limit * 30))->get();

        // Score her programa
        $scored = $candidates->map(function (Program $p) use ($a) {
            $score = 50; // baseline
            $reasons = [];

            // Language match (30 puan)
            $studyLang = $a['study_language'] ?? null;
            if ($studyLang === 'flexible') {
                $score += 10;
            } elseif ($p->language === $studyLang) {
                $score += 30;
                $reasons[] = $studyLang === 'de' ? 'Almanca eğitim ✓' : 'İngilizce eğitim ✓';
            } elseif ($p->language === 'both') {
                $score += 25;
                $reasons[] = 'Hem Almanca hem İngilizce';
            }

            // Language level vs requirement (her programın kendi requirements'ı yok yet — basitleştir)
            $userLang = $studyLang === 'de' ? ($a['german_level'] ?? null) : ($a['english_level'] ?? null);
            if (in_array($userLang, ['b2', 'c1', 'c2', 'native'], true)) {
                $score += 10;
                $reasons[] = 'Dil seviyen yeterli';
            } elseif ($userLang === 'b1') {
                $score += 5;
            } elseif (in_array($userLang, ['none', 'a1', 'a2'], true)) {
                $score -= 15;
                $reasons[] = '⚠ Dil seviyen düşük';
            }

            // Finance match (15 puan)
            $finance = $a['finance_method'] ?? null;
            $tuition = (int) ($p->tuition_eur_per_semester ?? 0);
            if ($finance === 'self_funded' || $finance === 'sponsor') {
                // Ücretli/ücretsiz farketmez
                $score += 5;
            } elseif (in_array($finance, ['blocked_account', 'undecided'], true)) {
                if ($tuition === 0) { $score += 15; $reasons[] = 'Ücretsiz devlet üniversitesi'; }
                elseif ($tuition < 500) { $score += 10; $reasons[] = 'Düşük semester ücreti'; }
                else { $score -= 5; }
            } elseif ($finance === 'scholarship') {
                $score += 8; // Burs öğrencileri için ücret esnek
            }

            // Field of study match (15 puan)
            $userField = $a['target_field'] ?? null; // örn. 'Computer Science', 'Engineering'
            if ($userField && is_array($p->study_fields)) {
                foreach ($p->study_fields as $f) {
                    if (mb_strtolower($f) === mb_strtolower($userField) ||
                        str_contains(mb_strtolower($f), mb_strtolower($userField))) {
                        $score += 15;
                        $reasons[] = 'Alan eşleşiyor: ' . $f;
                        break;
                    }
                }
            }

            // City preference (10 puan)
            $cityPref = $a['target_city'] ?? null;
            if ($cityPref && $p->location && mb_strtolower($p->location) === mb_strtolower($cityPref)) {
                $score += 10;
                $reasons[] = "Hedef şehir: {$p->location}";
            }

            // Quality penalty (eksik veriler)
            if (($p->quality_score ?? 50) < 40) $score -= 5;

            // Cap 0-100
            $score = max(0, min(100, $score));

            return [
                'program_id'           => $p->id,
                'course_name'          => $p->course_name,
                'university_name'      => $p->university_name_cached,
                'degree_specification' => $p->degree_specification,
                'language'             => $p->language,
                'languages_raw'        => $p->languages_raw,
                'location'             => $p->location,
                'tuition_eur'          => $p->tuition_eur_per_semester,
                'duration_semesters'   => $p->duration_semesters,
                'match_score'          => $score,
                'reasons'              => $reasons,
            ];
        });

        // Top-N
        $top = $scored->sortByDesc('match_score')->take($limit)->values()->all();
        return $top;
    }
}
