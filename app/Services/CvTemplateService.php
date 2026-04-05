<?php

namespace App\Services;

use App\Models\GuestApplication;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class CvTemplateService
{
    /**
     * Plain text'ten PDF binary üretir (DomPDF).
     * CV/Motivasyon mektupları için — Alman standartlarına uygun A4 tasarım.
     */
    public function buildPdfFromText(string $text): string
    {
        $headings = ['Lebenslauf', 'Persönliche Daten', 'Schulbildung', 'Berufserfahrung',
                     'Sprachkenntnisse', 'Computerkenntnisse', 'Interessen und Hobbys',
                     'Unterschrift', 'Motivationsschreiben', 'Empfehlungsschreiben'];

        $html = '<html><head><meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1a1a1a; margin: 0; padding: 0; }
.page { margin: 20mm 20mm 20mm 25mm; }
.title { font-size: 18pt; font-weight: bold; margin-bottom: 8pt; border-bottom: 2px solid #374151; padding-bottom: 4pt; }
.heading { font-size: 12pt; font-weight: bold; margin-top: 10pt; margin-bottom: 2pt; color: #1e3a5f; border-bottom: 1px solid #d1d5db; padding-bottom: 2pt; }
.line { font-size: 10.5pt; line-height: 1.55; margin: 1pt 0; }
.break { margin-top: 5pt; }
</style></head><body><div class="page">';

        foreach (preg_split("/\r\n|\n|\r/u", $text) ?: [] as $line) {
            $trimmed = trim((string) $line);
            $escaped = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
            if ($trimmed === '') {
                $html .= '<div class="break"></div>';
            } elseif (mb_strtolower($trimmed, 'UTF-8') === 'lebenslauf') {
                $html .= '<div class="title">' . $escaped . '</div>';
            } elseif (in_array($trimmed, $headings, true)) {
                $html .= '<div class="heading">' . $escaped . '</div>';
            } else {
                $html .= '<div class="line">' . $escaped . '</div>';
            }
        }

        $html .= '</div></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    /**
     * @param array<string,mixed> $draft
     * @return array{title:string,content:string}
     */
    public function buildGermanCv(
        GuestApplication $guest,
        array $draft,
        string $extraNotes = '',
        string $aiMode = 'template'
    ): array {
        $fullName = trim(((string) ($draft['first_name'] ?? $guest->first_name ?? '')).' '.((string) ($draft['last_name'] ?? $guest->last_name ?? '')));
        $birthDate = $this->formatDateDe((string) ($draft['birth_date'] ?? ''));
        $birthPlace = trim((string) ($draft['birth_place'] ?? ''));
        $marital = $this->trMaritalToDe((string) ($draft['marital_status'] ?? ''));
        $nationality = $this->trNationalityToDe((string) ($draft['nationality'] ?? ''));
        $address = $this->composeAddress($draft);
        $email = trim((string) ($guest->email ?? ''));
        $phone = trim((string) ($guest->phone ?? ''));

        $education = $this->buildEducationLines($draft);
        $languageLines = $this->buildLanguageLines($draft);
        $computer = $this->csvToSentence((string) ($draft['cv_computer_skills_tr'] ?? ''), 'Microsoft Office (Word, Excel, PowerPoint etc.), Online-Anwendungen');
        $hobbies = $this->csvToSentence((string) ($draft['cv_hobbies_tr'] ?? ''), 'Musik horen, Reisen, Technik, internationale Kultur');

        $signatureCity = trim((string) ($draft['cv_city_signature_tr'] ?? $draft['application_city'] ?? 'Ankara'));
        if ($signatureCity === '') {
            $signatureCity = 'Ankara';
        }
        $signatureDate = now()->format('d.m.Y');

        $content = "Lebenslauf\n\n";
        $content .= "Persönliche Daten\n\n";
        $content .= "Name     ".($fullName !== '' ? $fullName : "-")."\n";
        $content .= "Geburtsdatum   ".($birthDate !== '' ? $birthDate : "-")."\n";
        $content .= "Geburtsort    ".($birthPlace !== '' ? $birthPlace : "-")."\n";
        $content .= "Familienstand   ".($marital !== '' ? $marital : "Ledig")."\n";
        $content .= "Staatsangehörigkeit   ".($nationality !== '' ? $nationality : "Turkisch")."\n";
        $content .= "Anschrift    ".($address !== '' ? $address : "-")."\n";
        $content .= "E-Mail      ".($email !== '' ? $email : "-")."\n";
        $content .= "Telefon     ".($phone !== '' ? $phone : "-")."\n\n";

        $content .= "Schulbildung\n";
        $content .= ($education->isNotEmpty() ? $education->implode("\n") : "-")."\n\n";

        $content .= "Sprachkenntnisse\n";
        $content .= $languageLines->implode("\n")."\n\n";

        $content .= "Computerkenntnisse\n";
        $content .= $computer."\n\n";

        $content .= "Interessen und Hobbys\n";
        $content .= $hobbies."\n\n";

        $content .= $signatureCity.", ".$signatureDate."\n";

        if (trim($extraNotes) !== '') {
            $content .= "\n\nNot:\n".trim($extraNotes)."\n";
        }
        if ($aiMode === 'ai_assist') {
            $content .= "\nAI-Assist: aktiv\n";
        }

        return [
            'title' => 'Lebenslauf',
            'content' => $content,
        ];
    }

    public function buildDocxFromText(string $text): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 800,
            'marginBottom' => 800,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $lines = preg_split("/\r\n|\n|\r/u", $text) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '') {
                $section->addTextBreak(1);
                continue;
            }
            $font = ['name' => 'Calibri', 'size' => 11];
            if (mb_strtolower($trimmed, 'UTF-8') === 'lebenslauf') {
                $font = ['name' => 'Calibri', 'size' => 16, 'bold' => true];
            } elseif (in_array($trimmed, ['Persönliche Daten', 'Schulbildung', 'Sprachkenntnisse', 'Computerkenntnisse', 'Interessen und Hobbys'], true)) {
                $font = ['name' => 'Calibri', 'size' => 12, 'bold' => true];
            }
            $section->addText($trimmed, $font);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cv_docx_');
        if ($tmp === false) {
            throw new \RuntimeException('Gecici dosya olusturulamadi.');
        }
        $path = $tmp . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        $binary = (string) file_get_contents($path);
        @unlink($path);
        @unlink($tmp);
        return $binary;
    }

    /**
     * @param array<string,mixed> $draft
     * @return Collection<int,string>
     */
    private function buildEducationLines(array $draft): Collection
    {
        $lines = collect();

        $uniName = trim((string) ($draft['university_name'] ?? ''));
        $uniDept = trim((string) ($draft['university_department'] ?? ''));
        $uniStart = $this->formatMonthYear((string) ($draft['university_start_target_date'] ?? ''));
        $city = trim((string) ($draft['application_city'] ?? ''));
        if ($uniName !== '') {
            $uniLine = ($uniStart !== '' ? $uniStart."  –    " : '').$uniName;
            if ($uniDept !== '') {
                $uniLine .= "  ".$uniDept;
            }
            if ($city !== '') {
                $uniLine .= "  ".$city.", Turkei";
            }
            $lines->push($uniLine);
        }

        $highLine = $this->buildSchoolLine(
            (string) ($draft['high_start_date'] ?? ''),
            (string) ($draft['high_end_date'] ?? ''),
            (string) ($draft['high_school_name'] ?? ''),
            (string) ($draft['application_city'] ?? ''),
            (string) ($draft['high_school_grade'] ?? ''),
            'Abitur'
        );
        if ($highLine !== '') {
            $lines->push($highLine);
        }

        $middleLine = $this->buildSchoolLine(
            (string) ($draft['middle_start_date'] ?? ''),
            (string) ($draft['middle_end_date'] ?? ''),
            (string) ($draft['middle_school_name'] ?? ''),
            (string) ($draft['application_city'] ?? ''),
            (string) ($draft['middle_grade'] ?? ''),
            'Diplom der Sekundarschule'
        );
        if ($middleLine !== '') {
            $lines->push($middleLine);
        }

        $primaryLine = $this->buildSchoolLine(
            (string) ($draft['primary_start_date'] ?? ''),
            (string) ($draft['primary_end_date'] ?? ''),
            (string) ($draft['primary_school_name'] ?? ''),
            (string) ($draft['application_city'] ?? ''),
            (string) ($draft['primary_grade'] ?? ''),
            'Diplom der Grundschule'
        );
        if ($primaryLine !== '') {
            $lines->push($primaryLine);
        }

        $manual = trim((string) ($draft['cv_education_tr'] ?? ''));
        if ($lines->isEmpty() && $manual !== '') {
            collect(preg_split('/[\r\n]+/u', $manual) ?: [])
                ->map(fn ($x) => trim((string) $x))
                ->filter()
                ->each(fn ($x) => $lines->push($this->translateLightTrToDe($x)));
        }

        return $lines;
    }

    private function buildSchoolLine(
        string $startRaw,
        string $endRaw,
        string $schoolNameRaw,
        string $cityRaw,
        string $gradeRaw,
        string $abschlussLabel
    ): string {
        $school = trim($schoolNameRaw);
        if ($school === '') {
            return '';
        }
        $start = $this->formatMonthYear($startRaw);
        $end = $this->formatMonthYear($endRaw);
        $range = trim($start.($end !== '' ? "  –  ".$end : "  –"));
        $city = trim($cityRaw);
        $grade = trim($gradeRaw);

        $line = ($range !== '' ? $range.'   ' : '').$school;
        if ($city !== '') {
            $line .= '  '.$city.' / Turkei';
        }
        if ($abschlussLabel !== '') {
            $line .= '  Abschluss: '.$abschlussLabel;
            if ($grade !== '') {
                $line .= ' ('.$grade.')';
            }
        }
        return trim($line);
    }

    /**
     * @param array<string,mixed> $draft
     * @return Collection<int,string>
     */
    private function buildLanguageLines(array $draft): Collection
    {
        $lines = collect();
        $lines->push("Turkisch: Muttersprache");

        $german = $this->normalizeLangLevel((string) ($draft['german_level'] ?? 'A2'));
        $english = $this->normalizeLangLevel((string) ($draft['english_level'] ?? 'B1'));
        $other = trim((string) ($draft['other_language_level'] ?? ''));
        if ($german !== '') {
            $lines->push("Deutsch: ".$german);
        }
        if ($english !== '') {
            $lines->push("Englisch: ".$english);
        }
        if ($other !== '') {
            $lines->push($this->translateLightTrToDe($other));
        }
        return $lines;
    }

    /**
     * @param array<string,mixed> $draft
     */
    private function composeAddress(array $draft): string
    {
        $address = trim((string) ($draft['address_line'] ?? $draft['address_application'] ?? ''));
        $district = trim((string) ($draft['district'] ?? ''));
        $province = trim((string) ($draft['province'] ?? ''));
        $postal = trim((string) ($draft['postal_code'] ?? ''));
        $parts = array_filter([$address, trim($district.' '.$province), $postal, 'Turkei']);
        return trim(implode(' ', $parts));
    }

    private function formatDateDe(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($raw)->format('d.m.Y');
        } catch (\Throwable) {
            return $raw;
        }
    }

    private function formatMonthYear(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($raw)->format('m.Y');
        } catch (\Throwable) {
            if (preg_match('/^\d{2}\.\d{4}$/', $raw)) {
                return $raw;
            }
            if (preg_match('/^\d{4}$/', $raw)) {
                return '01.'.$raw;
            }
            return $raw;
        }
    }

    private function trMaritalToDe(string $val): string
    {
        return match (strtolower(trim($val))) {
            'evli' => 'Verheiratet',
            'bekar' => 'Ledig',
            'bosanmis' => 'Geschieden',
            default => '',
        };
    }

    private function trNationalityToDe(string $val): string
    {
        $v = strtolower(trim($val));
        if ($v === '' || $v === 'turk' || $v === 'turkiye' || $v === 'turkish') {
            return 'Turkisch';
        }
        return ucfirst($v);
    }

    private function normalizeLangLevel(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^(A1|A2|B1|B2|C1|C2)$/', $raw)) {
            return $raw;
        }
        return $this->translateLightTrToDe($raw);
    }

    private function csvToSentence(string $raw, string $fallback): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $fallback;
        }
        return collect(preg_split('/[\r\n,;]+/u', $raw) ?: [])
            ->map(fn ($x) => trim((string) $x))
            ->filter()
            ->map(fn ($x) => $this->translateLightTrToDe($x))
            ->implode(', ');
    }

    private function translateLightTrToDe(string $line): string
    {
        $text = trim($line);
        if ($text === '') {
            return $text;
        }
        $map = [
            'üniversite' => 'Universitat',
            'universite' => 'Universitat',
            'lise' => 'Gymnasium',
            'ortaokul' => 'Sekundarschule',
            'ilkokul' => 'Grundschule',
            'mezuniyet' => 'Abschluss',
            'ingilizce' => 'Englisch',
            'almanca' => 'Deutsch',
            'turkce' => 'Turkisch',
            'türkçe' => 'Turkisch',
            'teknik' => 'Technik',
            'seyahat' => 'Reisen',
            'muzik' => 'Musik',
            'müzik' => 'Musik',
            'takim calismasi' => 'Teamarbeit',
            'takım çalışması' => 'Teamarbeit',
        ];
        return str_ireplace(array_keys($map), array_values($map), $text);
    }
}
