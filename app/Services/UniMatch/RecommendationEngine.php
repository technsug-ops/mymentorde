<?php

namespace App\Services\UniMatch;

use App\Models\Program;
use App\Models\UniMatchResponse;
use Illuminate\Support\Collection;

/**
 * UniMatch rule-based recommendation engine.
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
    public function recommend(UniMatchResponse $response, int $limit = 10): array
    {
        $a = is_array($response->answers) ? $response->answers : [];

        // ── Hard filters — aday'ın cevaplarına aykırı programları çıkar ──
        $query = Program::query()->active()->with(['university:id,name,is_uni_assist_member,uni_assist_id']);

        // Degree filter
        if (! empty($a['target_degree'])) {
            $query->where('degree_type', $a['target_degree']);
        }

        // Language filter (de/en/both/flexible)
        if (! empty($a['study_language']) && $a['study_language'] !== 'flexible') {
            $query->whereIn('language', [$a['study_language'], 'both']);
        }

        // Tuition tolerance — schema değerleri: public_only / both / private_ok
        // Sadece public_only hard filter; diğerleri kullanıcı her tip uniyi kabul ediyor
        if (($a['tuition_tolerance'] ?? null) === 'public_only') {
            $query->where(function ($q) {
                $q->whereNull('tuition_eur_per_semester')->orWhere('tuition_eur_per_semester', 0);
            });
        }

        // Target field hard filter (JSON_SEARCH LIKE — liveMatchCount ile aynı mantık)
        if (! empty($a['target_field'])) {
            $query->whereRaw("JSON_SEARCH(study_fields, 'one', ?) IS NOT NULL", ['%' . (string) $a['target_field'] . '%']);
        }

        // Preferred cities hard filter (location LIKE OR — kullanıcı dolu bırakırsa strict)
        $preferredCities = is_array($a['preferred_cities'] ?? null) ? $a['preferred_cities'] : [];
        $preferredCities = array_filter(array_map('strval', $preferredCities));
        if (! empty($preferredCities)) {
            $query->where(function ($q) use ($preferredCities) {
                foreach ($preferredCities as $c) {
                    $q->orWhere('location', 'LIKE', "%{$c}%");
                }
            });
        }

        // Strict match — liveMatchCount ile aynı candidate pool
        // Limit: kullanıcının istediği kadar (default 10), strict eşleşen kaç tane varsa o kadar dönecek
        $candidates = $query->limit(max($limit, 50))->get();

        // ── Scoring ──
        $scored = $candidates->map(function (Program $p) use ($a, $preferredCities) {
            $score = 50;
            $reasons = [];

            // 1) Language match (max +30)
            $studyLang = $a['study_language'] ?? null;
            if ($studyLang === 'flexible') {
                $score += 10;
            } elseif ($p->language === $studyLang) {
                $score += 30;
                $reasons[] = $studyLang === 'de' ? 'Almanca eğitim ✓' : 'İngilizce eğitim ✓';
            } elseif ($p->language === 'both') {
                $score += 25;
                $reasons[] = 'Almanca + İngilizce';
            }

            // 2) Language level vs requirement (max +12 / -15)
            $userLang = $studyLang === 'de' ? ($a['german_level'] ?? null) : ($a['english_level'] ?? null);
            $cert = $a['language_certificate'] ?? null;
            if ($cert === 'have_de_high' || $cert === 'have_en_high' || $cert === 'have_both') {
                $score += 12;
                $reasons[] = 'Dil sertifikan var ✓';
            } elseif (in_array($userLang, ['b2', 'c1', 'c2', 'native'], true)) {
                $score += 8;
                $reasons[] = 'Dil seviyen yeterli';
            } elseif ($userLang === 'b1') {
                $score += 3;
            } elseif (in_array($userLang, ['none', 'a1', 'a2'], true)) {
                $score -= 15;
                $reasons[] = '⚠ Dil seviyen düşük';
            }

            // 3) GPA boost — yüksek not, prestijli üni şansı
            $gpa = $a['gpa_range'] ?? null;
            if ($gpa === 'excellent') $score += 5;
            elseif ($gpa === 'very_good') $score += 3;
            elseif ($gpa === 'low') $score -= 3;

            // 4) Finance match (max +15)
            $finance = $a['finance_method'] ?? null;
            $budget = $a['monthly_budget'] ?? null;
            $tuition = (int) ($p->tuition_eur_per_semester ?? 0);

            if ($finance === 'scholarship') {
                $score += 8; // Burs alacaksa ücret esnek
            } elseif (in_array($finance, ['blocked_account', 'undecided'], true) || $budget === 'tight') {
                if ($tuition === 0) { $score += 15; $reasons[] = 'Ücretsiz devlet üniversitesi'; }
                elseif ($tuition < 500) { $score += 10; }
                else { $score -= 5; }
            } elseif ($finance === 'self_funded' || $finance === 'sponsor') {
                $score += 5;
            }

            // 5) Field of study match (max +15)
            $userField = $a['target_field'] ?? null;
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

            // 6) Preferred cities match (multi, max +10)
            if (! empty($preferredCities) && $p->location) {
                foreach ($preferredCities as $city) {
                    if (mb_strtolower($p->location) === mb_strtolower((string) $city)) {
                        $score += 10;
                        $reasons[] = "Hedef şehrin: {$p->location}";
                        break;
                    }
                }
            }

            // 7) Living priority — lokasyon karakteri (basit heuristic)
            $livingPref = $a['living_priority'] ?? null;
            if ($livingPref === 'big_city' && in_array($p->location, ['Berlin', 'Munich', 'Hamburg', 'Cologne', 'Frankfurt am Main', 'Düsseldorf', 'Stuttgart'], true)) {
                $score += 4;
            } elseif ($livingPref === 'uni_town' && in_array($p->location, ['Heidelberg', 'Tübingen', 'Göttingen', 'Freiburg im Breisgau', 'Marburg', 'Jena', 'Würzburg', 'Erlangen'], true)) {
                $score += 4;
            }

            // 8) Start term — deadline yaklaşımı (soft soft scoring)
            $startTerm = $a['start_term'] ?? null;
            if ($startTerm === 'winter_2026' && $p->application_deadline_winter) {
                $score += 3; // Kış için deadline biliniyor
            } elseif ($startTerm === 'summer_2027' && $p->application_deadline_summer) {
                $score += 3;
            }

            // 9) Quality penalty/bonus (eksik veriler / detaylı veriler)
            $quality = $p->quality_score ?? 50;
            if ($quality < 40) $score -= 5;
            elseif ($quality >= 80) $score += 3;

            // 9b) Description doluysa küçük bonus (kullanıcının değerlendirmesi kolay)
            if (! empty($p->description) && mb_strlen($p->description) > 200) $score += 2;

            // 9c) TR çevirisi varsa +1 (TR aday için daha rahat okuma)
            if (! empty($p->description_tr)) $score += 1;

            // Cap 0-100
            $score = max(0, min(100, $score));

            return [
                'program_id'             => $p->id,
                'course_name'            => $p->course_name,
                'university_name'        => $p->university_name_cached,
                'is_uni_assist_member'   => (bool) ($p->university->is_uni_assist_member ?? false),
                'degree_specification'   => $p->degree_specification,
                'language'               => $p->language,
                'languages_raw'          => $p->languages_raw,
                'location'               => $p->location,
                'tuition_eur'            => $p->tuition_eur_per_semester,
                'duration_semesters'     => $p->duration_semesters,
                'match_score'            => $score,
                'reasons'                => array_slice($reasons, 0, 4),
            ];
        });

        // Top-N
        $top = $scored->sortByDesc('match_score')->take($limit)->values()->all();
        return $top;
    }
}
