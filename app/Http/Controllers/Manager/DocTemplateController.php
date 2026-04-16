<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DocumentBuilderTemplate;
use Illuminate\Http\Request;

class DocTemplateController extends Controller
{
    private function cid(): ?int
    {
        $v = auth()->user()?->company_id;
        return $v ? (int) $v : null;
    }

    public function index(Request $request)
    {
        $docType = $request->query('doc_type', '');
        $cid     = $this->cid();

        $templates = DocumentBuilderTemplate::where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->when($docType !== '', fn($q) => $q->where('doc_type', $docType))
            ->orderBy('doc_type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('manager.doc-templates.index', compact('templates', 'docType'));
    }

    public function create()
    {
        return view('manager.doc-templates.form', ['tpl' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id']  = $this->cid();
        $data['created_by']  = auth()->user()?->email;
        $data['version']     = 1;
        $data['is_active']   = true;

        $tpl = DocumentBuilderTemplate::create($data);

        if ($data['is_default']) {
            $this->clearOtherDefaults($tpl->id, $data['doc_type'], $data['language']);
        }

        return redirect('/manager/doc-templates')->with('status', 'Şablon oluşturuldu.');
    }

    public function edit(DocumentBuilderTemplate $tpl)
    {
        return view('manager.doc-templates.form', compact('tpl'));
    }

    public function update(Request $request, DocumentBuilderTemplate $tpl)
    {
        $data = $this->validated($request);
        $data['version'] = $tpl->version + 1;

        $tpl->update($data);

        if ($data['is_default']) {
            $this->clearOtherDefaults($tpl->id, $tpl->doc_type, $tpl->language);
        }

        return redirect('/manager/doc-templates')->with('status', 'Şablon güncellendi.');
    }

    public function destroy(DocumentBuilderTemplate $tpl)
    {
        $tpl->delete();
        return back()->with('status', 'Şablon silindi.');
    }

    public function setDefault(DocumentBuilderTemplate $tpl)
    {
        $this->clearOtherDefaults($tpl->id, $tpl->doc_type, $tpl->language);
        $tpl->update(['is_default' => true]);

        return back()->with('status', '"'.$tpl->name.'" varsayılan şablon olarak ayarlandı.');
    }

    public function preview(DocumentBuilderTemplate $tpl)
    {
        return view('manager.doc-templates.preview', [
            'tpl'      => $tpl,
            'rendered' => $this->renderTemplate($tpl),
        ]);
    }

    public function download(DocumentBuilderTemplate $tpl)
    {
        $rendered = $this->renderTemplate($tpl);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('manager.doc-templates.pdf', [
            'tpl'      => $tpl,
            'rendered' => $rendered,
        ]);
        $safeName = preg_replace('/[^A-Za-z0-9_\- ]/u', '', $tpl->name);
        $filename = 'Sablon_' . $tpl->doc_type . '_' . $safeName . '.pdf';
        return $pdf->download($filename);
    }

    /** Placeholder'lar örnek verilerle doldurulmuş düz metin döner (section_order sırasına göre). */
    private function renderTemplate(DocumentBuilderTemplate $tpl): string
    {
        $sections = is_array($tpl->section_templates) ? $tpl->section_templates : [];
        $order    = is_array($tpl->section_order) && !empty($tpl->section_order)
            ? $tpl->section_order
            : array_keys($sections);

        $sample = [
            'first_name' => 'Mustafa', 'last_name' => 'Yılmaz',
            'address' => 'Atatürk Caddesi No: 12, 34000 İstanbul, Türkei',
            'email' => 'mustafa.yilmaz@example.com', 'phone' => '+90 532 123 45 67',
            'birth_date' => '15.03.2002', 'birth_place' => 'İstanbul',
            'city' => 'İstanbul', 'date' => '[Datum]',
            'passport_number' => 'U12345678',
            'high_school_name' => 'Kadıköy Anadolu Lisesi', 'high_school_city' => 'İstanbul',
            'high_school_grade' => '90', 'high_start_year' => '2016', 'high_end_year' => '2020',
            'primary_school_name' => 'Atatürk İlköğretim Okulu', 'primary_city' => 'İstanbul',
            'primary_start_year' => '2008', 'primary_end_year' => '2016',
            'university_name' => 'Technische Universität Berlin',
            'university_address' => 'Straße des 17. Juni 135, 10623 Berlin',
            'university_city' => 'Berlin', 'university_short' => 'TU Berlin',
            'university_department' => 'Maschinenbau', 'university_degree' => 'B.Sc.',
            'university_start_year' => '2020', 'university_end_year' => '2022',
            'target_program' => 'Maschinenbau', 'target_semester' => '2026/2027',
            'german_cert' => 'Goethe-Zertifikat', 'german_level' => 'B2',
            'english_cert' => 'IELTS 7.0', 'english_level' => 'C1',
            'german_cert_detail' => 'Goethe-Zertifikat B2',
            'german_cert_year' => '2024', 'english_cert_year' => '2023',
            'internship_company' => 'ABC Mühendislik A.Ş.', 'internship_city' => 'İstanbul',
            'internship_role' => 'Praktikant im Bereich Maschinenbau',
            'internship_period' => '07/2022 – 08/2022',
            'internship_task1' => 'Mitarbeit bei Produktionsoptimierung',
            'internship_task2' => 'Datenanalyse mit Excel',
            'software_skills' => 'AutoCAD, Python, MATLAB',
            'interests' => 'Schach, Programmieren, Wandern',
            'awards' => '2021: Stipendium der Türkiye Bursları',
            'volunteer_work' => '2020-2022: Kitap Köprüsü Bildungsprojekt',
            'application_number' => 'TUB-2026-00123',
            'matrikulation_number' => '[wird beantragt]',
            'move_in_date' => '01.10.2026',
            'studentenwerk_address' => 'Hardenbergstraße 34, 10623 Berlin',
            'embassy_address' => 'Atatürk Bulvarı 114, 06540 Ankara',
            'provider_company' => 'Fintiba GmbH',
            'provider_email' => 'support@fintiba.com',
            'provider_address' => 'Bockenheimer Landstraße, 60325 Frankfurt',
            'blocked_account_bank' => 'Fintiba',
            'branch_name' => 'Berlin Mitte', 'branch_address' => 'Unter den Linden 21, 10117 Berlin',
            'city_name' => 'Berlin',
            'scholarship_or_source' => 'ein Stipendium der Türkiye Bursları',
            'scholarship_name' => 'Türkiye Bursları',
            'salutation' => 'Herr',
            'teacher_name' => 'Ahmet Şahin', 'teacher_title' => 'Mathematik- und Physiklehrer',
            'teaching_years' => '15', 'teaching_period' => 'seiner gesamten Schulzeit (9.-12. Klasse)',
            'teacher_subjects' => 'Mathematik und Physik',
            'school_phone' => '+90 216 123 45 67', 'school_email' => 'mudurluk@kadikoyanafen.edu.tr',
            'high_school_address' => 'Moda Caddesi No: 45, 34710 Kadıköy / İstanbul',
            'best_subject' => 'Mathematik', 'best_grade' => '100',
            'overall_grade' => '90',
            'pronoun_his_her' => 'seine',
            'study_level' => 'Bachelorstudium', 'degree_type' => 'Bachelor',
            'previous_degree' => 'Bachelorabschluss', 'previous_field' => 'Chemieingenieurwesen',
            'previous_university' => 'Ege Üniversitesi', 'previous_city' => 'İzmir',
            'previous_study' => 'Maschinenbau', 'semesters' => 'zwei',
            'field_interest1' => 'Thermodynamik', 'field_interest2' => 'Konstruktionstechnik',
            'field_name' => 'Volkswirtschaft',
            'interest_topic' => 'wirtschaftliche Entscheidungen die Gesellschaft beeinflussen',
            'exchange_program' => 'Schüleraustauschs nach München',
            'exchange_experience' => 'ein gut funktionierendes Wirtschaftssystem den Alltag gestaltet',
            'academic_achievement' => 'belegte den zweiten Platz bei einer nationalen Mathematik-Olympiade',
            'previous_subjects' => 'Mikro- und Makroökonomie',
            'research_interest1' => 'nachhaltige Wirtschaft', 'research_interest2' => 'internationale Entwicklungspolitik',
            'career_field' => 'erneuerbare Energien und Automobiltechnik',
            'career_goal' => 'in einer internationalen Organisation',
            'target_company_type' => 'internationalen Chemieunternehmens',
            'impact_area' => 'klimafreundlicher Produktionstechnologien',
            'industry_field' => 'chemischen Industrie',
            'bachelor_field' => 'Chemieingenieurwesen',
            'bachelor_university' => 'Ege Üniversitesi', 'bachelor_city' => 'İzmir',
            'bachelor_grade_de' => '2,3', 'bachelor_grade_tr' => '85',
            'work_duration' => 'eineinhalb Jahre', 'work_role' => 'Laborassistent',
            'work_field' => 'chemischen Betrieb',
            'work_skills' => 'Prozessoptimierung und Qualitätssicherung',
            'work_start' => 'Januar 2021', 'work_end' => 'Juni 2022',
            'work_area1' => 'der Probenvorbereitung',
            'work_area2' => 'chemischer Analysen',
            'work_area3' => 'Dokumentation von Versuchsreihen',
            'research_topic' => 'nachhaltigen Produktionsprozessen',
            'institute_name' => 'Technische Thermodynamik',
            'chair_name' => 'chemische Verfahrenstechnik',
            'specific_interest' => 'CO₂-Reduktion durch innovative Verfahrenstechnik',
            'extra_activity' => 'nahm an einer Sommeruni zu nachhaltiger Entwicklung teil',
            'professor_name' => 'Mehmet Arslan', 'professor_chair' => 'Mikroökonomie',
            'professor_email' => 'prof.mehmet.arslan@hacettepe.edu.tr',
            'university_phone' => '+90 312 234 56 78', 'faculty_name' => 'Wirtschaftswissenschaften',
            'target_university' => 'RWTH Aachen University', 'target_address' => 'Templergraben 55, 52062 Aachen',
            'course1' => 'Einführung in die Mikroökonomie',
            'course2' => 'Quantitative Methoden der Wirtschaftswissenschaften',
            'grade_system' => '4,0/4,0',
            'seminar_topic' => 'Marktversagen und staatliche Intervention',
            'company_name' => 'ABC Mühendislik A.Ş.', 'company_city' => 'İzmir',
            'company_address' => 'Sanayi Cad. No: 77, 35000 İzmir',
            'company_phone' => '+90 232 345 67 89', 'hr_email' => 'hr@abcmuhendislik.com',
            'supervisor_name' => 'Zeynep Çelik', 'supervisor_title' => 'Laborleiterin',
            'job1_role' => 'Laborassistenz', 'job1_company' => 'XY Kimya Laboratuvarı',
            'job1_city' => 'İzmir', 'job1_period' => '01/2021 – 06/2022',
            'job1_task1' => 'Vorbereitung chemischer Experimente',
            'job1_task2' => 'Datenpflege und Dokumentation',
            'job2_role' => 'Nachhilfelehrer', 'job2_company' => 'Selbstständig',
            'job2_period' => '09/2022 – 08/2023',
            'job2_task' => 'Mathematik- und Chemiehilfe Klasse 9-11',
            'high_school_branch' => 'Naturwissenschaftlicher Zweig',
            'tool2' => 'MATLAB', 'tool2_level' => 'Grundkenntnisse',
            'tool3' => 'Python', 'tool3_level' => 'Grundkenntnisse',
            'volunteer' => 'Umweltschutzprojekt Yeşil İzmir',
            'studienkolleg_address' => 'Keplerstraße 11, 70174 Stuttgart',
            'kurs_type' => 'T-Kurs', 'kurs_description' => 'Ingenieur-/Naturwissenschaften',
            'target_study_field' => 'Ingenieur',
        ];

        $out = [];
        foreach ($order as $key) {
            if (!isset($sections[$key])) {
                continue;
            }
            $content = (string) $sections[$key];
            $content = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($sample) {
                $k = $m[1];
                return $sample[$k] ?? ('[' . $k . ']');
            }, $content);
            $out[] = $content;
        }
        return implode("\n\n", $out);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'doc_type'          => 'required|in:cv,motivation,reference,cover_letter,sperrkonto,housing',
            'language'          => 'required|in:de,tr,en',
            'section_order'     => 'required|string',   // JSON string from textarea
            'section_templates' => 'required|string',   // JSON string from textarea
            'variables'         => 'nullable|string',   // JSON string from textarea
            'is_active'         => 'boolean',
            'is_default'        => 'boolean',
        ]);

        // JSON alanlarını decode et
        $data['section_order']     = json_decode($data['section_order'], true) ?? [];
        $data['section_templates'] = json_decode($data['section_templates'], true) ?? [];
        $data['variables']         = isset($data['variables']) && $data['variables'] !== ''
            ? (json_decode($data['variables'], true) ?? null)
            : null;
        $data['is_active']  = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    /** Aynı tip/dil için diğer varsayılanları kaldır */
    private function clearOtherDefaults(int $excludeId, string $docType, string $lang): void
    {
        $cid = $this->cid();
        DocumentBuilderTemplate::where('doc_type', $docType)
            ->where('language', $lang)
            ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
            ->where('id', '!=', $excludeId)
            ->update(['is_default' => false]);
    }
}
