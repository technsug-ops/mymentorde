<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Almanya öğrenci başvuru süreci için 18 hazır Almanca şablon:
 *   - Lebenslauf (3 variant)
 *   - Motivationsschreiben (3 variant)
 *   - Empfehlungsschreiben (3 variant)
 *   - Anschreiben (3 variant)
 *   - Sperrkonto-Antrag (3 variant)
 *   - Wohnheimsantrag (3 variant)
 *
 * İdempotent: aynı name ile varsa atlar (update etmez — manuel düzenlemeleri korur).
 *
 * Çalıştırma:
 *   php artisan db:seed --class=DocumentBuilderTemplatesSeeder
 */
class DocumentBuilderTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [];

        // ════════════════════════════════════════════════════════════════════════
        // 1) LEBENSLAUF (CV) — 3 variant
        // ════════════════════════════════════════════════════════════════════════
        $templates[] = [
            'doc_type' => 'cv',
            'language' => 'de',
            'name'     => 'Klasik Tabellarisch',
            'is_default' => true,
            'body'     => <<<'TXT'
LEBENSLAUF

Persönliche Daten
Name: {{first_name}} {{last_name}}
Adresse: {{address}}
Telefon: {{phone}}
E-Mail: {{email}}
Geburtsdatum: {{birth_date}}
Geburtsort: {{birth_place}}
Staatsangehörigkeit: Türkisch
Familienstand: Ledig

Schulbildung / Bildungsweg
09/{{primary_start_year}} – 06/{{primary_end_year}}  {{primary_school_name}}, {{primary_city}}  Grundschulabschluss
09/{{high_start_year}} – 06/{{high_end_year}}  {{high_school_name}}, {{high_school_city}}  Abitur (Lise Diploması) — Ø {{high_school_grade}}/100

Hochschulstudium (falls vorhanden)
{{university_start_year}} – aktuell  {{university_name}}  {{university_department}}

Sprachkenntnisse
Türkisch — Muttersprache
Deutsch — {{german_level}}
Englisch — {{english_level}}

EDV-Kenntnisse
- MS Office (Word, Excel, PowerPoint) — sehr gut
- {{software_skills}}

Praktika / Berufserfahrung
{{internship_period}}  {{internship_company}}, {{internship_city}}  {{internship_role}}

Ehrenamtliches Engagement
{{volunteer_work}}

Interessen und Hobbys
{{interests}}

{{city}}, {{date}}
{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'cv',
            'language' => 'de',
            'name'     => 'Modern Kompakt',
            'is_default' => false,
            'body'     => <<<'TXT'
# LEBENSLAUF

## {{first_name}} {{last_name}}
**Adresse:** {{address}}
**Tel:** {{phone}} | **E-Mail:** {{email}}
**Geburtsdatum:** {{birth_date}} | **Staatsangehörigkeit:** Türkisch

---

### BILDUNG
**Abitur** | {{high_school_name}}, {{high_school_city}}
`09/{{high_start_year}} – 06/{{high_end_year}}` | Note: {{high_school_grade}}/100

**{{university_degree}}** (laufend)
{{university_name}}, {{university_city}} | `{{university_start_year}} – aktuell`

---

### SPRACHKENNTNISSE
| Sprache | Zertifikat | Niveau |
|---|---|---|
| Türkisch | Muttersprache | C2 |
| Deutsch | {{german_cert}} | {{german_level}} |
| Englisch | {{english_cert}} | {{english_level}} |

---

### BERUFLICHE ERFAHRUNG
**{{internship_role}}**
{{internship_company}}, {{internship_city}} | `{{internship_period}}`
- {{internship_task1}}
- {{internship_task2}}

---

### KENNTNISSE & FÄHIGKEITEN
- **EDV:** {{software_skills}}
- **Interessen:** {{interests}}

---

### AUSZEICHNUNGEN
{{awards}}

---

{{city}}, {{date}} — {{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'cv',
            'language' => 'de',
            'name'     => 'Quereinstieg / Akademisch Detaylı',
            'is_default' => false,
            'body'     => <<<'TXT'
# LEBENSLAUF

**Name:** {{first_name}} {{last_name}}
**Anschrift:** {{address}}
**Kontakt:** {{phone}} | {{email}}
**Geburtsdatum/-ort:** {{birth_date}}, {{birth_place}} | **Staatsangehörigkeit:** Türkisch

---

## Bildungsweg

### Abitur
**{{high_school_name}}** | {{high_school_city}}, Türkei | `{{high_start_year}} – {{high_end_year}}`
{{high_school_branch}} | Abschlussnote: {{high_school_grade}}/100

### Bachelorstudium (falls abgebrochen: Anzahl der absolvierten Semester)
**{{university_name}}** | {{university_city}} | `{{university_start_year}} – {{university_end_year}}`
Studiengang: {{university_department}}

---

## Sprachkenntnisse
- **Türkisch** — Muttersprache
- **Deutsch** — {{german_level}} ({{german_cert}}, {{german_cert_year}})
- **Englisch** — {{english_level}} ({{english_cert}}, {{english_cert_year}})

---

## Berufliche Erfahrung

### {{job1_role}}
**{{job1_company}}** | {{job1_city}} | `{{job1_period}}`
- {{job1_task1}}
- {{job1_task2}}

### {{job2_role}}
**{{job2_company}}** | `{{job2_period}}`
- {{job2_task}}

---

## IT-Kenntnisse
| Tool | Niveau |
|---|---|
| MS Office | Sehr gut |
| {{tool2}} | {{tool2_level}} |
| {{tool3}} | {{tool3_level}} |

---

## Sonstiges
- Führerschein Klasse B
- Ehrenamt: {{volunteer}}
- Interessen: {{interests}}

---

{{city}}, {{date}}
{{first_name}} {{last_name}}
TXT,
        ];

        // ════════════════════════════════════════════════════════════════════════
        // 2) MOTIVATIONSSCHREIBEN — 3 variant
        // ════════════════════════════════════════════════════════════════════════
        $templates[] = [
            'doc_type' => 'motivation',
            'language' => 'de',
            'name'     => 'Teknik Bölüm (Mühendislik)',
            'is_default' => true,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

{{university_name}}
Studierendensekretariat
{{university_address}}

{{city}}, {{date}}

Motivationsschreiben für den Studiengang {{target_program}} (B.Sc.)

Sehr geehrte Damen und Herren,

mit großem Interesse bewerbe ich mich um einen Studienplatz im Bachelorstudiengang {{target_program}} an der {{university_name}}. Die hervorragende Reputation Ihrer Universität in den Ingenieurwissenschaften sowie die praxisnahe Ausbildung haben mich besonders angesprochen.

Meine akademische Grundlage
Nach meinem Abitur an der {{high_school_name}} in {{high_school_city}} (Abschlussnote: {{high_school_grade}}/100) absolvierte ich {{semesters}} Semester {{previous_study}} an der {{previous_university}}. Diese Erfahrung hat mein Interesse an Themen wie {{field_interest1}} und {{field_interest2}} weiter vertieft. Um meine akademische Ausbildung auf einem noch höheren Niveau fortzusetzen, habe ich mich entschlossen, ein Studium in Deutschland aufzunehmen.

Warum Deutschland — warum diese Universität?
Deutschland ist weltweit für seine Ingenieurskunst bekannt. Insbesondere die enge Verzahnung von Forschung und Industrie bietet Studierenden außergewöhnliche Möglichkeiten. Die {{university_name}} überzeugt mich durch ihre interdisziplinären Forschungsgruppen und die gute Vernetzung mit deutschen Unternehmen. Ich bin überzeugt, dass das Studium an Ihrer Universität mir eine solide Grundlage für meine berufliche Laufbahn bieten wird.

Sprachliche und persönliche Vorbereitung
Um gut vorbereitet nach Deutschland zu kommen, habe ich intensiv Deutsch gelernt und das {{german_cert}} ({{german_level}}) erworben. Parallel dazu habe ich ein Praktikum bei {{internship_company}} absolviert, das mir erste praktische Einblicke in das Berufsleben gegeben hat. Ich bin eine zielstrebige, offene und belastbare Person, die bereit ist, sich in eine neue Kultur und ein neues Bildungssystem zu integrieren.

Berufliche Ziele
Nach meinem Abschluss möchte ich im Bereich {{career_field}} tätig sein. Deutschland bietet mir hierfür ein ideales Umfeld. Langfristig plane ich, mein Wissen auch in mein Heimatland Türkei einzubringen.

Ich würde mich sehr freuen, die Möglichkeit zu erhalten, mein Studium an Ihrer Universität zu beginnen, und bin überzeugt, dass ich durch meine Motivation und mein Engagement einen positiven Beitrag zur Studierendengemeinschaft leisten kann.

Mit freundlichen Grüßen
{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'motivation',
            'language' => 'de',
            'name'     => 'Sosyal/Wirtschaftswissenschaften',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

{{university_name}}
Immatrikulationsbüro
{{university_address}}

{{city}}, {{date}}

Motivationsschreiben für den Studiengang {{target_program}} (B.Sc.)

Sehr geehrte Damen und Herren,

hiermit bewerbe ich mich für einen Studienplatz im Bachelorstudiengang {{target_program}} an der {{university_name}}. Als eine der renommiertesten Universitäten Europas bietet {{university_city}} ein akademisches Umfeld, das meinen wissenschaftlichen Ambitionen bestens entspricht.

Mein Interesse an der {{field_name}}
Bereits während meiner Schulzeit an der {{high_school_name}} faszinierte mich die Frage, wie {{interest_topic}}. Im Rahmen eines {{exchange_program}} konnte ich erleben, wie {{exchange_experience}}. Diese Erfahrung verstärkte meinen Wunsch, {{field_name}} auf höchstem Niveau zu studieren.

Akademische Leistungen und Vorbereitung
Mein Abitur schloss ich mit einer Durchschnittsnote von {{high_school_grade}}/100 ab und {{academic_achievement}}. Im Rahmen meines bisherigen Studiums an der {{previous_university}} in {{previous_city}} erwarb ich Grundkenntnisse in {{previous_subjects}}. Meine Deutschkenntnisse habe ich durch das {{german_cert}} ({{german_level}}) nachgewiesen.

Interessen und Ziele
Besonders interessieren mich die Themen {{research_interest1}} und {{research_interest2}}. Die Forschungsschwerpunkte der Fakultät {{university_city}} decken sich hervorragend mit meinen akademischen Interessen. Nach dem Studium strebe ich eine Karriere {{career_goal}} an.

Ich bin fest davon überzeugt, dass ich durch Fleiß, Neugier und meinen kulturellen Hintergrund als Türk(e/in) in Deutschland eine bereichernde Perspektive einbringen kann. Ich freue mich auf die Herausforderungen und Chancen, die ein Studium an der {{university_name}} mit sich bringt.

Mit freundlichen Grüßen
{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'motivation',
            'language' => 'de',
            'name'     => 'Master / İleri Studium',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

{{university_name}}
Dekanat der Fakultät für {{faculty_name}}
{{university_address}}

{{city}}, {{date}}

Motivationsschreiben für den Masterstudiengang {{target_program}} (M.Sc.)

Sehr geehrte Damen und Herren,

mit großer Begeisterung bewerbe ich mich um einen Platz im Masterstudiengang {{target_program}} an der {{university_name}}. Die {{university_short}} zählt zu den führenden technischen Hochschulen Europas und bietet durch ihre enge Kooperation mit der {{industry_field}} optimale Voraussetzungen für eine erstklassige wissenschaftliche Ausbildung.

Mein akademischer und beruflicher Hintergrund
Meinen Bachelor of Science in {{bachelor_field}} habe ich an der {{bachelor_university}} in {{bachelor_city}} mit einer Durchschnittsnote von {{bachelor_grade_de}} (türkisches System: {{bachelor_grade_tr}}/100) abgeschlossen. Parallel zum Studium arbeitete ich {{work_duration}} als {{work_role}} in einem {{work_field}}, wo ich Kenntnisse in {{work_skills}} erworben habe. Diese Erfahrungen haben in mir den Wunsch geweckt, mich vertieft mit {{research_topic}} auseinanderzusetzen.

Warum {{university_short}}?
Die Forschungsgruppen Ihres Instituts für {{institute_name}} und der Lehrstuhl für {{chair_name}} decken sich direkt mit meinen Forschungsinteressen, insbesondere im Bereich der {{specific_interest}}. Die Möglichkeit, im Rahmen des Masterstudiums an realen industriellen Projekten mitzuwirken, macht die {{university_short}} für mich zur ersten Wahl.

Sprachkompetenz und interkulturelle Stärken
Meine Deutschkenntnisse habe ich durch das {{german_cert}} ({{german_level}}) nachgewiesen. Ich spreche außerdem Englisch auf {{english_level}}. Als jemand, der bereits in einem multikulturellen Arbeitsumfeld tätig war und sich intensiv mit deutscher Sprache und Kultur auseinandergesetzt hat, bin ich bestens auf ein Studium in Deutschland vorbereitet.

Langfristige Perspektive
Mein Ziel ist es, nach dem Masterabschluss in der Forschung und Entwicklung eines {{target_company_type}} tätig zu sein und zur Entwicklung {{impact_area}} beizutragen.

Ich bin zuversichtlich, dass ich mit meiner fachlichen Kompetenz, meiner Lernbereitschaft und meiner interkulturellen Erfahrung einen wertvollen Beitrag zu Ihrer Studierendengemeinschaft leisten kann.

Mit freundlichen Grüßen
{{first_name}} {{last_name}}
TXT,
        ];

        // ════════════════════════════════════════════════════════════════════════
        // 3) EMPFEHLUNGSSCHREIBEN — 3 variant
        // ════════════════════════════════════════════════════════════════════════
        $templates[] = [
            'doc_type' => 'reference',
            'language' => 'de',
            'name'     => 'Lise Öğretmeni',
            'is_default' => true,
            'body'     => <<<'TXT'
{{high_school_name}}
{{high_school_address}}
Tel: {{school_phone}}
E-Mail: {{school_email}}

An die
{{university_name}}
Studierendensekretariat
{{university_address}}

{{city}}, {{date}}

Empfehlungsschreiben für {{first_name}} {{last_name}}

Sehr geehrte Damen und Herren,

ich empfehle Ihnen {{salutation}} {{first_name}} {{last_name}} als Bewerber(in) für das {{target_program}}-Studium an Ihrer renommierten Universität mit größter Überzeugung.

Ich unterrichte seit {{teaching_years}} Jahren {{teacher_subjects}} an der {{high_school_name}} und hatte das Vergnügen, {{salutation}} {{last_name}} während {{teaching_period}} zu begleiten. In diesen Jahren konnte ich ihn/sie als außergewöhnlich begabten/begabte und engagierten/engagierte Schüler(in) kennenlernen.

Fachliche Leistungen
{{salutation}} {{last_name}} zählte in meinen Fächern stets zu den leistungsstärksten Schüler(innen) seines/ihres Jahrgangs. Sein/Ihr analytisches Denkvermögen und seine/ihre Fähigkeit, komplexe Zusammenhänge schnell zu erfassen, sind bemerkenswert. Im Abitur erzielte er/sie in {{best_subject}} die Bestnote ({{best_grade}}/100) und schloss die Schule mit einem Gesamtdurchschnitt von {{overall_grade}}/100 ab.

Persönlichkeit und Arbeitsweise
Besonders hervorzuheben ist {{pronoun_his_her}} Disziplin und {{pronoun_his_her}} Fähigkeit zur Selbstorganisation. Er/Sie geht Probleme systematisch an und gibt nicht auf, bis er/sie eine zufriedenstellende Lösung gefunden hat. Darüber hinaus ist er/sie ein(e) teamfähige(r) Schüler(in), der/die seinen/ihren Mitschüler(inne)n gerne hilft und zum positiven Klima in der Klasse beiträgt.

Sprachliche Kompetenz
{{salutation}} {{last_name}} hat eigenverantwortlich Deutsch bis zum Niveau {{german_level}} erlernt, was {{pronoun_his_her}} Eigeninitiative und Zielstrebigkeit eindrucksvoll unter Beweis stellt.

Ich bin davon überzeugt, dass {{salutation}} {{last_name}} die akademischen Anforderungen Ihres Studiengangs mit Auszeichnung erfüllen wird. Ich empfehle ihn/sie ohne jegliche Vorbehalte.

Für Rückfragen stehe ich Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen

[Unterschrift]

{{teacher_name}}
{{teacher_title}}
{{high_school_name}}, {{high_school_city}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'reference',
            'language' => 'de',
            'name'     => 'Üniversite Hocası',
            'is_default' => false,
            'body'     => <<<'TXT'
{{previous_university}}
Fakultät für {{faculty_name}}
{{university_address}}
Tel: {{university_phone}}
E-Mail: {{professor_email}}

An die
{{target_university}}
Immatrikulationsbüro
{{target_address}}

{{city}}, {{date}}

Empfehlungsschreiben für {{first_name}} {{last_name}}

Sehr geehrte Damen und Herren,

es ist mir eine besondere Freude, {{salutation}} {{first_name}} {{last_name}} für das {{target_program}}-Studium an Ihrer Universität zu empfehlen.

Ich bin Professor(in) für {{professor_chair}} an der {{previous_university}} und habe {{salutation}} {{last_name}} in zwei Lehrveranstaltungen unterrichtet: "{{course1}}" sowie "{{course2}}". In beiden Kursen gehörte er/sie zu den herausragenden Studierenden seines/ihres Jahrgangs.

Akademische Leistungen
{{salutation}} {{last_name}} erzielte in meinen Kursen jeweils die Bestnote (AA entspricht {{grade_system}}). Er/Sie verfügt über ein ausgeprägtes analytisches Denkvermögen und ist in der Lage, theoretische Konzepte auf reale Situationen anzuwenden. Seine/Ihre Seminararbeit zum Thema "{{seminar_topic}}" war von wissenschaftlicher Reife und überzeugte durch sorgfältige Quellenarbeit.

Engagement und Charakter
{{salutation}} {{last_name}} zeichnet sich durch außerordentliche Neugier und Einsatzbereitschaft aus. Er/Sie stellte regelmäßig tiefgründige Fragen im Unterricht und beteiligte sich konstruktiv an Gruppenarbeiten. Auch außerhalb des Unterrichts zeigte er/sie Eigeninitiative: {{extra_activity}}.

Ich bin fest davon überzeugt, dass {{salutation}} {{last_name}} die Herausforderungen eines Studiums in Deutschland mit Bravour meistern und Ihrer Universität eine wertvolle Bereicherung sein wird.

Für weitere Auskünfte stehe ich gerne per E-Mail zur Verfügung.

Mit freundlichen Grüßen

[Unterschrift]

Prof. Dr. {{professor_name}}
Lehrstuhlinhaber(in) für {{professor_chair}}
{{previous_university}}, {{university_city}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'reference',
            'language' => 'de',
            'name'     => 'Staj/İşveren',
            'is_default' => false,
            'body'     => <<<'TXT'
{{company_name}}
{{company_address}}
Tel: {{company_phone}}
E-Mail: {{hr_email}}

An die
{{target_university}}
Dekanat der Fakultät für {{faculty_name}}
{{target_address}}

{{city}}, {{date}}

Empfehlungsschreiben für {{first_name}} {{last_name}}

Sehr geehrte Damen und Herren,

ich empfehle {{salutation}} {{first_name}} {{last_name}} für sein/ihr geplantes {{study_level}} im Fach {{target_program}} an Ihrer Universität hiermit ausdrücklich und ohne jeglichen Vorbehalt.

{{salutation}} {{last_name}} war von {{work_start}} bis {{work_end}} als {{work_role}} in unserem Unternehmen tätig. In dieser Zeit konnte ich ihn/sie als äußerst zuverlässigen/zuverlässige, lernbegierigen/lernbegierige und fachlich versierten/versierte jungen/junge Mitarbeiter(in) kennenlernen.

Fachliche Kompetenz
{{salutation}} {{last_name}} übernahm von Beginn an eigenverantwortlich Aufgaben in {{work_area1}}, der Durchführung {{work_area2}} sowie der {{work_area3}}. Er/Sie arbeitete präzise, sorgfältig und hielt stets die geltenden Sicherheitsvorschriften ein. Besonders positiv fiel auf, dass er/sie bei unerwarteten Problemen eigenständig nach Lösungen suchte und dabei kreative und fundierte Ansätze einbrachte.

Persönliche Stärken
{{salutation}} {{last_name}} ist ein(e) ausgeprägte(r) Teamplayer mit hoher sozialer Kompetenz. Er/Sie kommuniziert klar und respektvoll und passte sich schnell in unser interdisziplinäres Team ein. Trotz seines/ihres jungen Alters zeigte er/sie eine bemerkenswerte Reife und Professionalität.

Fazit
Wir bedauern sehr, dass {{salutation}} {{last_name}} unser Unternehmen verlassen hat, um sein/ihr Studium fortzusetzen. Sein/Ihr Ziel, einen {{degree_type}} an der {{target_university}} zu erwerben, spricht für seinen/ihren hohen Ehrgeiz. Ich bin überzeugt, dass er/sie auch im akademischen Umfeld hervorragende Leistungen erbringen wird.

Für Rückfragen stehe ich jederzeit zur Verfügung.

Mit freundlichen Grüßen

[Unterschrift]

{{supervisor_name}}
{{supervisor_title}}
{{company_name}}, {{company_city}}
TXT,
        ];

        // ════════════════════════════════════════════════════════════════════════
        // 4) ANSCHREIBEN — 3 variant
        // ════════════════════════════════════════════════════════════════════════
        $templates[] = [
            'doc_type' => 'cover_letter',
            'language' => 'de',
            'name'     => 'Üniversite Başvurusu',
            'is_default' => true,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

{{university_name}}
Studierendensekretariat
{{university_address}}

{{city}}, {{date}}

Betreff: Bewerbung um einen Studienplatz — {{target_program}} B.Sc., Bewerbernummer: {{application_number}}

Sehr geehrte Damen und Herren,

hiermit bewerbe ich mich um einen Studienplatz im Bachelorstudiengang {{target_program}} (B.Sc.) an der {{university_name}} zum Wintersemester {{target_semester}}.

Ich bin türkische(r) Staatsbürger(in), habe mein Abitur (Lise Diploması) an der {{high_school_name}} in {{high_school_city}} mit der Note {{high_school_grade}}/100 abgeschlossen und verfüge über ein {{german_level}}-Sprachzertifikat in Deutsch ({{german_cert}}).

Anbei sende ich Ihnen folgende Unterlagen:
- Ausgefülltes Bewerbungsformular
- Beglaubigte Kopie des Abiturzeugnisses (Original + deutsche Übersetzung)
- Sprachnachweis ({{german_cert_detail}})
- Tabellarischer Lebenslauf
- Motivationsschreiben
- Lichtbild

Falls Sie weitere Unterlagen benötigen oder Fragen haben, stehe ich Ihnen jederzeit zur Verfügung.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'cover_letter',
            'language' => 'de',
            'name'     => 'Studienkolleg Başvurusu',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

Studienkolleg bei der {{university_name}}
{{studienkolleg_address}}

{{city}}, {{date}}

Betreff: Bewerbung für das Studienkolleg — {{kurs_type}} ({{kurs_description}})

Sehr geehrte Damen und Herren,

ich bewerbe mich hiermit für einen Platz im Studienkolleg an der {{university_name}}, {{kurs_type}}, zum {{target_semester}}.

Nach meinem Abitur in der Türkei und der Vorbereitung auf das Hochschulstudium in Deutschland möchte ich durch das Studienkolleg die notwendige fachliche und sprachliche Grundlage für ein anschließendes {{target_study_field}}-Studium erwerben.

Ich habe das {{german_cert}} mit {{german_level}} in allen Teilbereichen abgelegt und bin daher sprachlich auf die Anforderungen des Studienkollegs vorbereitet.

Folgende Unterlagen liegen diesem Schreiben bei:
- Beglaubigte Kopie des Abiturzeugnisses mit beglaubigter Übersetzung
- Sprachzertifikat ({{german_cert}}, {{german_level}})
- Lebenslauf
- Passfoto
- Ausgefülltes Bewerbungsformular

Ich freue mich darauf, meine Ausbildung in Deutschland fortzusetzen, und danke Ihnen herzlich für die Prüfung meiner Bewerbung.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'cover_letter',
            'language' => 'de',
            'name'     => 'Vize Başvurusu (Botschaft)',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}
Geburtsdatum: {{birth_date}}

Deutsche Botschaft {{city}}
Konsularabteilung
{{embassy_address}}

{{city}}, {{date}}

Betreff: Antrag auf Erteilung eines Visums zu Studienzwecken (§ 16b AufenthG)

Sehr geehrte Damen und Herren,

ich bitte Sie höflich um die Erteilung eines nationalen Visums (D-Visum) zu Studienzwecken. Ich wurde von der {{university_name}} für den {{target_program}} zum Wintersemester {{target_semester}} zugelassen.

Zu meiner Person: Ich bin türkische(r) Staatsbürger(in), geboren am {{birth_date}} in {{birth_place}}, und habe meinen {{previous_degree}} in {{previous_field}} an der {{previous_university}} erworben. Ich verfüge über ausreichende Deutschkenntnisse ({{german_cert}} / {{german_level}}) sowie über ein Sperrkonto bei der {{blocked_account_bank}} mit einem Guthaben von mindestens 11.208 Euro für das erste Jahr.

Folgende Unterlagen füge ich diesem Schreiben bei:
- Ausgefülltes Visumantragsformular
- Gültiger Reisepass (Kopie und Original)
- Zulassungsbescheid der {{university_name}}
- Nachweis über Sprachkenntnisse ({{german_cert_detail}})
- Nachweis über ein Sperrkonto (Kontoauszug)
- Krankenversicherungsnachweis
- Motivationsschreiben
- Beglaubigte Kopie des {{degree_type}}-Zeugnisses mit Übersetzung
- Lichtbilder (biometrisch)

Für Rückfragen stehe ich Ihnen unter der oben genannten E-Mail-Adresse und Telefonnummer jederzeit zur Verfügung.

Ich danke Ihnen für die Bearbeitung meines Antrags.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        // ════════════════════════════════════════════════════════════════════════
        // 5) SPERRKONTO-ANTRAG — 3 variant
        // ════════════════════════════════════════════════════════════════════════
        $templates[] = [
            'doc_type' => 'sperrkonto',
            'language' => 'de',
            'name'     => 'Fintiba/Coracle E-posta',
            'is_default' => true,
            'body'     => <<<'TXT'
Von: {{email}}
An: {{provider_email}}
Betreff: Antrag auf Eröffnung eines Sperrkontos — {{first_name}} {{last_name}}

Sehr geehrte Damen und Herren,

ich möchte hiermit ein Sperrkonto für Studienzwecke in Deutschland eröffnen.

Meine Angaben:
Name: {{first_name}} {{last_name}}
Geburtsdatum: {{birth_date}}
Staatsangehörigkeit: Türkisch
Reisepassnummer: {{passport_number}}
Wohnadresse (Türkei): {{address}}
E-Mail: {{email}}
Telefon: {{phone}}
Zieluniversität: {{university_name}}
Geplanter Studienbeginn: WS {{target_semester}}
Gewünschter Einzahlungsbetrag: 11.208 Euro (mindestens)

Ich bitte Sie, mir die notwendigen Schritte zur Kontoeröffnung sowie die Bankverbindung für die Überweisung mitzuteilen.

Folgende Dokumente lege ich auf Anfrage gerne bei:
- Kopie des Reisepasses
- Zulassungsbescheid der Universität
- Nachweis über Sprachkenntnisse

Für Rückfragen stehe ich jederzeit zur Verfügung.

Mit freundlichen Grüßen
{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'sperrkonto',
            'language' => 'de',
            'name'     => 'Deutsche Bank Resmi',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

Deutsche Bank AG
Filiale {{branch_name}}
{{branch_address}}

{{city}}, {{date}}

Betreff: Antrag auf Eröffnung eines Sperrkontos für ausländische Studierende (§ 16b AufenthG)

Sehr geehrte Damen und Herren,

ich bewerbe mich um die Eröffnung eines Sperrkontos (blocked account) zur Beantragung eines deutschen Studentenvisums. Ich plane, ab dem Wintersemester {{target_semester}} an der {{university_name}} das {{target_program}} aufzunehmen.

Persönliche Daten:
- Name: {{first_name}} {{last_name}}
- Geburtsdatum: {{birth_date}}
- Geburtsort: {{birth_place}}
- Staatsangehörigkeit: Türkisch
- Reisepassnummer: {{passport_number}}
- Aktuelle Adresse: {{address}}

Angaben zum Studium:
- Universität: {{university_name}}
- Studiengang: {{target_program}}
- Studienbeginn: WS {{target_semester}}

Einzahlungsbetrag: Ich beabsichtige, den Mindestbetrag von 11.208 Euro auf das Sperrkonto einzuzahlen, der einem monatlichen Betrag von 934 Euro für 12 Monate entspricht.

Ich bitte um Übersendung der erforderlichen Formulare und Informationen zur Kontoeröffnung sowie zur Einzahlung aus dem Ausland. Falls ein persönliches Gespräch oder eine Identifikation erforderlich ist, stehe ich für einen Termin zur Verfügung.

Folgende Unterlagen lege ich diesem Schreiben bei:
- Beglaubigte Kopie des Reisepasses
- Zulassungsbescheid der Universität
- Meldebescheinigung (türkisch, mit Übersetzung)

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'sperrkonto',
            'language' => 'de',
            'name'     => 'Coracle/Expatrio Begleitschreiben',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}} | {{phone}}

{{provider_company}}
Kundenservice Sperrkonto
{{provider_address}}

{{city}}, {{date}}

Betreff: Eröffnung eines Sperrkontos — Antrag und Begleitdokumente

Sehr geehrte Damen und Herren,

anbei sende ich Ihnen meine Unterlagen zur Eröffnung eines Sperrkontos für Studienzwecke in Deutschland.

Übersicht meiner Angaben:
Vollständiger Name: {{first_name}} {{last_name}}
Geburtsdatum: {{birth_date}}
Nationalität: Türkisch
Reisepassnummer: {{passport_number}}
Adresse in der Türkei: {{address}}
E-Mail-Adresse: {{email}}
Telefonnummer: {{phone}}
Zieluniversität: {{university_name}}
Studiengang: {{target_program}}
Geplanter Studienbeginn: WS {{target_semester}}
Einzahlungsbetrag: 11.208 Euro

Beigefügte Dokumente:
1. Kopie des gültigen Reisepasses (Seiten mit Foto und Personalien)
2. Zulassungsbescheid der {{university_name}}
3. Ausgefülltes Antragsformular (falls zugesandt)
4. Nachweis über Heimatadresse (Nüfus kayıt örneği + Übersetzung)

Ich bitte Sie, nach Eingang der Unterlagen die Kontoeröffnung zu veranlassen und mir die Überweisungsdaten für die Einzahlung mitzuteilen. Nach erfolgter Einzahlung bitte ich um Ausstellung der offiziellen Sperrkontobestätigung, die ich für meinen Visumantrag bei der deutschen Botschaft benötige.

Bei Rückfragen erreichen Sie mich jederzeit per E-Mail oder Telefon.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        // ════════════════════════════════════════════════════════════════════════
        // 6) WOHNHEIMSANTRAG — 3 variant
        // ════════════════════════════════════════════════════════════════════════
        $templates[] = [
            'doc_type' => 'housing',
            'language' => 'de',
            'name'     => 'Standart Başvuru',
            'is_default' => true,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}}
{{phone}}

Studentenwerk {{city_name}}
Wohnheimanmeldung
{{studentenwerk_address}}

{{city}}, {{date}}

Betreff: Antrag auf einen Wohnheimplatz — Erstsemester / internationaler Studierender

Sehr geehrte Damen und Herren,

ich bewerbe mich hiermit um einen Wohnheimplatz im Studentenwerk {{city_name}}. Ich bin ein(e) internationale(r) Studierende(r) aus der Türkei und werde zum Wintersemester {{target_semester}} mein {{study_level}} im Fach {{target_program}} an der {{university_name}} aufnehmen.

Angaben zu meiner Person:
Name: {{first_name}} {{last_name}}
Geburtsdatum: {{birth_date}}
Staatsangehörigkeit: Türkisch
Matrikelnummer: {{matrikulation_number}}
Universität: {{university_name}}
Studiengang: {{target_program}}
Studienbeginn: WS {{target_semester}}
Gewünschter Einzugstermin: {{move_in_date}}

Begründung meines Antrags:
Als internationale(r) Studierende(r), die/der zum ersten Mal nach Deutschland kommt, ist ein sicherer und erschwinglicher Wohnheimplatz für mich von besonderer Bedeutung. Ich habe keine verwandten oder bekannten Personen in {{city_name}}, die mir bei der Wohnungssuche helfen könnten. Die Unterkunft in einem Studentenwohnheim würde mir einen reibungslosen Start in das Studium und die Integration in die Universitätsgemeinschaft ermöglichen.

Ich bin flexibel bezüglich der Zimmergröße (Einzelzimmer oder WG-Zimmer) und bin bereit, in verschiedenen Wohnheimstandorten in {{city_name}} zu wohnen.

Beigefügte Unterlagen:
- Zulassungsbescheid der {{university_name}}
- Kopie des Reisepasses
- Immatrikulationsbescheinigung (sobald erhältlich)

Ich danke Ihnen herzlich für die Bearbeitung meines Antrags und freue mich auf eine positive Rückmeldung.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'housing',
            'language' => 'de',
            'name'     => 'Sosyal Öncelik Talebi',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}}
{{phone}}

Studentenwerk {{city_name}}
Abteilung Wohnen
{{studentenwerk_address}}

{{city}}, {{date}}

Betreff: Antrag auf bevorzugte Vergabe eines Wohnheimplatzes — Internationale Studierende aus einkommensschwachem Hintergrund

Sehr geehrte Damen und Herren,

ich bewerbe mich um einen Wohnheimplatz im Studentenwerk {{city_name}} und bitte um bevorzugte Berücksichtigung meines Antrags aufgrund meiner besonderen persönlichen Situation.

Ich bin türkische(r) Staatsbürger(in) und werde zum Wintersemester {{target_semester}} das {{study_level}} {{target_program}} an der {{university_name}} beginnen. Dies ist mein erster Aufenthalt in Deutschland.

Begründung für bevorzugte Berücksichtigung:
Meine Familie gehört in der Türkei einer einkommensschwachen Schicht an. Ich finanziere mein Studium größtenteils durch {{scholarship_or_source}} sowie durch eigene Ersparnisse. Die Mietpreise auf dem freien Wohnungsmarkt in {{city_name}} übersteigen meine finanzielle Leistungsfähigkeit erheblich. Ein Platz in einem Studentenwohnheim würde mir ermöglichen, mein Studium ohne existenzielle finanzielle Sorgen aufzunehmen und erfolgreich abzuschließen.

Ich habe keine Kontakte in Deutschland, die mir vorübergehend Unterkunft bieten könnten. Ein Wohnheimplatz ist daher für mich keine Komfortoption, sondern eine notwendige Voraussetzung für die Aufnahme meines Studiums.

Gewünschte Zimmerkategorie: Kleinstes verfügbares Zimmer / Preisgünstigstes Angebot

Meine Daten:
- Name: {{first_name}} {{last_name}} | Geburtsdatum: {{birth_date}}
- Universität: {{university_name}} | Studiengang: {{target_program}}
- Matrikelnummer: {{matrikulation_number}}
- Einzugswunsch: {{move_in_date}}

Beigefügte Unterlagen:
- Zulassungsbescheid der Universität
- Stipendiennachweis ({{scholarship_name}})
- Kopie des Reisepasses
- Einkommensnachweise der Eltern (türkisch + Übersetzung, auf Anfrage)

Ich stehe für Rückfragen jederzeit zur Verfügung und danke Ihnen für Ihr Verständnis.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        $templates[] = [
            'doc_type' => 'housing',
            'language' => 'de',
            'name'     => 'Warteliste / Alternatif Sorgusu',
            'is_default' => false,
            'body'     => <<<'TXT'
{{first_name}} {{last_name}}
{{address}}
{{email}}
{{phone}}

Studentenwerk {{city_name}}
Wohnheimanmeldung
{{studentenwerk_address}}

{{city}}, {{date}}

Betreff: Wohnheimsantrag sowie Anfrage zur Wartelistensituation — WS {{target_semester}}

Sehr geehrte Damen und Herren,

ich bewerbe mich mit diesem Schreiben um einen Wohnheimplatz im Studentenwerk {{city_name}} und bitte gleichzeitig um Auskunft über die aktuelle Wartelistensituation sowie über mögliche Alternativen für internationale {{study_level}}studierende.

Zu meiner Person:
Name: {{first_name}} {{last_name}}
Geburtsdatum: {{birth_date}}
Staatsangehörigkeit: Türkisch
Universität: {{university_name}}
Studiengang: {{target_program}}
Studienbeginn: WS {{target_semester}}
Matrikelnummer: {{matrikulation_number}}
Gewünschter Einzug: {{move_in_date}}

Meine Situation:
Ich komme zum ersten Mal nach Deutschland und habe keine sozialen Kontakte in {{city_name}}, die mir bei der Unterkunftssuche behilflich sein könnten. Der private Wohnungsmarkt in {{city_name}} ist erfahrungsgemäß für Erstzuzügler aus dem Ausland schwer zugänglich. Ich bin daher auf die Unterstützung des Studentenwerks angewiesen.

Meine Anfragen:
1. Ist für das WS {{target_semester}} noch ein Wohnheimplatz verfügbar? Falls nicht: Wie kann ich mich auf die Warteliste setzen lassen?
2. Gibt es spezielle Wohnheimangebote für internationale {{study_level}}studierende?
3. Können Sie mir alternative Unterkunftsmöglichkeiten (z.B. Wohnungsvermittlungsportale, private Anbieter mit Kooperationsvertrag) empfehlen?

Zimmertyp: Ich bevorzuge ein Einzelzimmer in einer Wohngemeinschaft (WG), bin jedoch auch für andere Optionen offen.

Beigefügte Unterlagen:
- Zulassungsbescheid der {{university_name}}
- Kopie des Reisepasses
- Kurzlebenslauf

Ich danke Ihnen herzlich für Ihre Unterstützung und freue mich auf Ihre Rückmeldung.

Mit freundlichen Grüßen

{{first_name}} {{last_name}}
TXT,
        ];

        // ════════════════════════════════════════════════════════════════════════
        // INSERT
        // ════════════════════════════════════════════════════════════════════════
        $inserted = 0;
        $skipped = 0;
        foreach ($templates as $tpl) {
            $exists = DB::table('document_builder_templates')
                ->whereNull('company_id')
                ->where('doc_type', $tpl['doc_type'])
                ->where('language', $tpl['language'])
                ->where('name', $tpl['name'])
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            DB::table('document_builder_templates')->insert([
                'company_id'        => null,
                'doc_type'          => $tpl['doc_type'],
                'language'          => $tpl['language'],
                'name'              => $tpl['name'],
                'section_order'     => json_encode(['body'], JSON_UNESCAPED_UNICODE),
                'section_templates' => json_encode(['body' => $tpl['body']], JSON_UNESCAPED_UNICODE),
                'variables'         => null,
                'is_active'         => true,
                'is_default'        => $tpl['is_default'],
                'version'           => 1,
                'created_by'        => 'system_seeder',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $inserted++;
        }

        $this->command?->info("DocumentBuilderTemplatesSeeder: $inserted inserted, $skipped skipped (already exist).");
    }
}
