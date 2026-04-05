<?php

namespace App\Services;

use App\Models\DocumentCategory;
use App\Models\GuestApplication;

/**
 * Ortak döküman oluşturucu mantığı.
 * StudentWorkflowController ve SeniorDashboardController tarafından kullanılır.
 */
class DocumentBuilderService
{
    public function __construct(
        private readonly AiWritingService $aiWritingService,
        private readonly CvTemplateService $cvTemplateService,
    ) {
    }

    // ── Category resolution ────────────────────────────────────────────────────

    public function resolveBuilderCategory(string $documentType): DocumentCategory
    {
        $map = [
            'cv'         => ['code' => 'DOC-CV__', 'name' => 'CV'],
            'motivation' => ['code' => 'DOC-MOTV', 'name' => 'Motivasyon Mektubu'],
            'reference'  => ['code' => 'DOC-REFR', 'name' => 'Referans Mektubu'],
        ];
        $selected = $map[$documentType] ?? ['code' => 'DOC-CV__', 'name' => 'CV'];
        $code     = (string) $selected['code'];
        $name     = (string) $selected['name'];

        return DocumentCategory::query()->firstOrCreate(
            ['code' => $code],
            [
                'name_tr'           => $name,
                'name_de'           => $name,
                'name_en'           => $name,
                'top_category_code' => 'diger_dokumanlar',
                'is_active'         => true,
                'sort_order'        => 500,
            ]
        );
    }

    // ── Main text builder ─────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed>  $draft
     * @return array{title:string,content:string}
     */
    public function buildDocumentText(
        GuestApplication $guest,
        array $draft,
        string $type,
        string $language,
        string $extraNotes,
        string $aiMode = 'template'
    ): array {
        $firstName  = trim((string) ($draft['first_name'] ?? $guest->first_name ?? ''));
        $lastName   = trim((string) ($draft['last_name'] ?? $guest->last_name ?? ''));
        $fullName   = trim($firstName . ' ' . $lastName);
        $program    = trim((string) ($draft['target_program'] ?? ''));
        $city       = trim((string) ($draft['application_city'] ?? $guest->target_city ?? ''));
        $email      = trim((string) ($guest->email ?? ''));
        $phone      = trim((string) ($guest->phone ?? ''));
        $reference  = trim((string) ($draft['reference_teacher_contact'] ?? ''));
        $motivation = trim((string) ($draft['motivation_text'] ?? ''));
        $education  = trim((string) ($draft['education_level'] ?? ''));

        $titleMap = [
            'cv'           => ['tr' => 'Ozgecmis (CV)',            'de' => 'Lebenslauf',              'en' => 'Curriculum Vitae'],
            'motivation'   => ['tr' => 'Motivasyon Mektubu',       'de' => 'Motivationsschreiben',    'en' => 'Motivation Letter'],
            'reference'    => ['tr' => 'Referans Mektubu',         'de' => 'Empfehlungsschreiben',    'en' => 'Recommendation Letter'],
            'cover_letter' => ['tr' => 'Basvuru Mektubu',          'de' => 'Anschreiben',             'en' => 'Cover Letter'],
            'sperrkonto'   => ['tr' => 'Bloke Hesap Basvurusu',    'de' => 'Sperrkonto-Antrag',       'en' => 'Blocked Account Application'],
            'housing'      => ['tr' => 'Yurt Basvurusu',           'de' => 'Wohnheimsantrag',         'en' => 'Housing Application'],
        ];
        $title = $titleMap[$type][$language] ?? $titleMap[$type]['tr'] ?? 'Belge';

        $body = "# {$title}\n\n";

        if ($type === 'cv') {
            return $this->cvTemplateService->buildGermanCv($guest, $draft, $extraNotes, $aiMode);
        }

        if ($type === 'motivation') {
            $programDe      = $program !== '' ? $this->translateLineTrToDe($program) : 'dem ausgewahlten Studiengang';
            $countryDe      = trim((string) ($draft['application_country'] ?? $guest->application_country ?? ''));
            $countryDe      = $countryDe !== '' ? $this->translateLineTrToDe($countryDe) : 'Deutschland';
            $motivationDe   = $motivation !== '' ? $this->translateLineTrToDe($motivation) : '';
            $noteParts      = $this->extractStructuredAnswers($extraNotes, ['A','B','C','D','E','F','G','H','I','J']);
            $futureGoalDe   = !empty($noteParts['I']) ? $this->translateLineTrToDe((string) $noteParts['I']) : '';
            $strengthsDe    = !empty($noteParts['D']) ? $this->translateLineTrToDe((string) $noteParts['D']) : '';
            $fitEvidenceDe  = !empty($noteParts['F']) ? $this->translateLineTrToDe((string) $noteParts['F']) : '';
            $backgroundDe   = !empty($noteParts['A']) ? $this->translateLineTrToDe((string) $noteParts['A']) : '';
            $courseIntentDe = !empty($noteParts['B']) ? $this->translateLineTrToDe((string) $noteParts['B']) : '';
            $disciplineDe   = !empty($noteParts['H']) ? $this->translateLineTrToDe((string) $noteParts['H']) : '';

            $body .= "Sehr geehrte Damen und Herren,\n\n";
            $body .= "hiermit bewerbe ich mich fur ein Studium im Bereich {$programDe} in {$countryDe}. ";
            $body .= "Mit diesem Motivationsschreiben mochte ich meine fachliche Motivation, meine personliche Eignung und meine mittel- bis langfristigen Ziele darstellen.\n\n";

            $body .= "Ich verfuge uber einen schulischen bzw. akademischen Hintergrund auf dem Niveau ";
            $body .= ($education !== '' ? $this->translateLineTrToDe($education) : 'meiner aktuellen Ausbildungsstufe');
            $body .= ". ";
            if ($city !== '') {
                $body .= "Mein geplanter Studienort bzw. Zielbezug ist {$this->translateLineTrToDe($city)}. ";
            }
            if ($backgroundDe !== '') {
                $body .= "Zusatzlich zu meinem formalen Hintergrund ist fur meine Bewerbung relevant: {$backgroundDe}. ";
            }
            $body .= "Ich habe mich bewusst fur diesen Studienweg entschieden, weil ich meine Kenntnisse strukturiert vertiefen und in einem anspruchsvollen, praxisnahen Umfeld weiterentwickeln mochte.\n\n";

            if ($motivationDe !== '') {
                $body .= $motivationDe . "\n\n";
            } else {
                $body .= "Mein Interesse an diesem Studiengang basiert auf einer langfristigen fachlichen Neugier, dem Wunsch nach einer fundierten Ausbildung sowie der Moglichkeit, theoretische Inhalte mit praktischer Anwendung zu verbinden.\n\n";
            }
            if ($courseIntentDe !== '') {
                $body .= "Fur meine Studienentscheidung ist besonders wichtig: {$courseIntentDe}\n\n";
            }

            $body .= "Ich arbeite strukturiert, zielorientiert und mit hoher Eigenverantwortung. Neue Inhalte erschliesse ich mir systematisch und setze Feedback gezielt zur Verbesserung ein. ";
            $body .= "In einem internationalen Studienumfeld kann ich mich an unterschiedliche Anforderungen anpassen und mich kontinuierlich fachlich sowie personlich weiterentwickeln.\n\n";
            if ($strengthsDe !== '') {
                $body .= "Zu meinen personlichen Starken zahlen insbesondere {$strengthsDe}.\n\n";
            }
            if (!empty($noteParts['C'])) {
                $body .= "Ein weiterer relevanter Aspekt meines Profils ist: " . $this->translateLineTrToDe((string) $noteParts['C']) . "\n\n";
            }
            if ($fitEvidenceDe !== '') {
                $body .= "Ein konkretes Beispiel fur meine fachliche Eignung ist: {$fitEvidenceDe}\n\n";
            }
            if ($disciplineDe !== '') {
                $body .= "Im Umgang mit Herausforderungen zeigt sich meine Arbeitsweise besonders deutlich: {$disciplineDe}\n\n";
            }

            $body .= "Nach dem Studium mochte ich mein Fachwissen professionell einsetzen, mich weiter spezialisieren und langfristig einen qualifizierten Beitrag in meinem Berufsfeld leisten. ";
            if ($futureGoalDe !== '') {
                $body .= $futureGoalDe . " ";
            }
            $body .= "Das Studium in Deutschland sehe ich als wichtigen Schritt fur meine fachliche und personliche Entwicklung.\n\n";

            $body .= "Ich danke Ihnen fur die Berucksichtigung meiner Bewerbung und freue mich uber die Moglichkeit, mich in Ihrem akademischen Umfeld weiterzuentwickeln und aktiv einzubringen.\n\n";
            $body .= "Mit freundlichen Grussen\n";
            $body .= ($fullName !== '' ? $fullName : 'Bewerber/in') . "\n\n";
        } elseif ($type === 'cover_letter') {
            $programDe = $program !== '' ? $this->translateLineTrToDe($program) : 'der ausgeschriebenen Stelle / dem Programm';
            $body .= "Sehr geehrte Damen und Herren,\n\n";
            $body .= "hiermit mochte ich mein Interesse an {$programDe} bekunden und bewerbe mich um einen Platz in Ihrem Programm.\n\n";
            $body .= "Mein Name ist {$fullName}";
            if ($city !== '') {
                $body .= " und ich komme aus " . $this->translateLineTrToDe($city);
            }
            $body .= ". Ich bin der Uberzeugung, dass meine bisherige Ausbildung und meine personlichen Starken mich zu einem geeigneten Bewerber machen.\n\n";
            if ($extraNotes !== '') {
                $body .= $this->translateLineTrToDe($extraNotes) . "\n\n";
            }
            $body .= "Ich freue mich auf die Moglichkeit, mich personlich vorzustellen, und stehe fur weitere Informationen jederzeit zur Verfugung.\n\n";
            $body .= "Mit freundlichen Grussen\n";
            $body .= ($fullName !== '' ? $fullName : 'Bewerber/in') . "\n";
            if ($email !== '') { $body .= "{$email}\n"; }
            if ($phone !== '') { $body .= "{$phone}\n"; }

        } elseif ($type === 'sperrkonto') {
            $bank = 'Deutsche Bank / Fidor Bank / Cortal Consors';
            $body .= "Sehr geehrte Damen und Herren,\n\n";
            $body .= "hiermit beantrage ich die Eroffnung eines Sperrkontos (blocked account) fur internationale Studierende.\n\n";
            $body .= "Angaben zur Person:\n";
            $body .= "Name: {$fullName}\n";
            if ($email !== '') { $body .= "E-Mail: {$email}\n"; }
            if ($phone !== '') { $body .= "Telefon: {$phone}\n"; }
            $body .= "\nIch benotige das Sperrkonto zum Nachweis ausreichender Finanzmittel fur mein Studium in Deutschland ";
            if ($program !== '') { $body .= "({$this->translateLineTrToDe($program)}) "; }
            $body .= "gemass den Anforderungen der deutschen Botschaft.\n\n";
            $body .= "Der erforderliche Jahresbetrag betragt derzeit 11.208 EUR (934 EUR / Monat).\n\n";
            if ($extraNotes !== '') { $body .= $extraNotes . "\n\n"; }
            $body .= "Ich bitte um schnellstmogliche Bearbeitung meines Antrags.\n\n";
            $body .= "Mit freundlichen Grussen\n";
            $body .= ($fullName !== '' ? $fullName : 'Antragsteller/in') . "\n";
            $body .= now()->format('d.m.Y') . "\n";

        } elseif ($type === 'housing') {
            $university = trim((string) ($draft['target_university'] ?? $guest->target_university ?? ''));
            $body .= "Sehr geehrte Damen und Herren,\n\n";
            $body .= "hiermit bewerbe ich mich um einen Platz im Studierendenwohnheim";
            if ($university !== '') { $body .= " der " . $this->translateLineTrToDe($university); }
            $body .= ".\n\n";
            $body .= "Zu meiner Person:\n";
            $body .= "Name: {$fullName}\n";
            if ($email !== '') { $body .= "E-Mail: {$email}\n"; }
            if ($program !== '') { $body .= "Studiengang: " . $this->translateLineTrToDe($program) . "\n"; }
            $body .= "\nIch bin internationaler Studierender und benoge dringend eine erschwingliche Unterkunft nahe dem Campus. Eine Unterkunft im Wohnheim wurde mir eine stabile Grundlage fur mein Studium bieten.\n\n";
            if ($extraNotes !== '') { $body .= $extraNotes . "\n\n"; }
            $body .= "Ich hoffe auf Ihre positive Ruckmeldung und stehe fur Ruckfragen jederzeit zur Verfugung.\n\n";
            $body .= "Mit freundlichen Grussen\n";
            $body .= ($fullName !== '' ? $fullName : 'Bewerber/in') . "\n";
            $body .= now()->format('d.m.Y') . "\n";

        } else {
            // reference
            $answers     = $this->extractStructuredAnswers($extraNotes, ['A','B','C','D','E','F','G','H','I','J']);
            $teacherLine = $reference !== '' ? $reference : (!empty($answers['A']) ? $this->translateLineTrToDe((string) $answers['A']) : '[Name des Lehrers], [Titel], [Schule/Kontakt]');
            $cityDate    = trim((string) ($draft['cv_city_signature_tr'] ?? ''));
            $cityDate    = $cityDate !== '' ? $cityDate : '[Ort]';
            $programDe   = $program !== '' ? $this->translateLineTrToDe($program) : 'dem gewunschten Studiengang';
            $candidate   = $fullName !== '' ? $fullName : '[Bewerber/in]';

            $recommendationClosing = !empty($answers['J'])
                ? $this->translateLineTrToDe((string) $answers['J'])
                : "Ich empfehle {$candidate} uneingeschrankt und mit voller Uberzeugung fur die Zulassung";
            $acquaintanceDe = !empty($answers['B']) ? $this->translateLineTrToDe((string) $answers['B']) : '';
            $academicDe     = !empty($answers['C']) ? $this->translateLineTrToDe((string) $answers['C']) : '';
            $traitsDe       = !empty($answers['D']) ? $this->translateLineTrToDe((string) $answers['D']) : '';
            $classroomDe    = !empty($answers['E']) ? $this->translateLineTrToDe((string) $answers['E']) : '';
            $projectDe      = !empty($answers['F']) ? $this->translateLineTrToDe((string) $answers['F']) : '';
            $teamworkDe     = !empty($answers['G']) ? $this->translateLineTrToDe((string) $answers['G']) : '';
            $resilienceDe   = !empty($answers['H']) ? $this->translateLineTrToDe((string) $answers['H']) : '';
            $fitDe          = !empty($answers['I']) ? $this->translateLineTrToDe((string) $answers['I']) : '';

            $body .= "Absender:\n";
            $body .= "{$teacherLine}\n\n";
            $body .= "Ort, Datum: {$cityDate}, " . now()->format('d.m.Y') . "\n\n";
            $body .= "An den Zulassungsausschuss der [Name der Universitat],\n\n";
            $body .= "Betreff: Empfehlungsschreiben fur {$candidate}\n\n";
            $body .= "Sehr geehrte Damen und Herren,\n\n";
            $body .= "hiermit empfehle ich {$candidate} mit Uberzeugung fur das Studium im Bereich {$programDe} an Ihrer Universitat.\n\n";

            if ($acquaintanceDe !== '') {
                $body .= "Ich kenne {$candidate} {$acquaintanceDe}. ";
            } else {
                $body .= "Ich kenne {$candidate} aus dem schulischen/akademischen Kontext. ";
            }
            $body .= "In dieser Zeit habe ich die Entwicklung als engagierte, lernbereite und verantwortungsbewusste Person beobachten konnen.\n\n";

            if ($academicDe !== '') {
                $body .= "Aus akademischer Sicht ist besonders hervorzuheben: {$academicDe}\n\n";
            } else {
                $body .= "Aus akademischer Sicht sind insbesondere die strukturierte Arbeitsweise, die Zuverlassigkeit und die konsequente Lernhaltung hervorzuheben.\n\n";
            }

            if ($traitsDe !== '' || $classroomDe !== '') {
                $body .= "Im Hinblick auf personliche Eigenschaften und das Verhalten im Unterricht zeigte {$candidate} ";
                $parts = [];
                if ($traitsDe !== '') {
                    $parts[] = $traitsDe;
                }
                if ($classroomDe !== '') {
                    $parts[] = $classroomDe;
                }
                $body .= implode(' / ', $parts) . ".\n\n";
            } else {
                $body .= "Im Unterricht und in betreuten Aufgaben zeigte {$candidate} kontinuierliches Interesse, Eigeninitiative sowie eine gute Teamorientierung.\n\n";
            }

            if ($projectDe !== '' || $teamworkDe !== '' || $resilienceDe !== '') {
                $body .= "Besonders aussagekraftig fur die Eignung sind folgende Beobachtungen aus Projekten und Zusammenarbeit: ";
                $projectBits = [];
                foreach ([$projectDe, $teamworkDe, $resilienceDe] as $bit) {
                    if (trim($bit) !== '') {
                        $projectBits[] = trim($bit);
                    }
                }
                $body .= implode(' ', $projectBits) . "\n\n";
            } else {
                $body .= "Auch bei anspruchsvollen Aufgaben blieb {$candidate} motiviert, nahm konstruktives Feedback an und setzte Verbesserungen zielgerichtet um.\n\n";
            }

            if ($fitDe !== '') {
                $body .= "Ich halte {$candidate} fur besonders geeignet fur ein Studium in Deutschland, weil {$fitDe}\n\n";
            } else {
                $body .= "Aufgrund der fachlichen Entwicklung, der personlichen Reife und der Motivation halte ich {$candidate} fur geeignet, ein Studium in Deutschland erfolgreich zu absolvieren und sich in ein internationales akademisches Umfeld einzubringen.\n\n";
            }

            $body .= rtrim($recommendationClosing, ". \t\n\r\0\x0B") . ". ";
            $body .= "Fur Ruckfragen stehe ich Ihnen jederzeit gerne zur Verfugung.\n\n";
            $body .= "Mit freundlichen Grussen,\n\n";
            $body .= "(Unterschrift)\n\n";
            $body .= ($reference !== '' ? $reference : (!empty($answers['A']) ? $this->translateLineTrToDe((string) $answers['A']) : '[Name des Lehrers / Position]')) . "\n\n";
        }

        if ($extraNotes !== '' && $type === 'cv') {
            $body .= "## Ek Not\n\n{$extraNotes}\n\n";
        }
        $body .= "Olusturma: " . now()->format('Y-m-d H:i:s') . "\n";

        return ['title' => $title, 'content' => $body];
    }

    // ── AI assist ─────────────────────────────────────────────────────────────

    /**
     * @param  array{title:string,content:string}  $built
     * @param  array<string,mixed>                  $draft
     * @return array{built:array{title:string,content:string},used:bool,error:?string,provider:?string,model:?string,effective_mode:string}
     */
    public function applyAiAssist(
        string $docType,
        array  $built,
        GuestApplication $guest,
        array  $draft,
        string $extraNotes
    ): array {
        $context = [
            'student_name'               => trim((string) (($draft['first_name'] ?? $guest->first_name ?? '') . ' ' . ($draft['last_name'] ?? $guest->last_name ?? ''))),
            'target_program'             => (string) ($draft['target_program'] ?? ''),
            'application_country'        => (string) ($draft['application_country'] ?? $guest->application_country ?? ''),
            'reference_teacher_contact'  => (string) ($draft['reference_teacher_contact'] ?? ''),
            'extra_notes'                => $extraNotes,
        ];

        $result = $this->aiWritingService->improveGermanDocument($docType, (string) ($built['content'] ?? ''), $context);
        if (empty($result['ok']) || trim((string) ($result['content'] ?? '')) === '') {
            return [
                'built'          => $built,
                'used'           => false,
                'error'          => (string) ($result['error'] ?? 'ai_unavailable'),
                'provider'       => $result['provider'] ?? null,
                'model'          => $result['model'] ?? null,
                'effective_mode' => 'template',
            ];
        }

        $built['content'] = trim((string) $result['content']) . "\n\nOlusturma: " . now()->format('Y-m-d H:i:s') . "\n";

        return [
            'built'          => $built,
            'used'           => true,
            'error'          => null,
            'provider'       => $result['provider'] ?? null,
            'model'          => $result['model'] ?? null,
            'effective_mode' => 'ai_assist',
        ];
    }

    /**
     * @param  array<string,mixed>|null  $aiAssistResult
     */
    public function composeReviewNote(string $userNotes, ?array $aiAssistResult): ?string
    {
        $base = trim($userNotes);
        if (!$aiAssistResult) {
            return $base !== '' ? $base : null;
        }

        $parts = [];
        if ($base !== '') {
            $parts[] = $base;
        }
        if (!empty($aiAssistResult['used'])) {
            $parts[] = '[AI] provider=' . (string) ($aiAssistResult['provider'] ?? '-') . ' model=' . (string) ($aiAssistResult['model'] ?? '-');
        } elseif (!empty($aiAssistResult['error'])) {
            $parts[] = '[AI_FALLBACK] ' . (string) $aiAssistResult['error'];
        }

        $combined = trim(implode(' | ', array_filter($parts)));
        return $combined !== '' ? $combined : null;
    }

    // ── Draft assembly for AI preview ─────────────────────────────────────────

    /**
     * @param  array<string,mixed>  $data
     */
    public function assembleStructuredDraftForAi(string $docType, array $data): string
    {
        $clean = fn(string $key): string => trim((string) ($data[$key] ?? ''));

        if ($docType === 'motivation') {
            $parts = array_filter([
                $clean('mot_background')     ? "Akademik geçmişim (A): "           . $clean('mot_background')     : '',
                $clean('mot_why_program')    ? "Bu programı seçme nedenim (B): "   . $clean('mot_why_program')    : '',
                $clean('mot_strengths')      ? "Güçlü yönlerim (D): "              . $clean('mot_strengths')      : '',
                $clean('mot_concrete')       ? "Somut proje/deneyim örneği (F): "  . $clean('mot_concrete')       : '',
                $clean('mot_why_germany')    ? "Almanya'yı seçme nedenim: "        . $clean('mot_why_germany')    : '',
                $clean('mot_lang_level')     ? "Dil seviyem: "                     . $clean('mot_lang_level')     : '',
                $clean('mot_career_goal')    ? "Kariyer hedefim (I): "             . $clean('mot_career_goal')    : '',
            ]);
            return implode("\n\n", $parts);
        }

        if ($docType === 'reference') {
            $teacherParts = array_filter([$clean('ref_name'), $clean('ref_title'), $clean('ref_institution'), $clean('ref_contact')]);
            $parts = array_filter([
                $teacherParts                 ? "Referans veren (A): "              . implode(', ', $teacherParts) : '',
                $clean('ref_how_long')        ? "Tanışma süresi ve bağlamı (B): "  . $clean('ref_how_long')       : '',
                $clean('ref_academic')        ? "Akademik performans (C): "        . $clean('ref_academic')        : '',
                $clean('ref_strengths')       ? "3 temel özellik ve kanıt (D): "   . $clean('ref_strengths')      : '',
                $clean('ref_example')         ? "Somut örnek/proje (F): "          . $clean('ref_example')         : '',
                $clean('ref_teamwork')        ? "Takım çalışması/liderlik (G): "   . $clean('ref_teamwork')        : '',
                $clean('ref_recommendation')  ? "Tavsiye düzeyi (J): "             . $clean('ref_recommendation')  : '',
            ]);
            return implode("\n\n", $parts);
        }

        return '';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function toGermanBullets(string $trText, string $fallback): string
    {
        $raw = trim($trText);
        if ($raw === '') {
            $raw = $fallback;
        }

        $parts = collect(preg_split('/[\r\n,;]+/u', $raw) ?: [])
            ->map(fn ($x) => trim((string) $x))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            $parts = collect([$fallback]);
        }

        return $parts
            ->map(fn ($line) => '- ' . $this->translateLineTrToDe((string) $line))
            ->implode("\n");
    }

    public function translateLineTrToDe(string $line): string
    {
        $text = trim($line);
        if ($text === '') {
            return $text;
        }

        $map = [
            'üniversite'      => 'Universitat',
            'universite'      => 'Universitat',
            'lise'            => 'Gymnasium',
            'mezuniyet'       => 'Abschluss',
            'ortalama'        => 'Durchschnitt',
            'staj'            => 'Praktikum',
            'deneyim'         => 'Erfahrung',
            'çalıştım'        => 'arbeitete',
            'calistim'        => 'arbeitete',
            'gönüllü'         => 'ehrenamtlich',
            'gonullu'         => 'ehrenamtlich',
            'proje'           => 'Projekt',
            'ingilizce'       => 'Englisch',
            'almanca'         => 'Deutsch',
            'ileri'           => 'fortgeschritten',
            'orta'            => 'mittel',
            'başlangıç'       => 'Grundkenntnisse',
            'baslangic'       => 'Grundkenntnisse',
            'sertifika'       => 'Zertifikat',
            'referans'        => 'Referenz',
            'takım çalışması' => 'Teamarbeit',
            'takim calismasi' => 'Teamarbeit',
            'iletişim'        => 'Kommunikation',
            'iletisim'        => 'Kommunikation',
        ];

        return str_ireplace(array_keys($map), array_values($map), $text);
    }

    /**
     * Basit A)/B)/... blok parser'i.
     *
     * @param  array<int,string>  $keys
     * @return array<string,string>
     */
    public function extractStructuredAnswers(string $text, array $keys): array
    {
        $raw = trim($text);
        if ($raw === '') {
            return [];
        }

        $results = [];
        foreach ($keys as $key) {
            $key = strtoupper(trim((string) $key));
            if ($key === '') {
                continue;
            }

            $pattern = '/(?:^|\R)\s*' . preg_quote($key, '/') . '\)\s*(.+?)(?=(?:\R\s*[A-Z]\))|\z)/si';
            if (preg_match($pattern, $raw, $m)) {
                $value = trim((string) ($m[1] ?? ''));
                if ($value !== '') {
                    $results[$key] = preg_replace('/\s+/', ' ', $value) ?: $value;
                }
            }
        }

        return $results;
    }

    // ── AI Kalite Skoru ───────────────────────────────────────────────────────

    /**
     * Belge içeriğini basit heuristiklerle değerlendirir.
     * @return array{overall:int,details:array<string,float>,word_count:int}
     */
    /**
     * Belge önizlemesi — markdown içerik + HTML render döndürür.
     */
    public function preview(
        GuestApplication $guest,
        array $draft,
        string $docType,
        string $lang,
        string $notes
    ): array {
        $built = $this->buildDocumentText($guest, $draft, $docType, $lang, $notes);
        $html  = \Illuminate\Support\Str::markdown((string) ($built['content'] ?? ''));
        return [
            'title'      => (string) ($built['title'] ?? $docType),
            'content'    => (string) ($built['content'] ?? ''),
            'html'       => $html,
            'word_count' => str_word_count((string) ($built['content'] ?? '')),
        ];
    }

    public function qualityScore(string $content, string $docType): array
    {
        $wordCount  = str_word_count($content);
        $paragraphs = substr_count($content, "\n\n") + 1;

        $targetWords      = in_array($docType, ['cv'], true) ? 300 : 500;
        $targetParagraphs = in_array($docType, ['cv'], true) ? 5 : 4;

        $scores = [
            'length'       => min(100.0, round(($wordCount / max(1, $targetWords)) * 100, 1)),
            'structure'    => min(100.0, round(($paragraphs / max(1, $targetParagraphs)) * 100, 1)),
            'has_greeting' => str_contains($content, 'Sehr geehrte') ? 100.0 : 0.0,
            'has_closing'  => str_contains($content, 'Mit freundlichen') ? 100.0 : 0.0,
        ];

        $overall = (int) round(array_sum($scores) / count($scores));

        return [
            'overall'    => $overall,
            'details'    => $scores,
            'word_count' => $wordCount,
            'label'      => match(true) {
                $overall >= 85 => 'Çok İyi',
                $overall >= 65 => 'İyi',
                $overall >= 45 => 'Orta',
                default        => 'Geliştirilebilir',
            },
        ];
    }
}
