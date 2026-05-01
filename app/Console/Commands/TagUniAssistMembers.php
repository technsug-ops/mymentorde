<?php

namespace App\Console\Commands;

use App\Models\University;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * uni-assist üye üniversiteleri canonical universities tablosunda işaretler.
 *
 * Akış:
 *   1. database/data/uni_assist_members.json → 196 uni-assist member
 *   2. Mevcut universities (canonical) tablosu adlarıyla fuzzy match
 *   3. Eşleşenlere is_uni_assist_member=true + uni_assist_id atar
 *
 * Eşleştirme stratejisi (cascade):
 *   1. Tam isim eşleşmesi (case-insensitive, normalize sonrası)
 *   2. "X University" ↔ "Universität X" varyantları
 *   3. Token-based contains (ör. "Bremen" ↔ "University of Bremen")
 *
 * Match etmeyenler "unmatched" olarak listelenir — manuel review için.
 *
 * Çalıştırma:
 *   php artisan unimatch:tag-uni-assist-members
 *   php artisan unimatch:tag-uni-assist-members --dry-run
 */
class TagUniAssistMembers extends Command
{
    protected $signature = 'unimatch:tag-uni-assist-members
        {--dry-run : Sadece eşleşmeleri raporla, DB değişiklik yapma}';

    protected $description = 'uni-assist üye üniversiteleri canonical katalogda işaretler';

    public function handle(): int
    {
        $jsonPath = database_path('data/uni_assist_members.json');
        if (! file_exists($jsonPath)) {
            $this->error("Bulunamadı: {$jsonPath}");
            return self::FAILURE;
        }

        $payload = json_decode(file_get_contents($jsonPath), true);
        $members = $payload['universities'] ?? [];
        if (empty($members)) {
            $this->error('JSON içinde üniversite yok.');
            return self::FAILURE;
        }

        // Manuel mapping (fuzzy match'in kaçırdığı / karıştırdığı durumlar)
        $manualPath = database_path('data/uni_assist_manual_matches.json');
        $manualMatches = [];
        if (file_exists($manualPath)) {
            $manualPayload = json_decode(file_get_contents($manualPath), true);
            $manualMatches = $manualPayload['matches'] ?? [];
        }

        $this->info('uni-assist üye listesi: ' . count($members) . ' üniversite.');
        $this->info('Manuel mapping: ' . count($manualMatches) . ' override.');
        $this->info('Canonical katalog: ' . University::count() . ' üniversite.');

        // Canonical: hem normalize ad hem core-token (noise temizlenmiş) ile index
        $canonical = University::query()->get(['id', 'name', 'city']);
        $byName = [];
        $byCore = [];
        foreach ($canonical as $u) {
            $byName[$this->normalize($u->name)][] = $u;
            $byCore[$this->coreTokens($u->name)][] = $u;
        }

        $matched   = [];
        $unmatched = [];

        foreach ($members as $m) {
            $hit = null;

            // 0) Manuel override (en yüksek öncelik)
            $uaIdStr = (string) $m['ua_id'];
            if (array_key_exists($uaIdStr, $manualMatches)) {
                $manualName = $manualMatches[$uaIdStr];
                if ($manualName === null) {
                    // null = bu ua_id'yi skip et (canonical'da yok, fuzzy match yanıltıcı)
                    $unmatched[] = $m;
                    continue;
                }
                $hit = University::query()->where('name', $manualName)->first();
                if ($hit) {
                    $matched[] = ['member' => $m, 'university' => $hit, 'method' => 'manual'];
                    continue;
                }
            }

            // 1) Tam normalize match
            $key = $this->normalize($m['name']);
            if (isset($byName[$key]) && count($byName[$key]) === 1) {
                $hit = $byName[$key][0];
            }

            // 2) Core-token match (noise temizlenmiş)
            if (! $hit) {
                $core = $this->coreTokens($m['name']);
                if ($core !== '' && isset($byCore[$core])) {
                    if (count($byCore[$core]) === 1) {
                        $hit = $byCore[$core][0];
                    } elseif (! empty($byCore[$core])) {
                        // Birden çok aday → city ile disambig
                        $cityHints = $this->extractCityHints($m['name']);
                        foreach ($byCore[$core] as $cand) {
                            $candCity = mb_strtolower((string) ($cand->city ?? ''));
                            foreach ($cityHints as $ch) {
                                if ($candCity !== '' && mb_strpos($candCity, mb_strtolower($ch)) !== false) {
                                    $hit = $cand;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            // 3) Token-contains fallback (uzun isim, az aday)
            if (! $hit) {
                $hit = $this->fuzzyContains($m['name'], $canonical);
            }

            if ($hit) {
                $matched[] = ['member' => $m, 'university' => $hit];
            } else {
                $unmatched[] = $m;
            }
        }

        $this->info("Eşleşen: " . count($matched));
        $this->info("Eşleşmeyen: " . count($unmatched));

        if (! $this->option('dry-run')) {
            // Önce hepsini false'a çek (re-run için, tek SQL — hızlı)
            \DB::table('universities')->update(['is_uni_assist_member' => false, 'uni_assist_id' => null]);

            // Aynı canonical uni'ye işaret eden farklı ua_id'leri tek girişte tut
            // (örn. Hochschule Anhalt 3 farklı kampus için 3 ua_id; biz son seen'i tutarız)
            $idToUaId = [];
            foreach ($matched as $pair) {
                $idToUaId[$pair['university']->id] = (int) $pair['member']['ua_id'];
            }

            // Batch CASE WHEN update — 50'lik chunk'lar (KAS shared hosting'de
            // tek tek update timeout yapıyordu; bu 1-2 SQL ile bitiyor)
            $totalWritten = 0;
            foreach (array_chunk($idToUaId, 50, true) as $chunk) {
                $cases = [];
                $ids = [];
                $caseBindings = [];
                foreach ($chunk as $id => $uaId) {
                    $cases[] = 'WHEN ? THEN ?';
                    $caseBindings[] = $id;
                    $caseBindings[] = $uaId;
                    $ids[] = $id;
                }
                $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
                $caseStmt = implode(' ', $cases);
                $bindings = array_merge($caseBindings, $ids);

                \DB::statement(
                    "UPDATE universities
                     SET is_uni_assist_member = 1,
                         uni_assist_id = CASE id {$caseStmt} END
                     WHERE id IN ({$idPlaceholders})",
                    $bindings
                );
                $totalWritten += count($chunk);
            }

            $this->info("DB'ye yazıldı: {$totalWritten} unique canonical üniversite işaretlendi (eşleşen üye: " . count($matched) . ").");
        } else {
            $this->warn('--dry-run: DB güncellenmedi.');
        }

        // Eşleşmeyenleri listele
        if (! empty($unmatched) && $this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->warn('Eşleşmeyenler (manuel kontrol için):');
            foreach (array_slice($unmatched, 0, 30) as $u) {
                $this->line("  - {$u['name']} (ua_id={$u['ua_id']})");
            }
            if (count($unmatched) > 30) {
                $this->line('  ... ve ' . (count($unmatched) - 30) . ' tane daha.');
            }
        } else {
            $this->newLine();
            $this->comment('Eşleşmeyen sayısı: ' . count($unmatched) . ' (detay için -v ekle)');
        }

        return self::SUCCESS;
    }

    /** Çoklu eşleştirme key adayı üretir (öncelik sırası ile). */
    private function generateMatchKeys(string $name): array
    {
        $base = $this->normalize($name);
        $keys = [$base];

        // "X University of Applied Sciences" ↔ "Hochschule X"
        $variants = [
            ' university of applied sciences' => '',
            ' university'                     => '',
            ' fachhochschule'                 => '',
            ' hochschule'                     => '',
            ' (campus '                       => ' (',
        ];
        foreach ($variants as $needle => $replace) {
            $variant = str_replace($needle, $replace, $base);
            if ($variant !== $base) $keys[] = trim($variant);
        }

        return array_values(array_unique($keys));
    }

    /** Token contains: "Bremen" ↔ "University of Bremen". Tek aday varsa eşleşir. */
    private function fuzzyContains(string $memberName, $canonical): ?University
    {
        $tokens = preg_split('/\s+/', trim(preg_replace(
            '/\b(university|of|applied|sciences|hochschule|fachhochschule|universit[äa]t|technische|für|fur|college|institute|the|and|campus)\b/iu',
            ' ',
            $memberName
        )));
        $tokens = array_filter($tokens, fn ($t) => Str::length($t) >= 4);
        if (empty($tokens)) return null;

        $candidates = [];
        foreach ($canonical as $u) {
            $matches = 0;
            $haystack = mb_strtolower($u->name);
            foreach ($tokens as $tok) {
                if (mb_strpos($haystack, mb_strtolower($tok)) !== false) {
                    $matches++;
                }
            }
            if ($matches >= count($tokens) || ($matches >= 2 && count($tokens) >= 2)) {
                $candidates[] = ['u' => $u, 'score' => $matches];
            }
        }

        if (count($candidates) === 1) return $candidates[0]['u'];
        if (count($candidates) > 1) {
            // En yüksek skoru tek başına aldıysa onu döndür
            usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);
            if ($candidates[0]['score'] > ($candidates[1]['score'] ?? 0)) {
                return $candidates[0]['u'];
            }
        }
        return null;
    }

    /** İsmi karşılaştırma için normalize et. */
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        // Almanca karakter normalizasyonu
        $s = strtr($s, [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        ]);
        // Boşluk ve noktalama temizliği
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /**
     * "Noise" terimlerini sil, sadece core kelimeleri bırak.
     * Hem İngilizce hem Almanca patterns.
     *
     * "Anhalt University of Applied Sciences (Campus Bernburg)" → "anhalt bernburg"
     * "Hochschule Anhalt" → "anhalt"
     */
    private function coreTokens(string $s): string
    {
        $n = $this->normalize($s);

        // Parantez içi (campus X, kısaltmalar) — bilgiyi tutmak için boşluğa çevir
        $n = preg_replace('/\([^)]*\)/', ' ', $n);

        // İngilizce + Almanca noise terimleri (uzun olandan kısaya — order matters)
        $noise = [
            'university of applied sciences and arts',
            'university of applied sciences',
            'university of education',
            'university of technology',
            'university of art',
            'university of arts',
            'university of music',
            'university of the arts',
            'fachhochschule fuer',
            'fachhochschule',
            'technische hochschule',
            'technische universitaet',
            'paedagogische hochschule',
            'philosophisch theologische hochschule',
            'philosophisch theologisch',
            'evangelische hochschule',
            'kunsthochschule fuer',
            'kunsthochschule',
            'medizinische hochschule',
            'hochschule fuer technik',
            'hochschule fuer musik',
            'hochschule fuer kuenste',
            'hochschule fuer angewandte wissenschaften',
            'hochschule fuer',
            'hochschule',
            'universitaet',
            'university',
            'college',
            'institute',
            'akademie',
            'school',
            'campus',
            'uas',
            'fh',
            'th',
            'tu',
            'ph',
            'hs',
            'and arts',
            'of applied sciences',
            'applied sciences',
            'of arts',
            'of art',
            'of music',
            'of education',
            'of management',
            'of health management',
            'of the arts',
            'fuer',
            'fur',
            'der',
            'die',
            'das',
            'an der',
            'and',
            'of',
            'the',
            'an',
            'in',
        ];

        // Tek seferde regex ile (word boundary)
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $noise)) . ')\b/u';
        $n = preg_replace($pattern, ' ', $n);
        $n = preg_replace('/\s+/', ' ', $n);
        $n = trim($n);

        // Duplicate token temizliği ("aschaffenburg aschaffenburg" → "aschaffenburg")
        $tokens = array_unique(explode(' ', $n));
        return trim(implode(' ', $tokens));
    }

    /** Üye isminden potansiyel şehir ipuçlarını çıkar. */
    private function extractCityHints(string $name): array
    {
        $hints = [];
        // (Campus X) → X
        if (preg_match('/\(campus\s+([^)]+)\)/i', $name, $m)) {
            $hints[] = trim($m[1]);
        }
        // Sondaki tek kelime (genelde şehir adı: "Hamburg", "München")
        if (preg_match('/\s([A-ZÄÖÜ][a-zäöüß]+)\s*$/u', $name, $m)) {
            $hints[] = $m[1];
        }
        return $hints;
    }
}
