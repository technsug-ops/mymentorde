import React, { useState } from 'react';
import { User, FileText, GraduationCap, Code, Eye, Play, Download,
  Plus, Trash2, Check, ChevronRight, ChevronLeft, Info, X, Save,
  HelpCircle, Globe } from 'lucide-react';

// ─── Data Constants ────────────────────────────────────────────────────────────

const GERMAN_CV_FORMAT = {
  sections: ['Persönliche Daten','Schulbildung','Sprachkenntnisse','Computerkenntnisse','Interessen und Hobbys']
};

const HOBBIES_LIST = {
  tr: [
    { value: 'Bücher lesen', label: 'Kitap Okumak' },
    { value: 'Musik hören', label: 'Müzik Dinlemek' },
    { value: 'Filme schauen', label: 'Film İzlemek' },
    { value: 'Sport treiben', label: 'Spor Yapmak' },
    { value: 'Zeichnen', label: 'Resim Çizmek' },
    { value: 'Joggen', label: 'Koşu Yapmak' },
    { value: 'Schwimmen', label: 'Yüzme' },
    { value: 'Fahrrad fahren', label: 'Bisiklet Sürmek' },
    { value: 'Kochen', label: 'Yemek Pişirmek' },
    { value: 'Fotografieren', label: 'Fotoğrafçılık' },
    { value: 'Spiele spielen', label: 'Oyun Oynamak' },
    { value: 'Instrument spielen', label: 'Enstrüman Çalmak' },
    { value: 'Tanzen', label: 'Dans Etmek' },
    { value: 'Basteln', label: 'El İşi Yapmak' },
    { value: 'Origami', label: 'Origami' },
    { value: 'Programmieren', label: 'Programlama' },
    { value: 'Schreiben', label: 'Yazma' },
    { value: 'Wandern', label: 'Doğa Yürüyüşü' },
    { value: 'Puzzle lösen', label: 'Puzzle Çözmek' },
    { value: 'Sprachen lernen', label: 'Dil Öğrenmek' }
  ],
  en: [
    { value: 'Bücher lesen', label: 'Reading Books' },
    { value: 'Musik hören', label: 'Listening to Music' },
    { value: 'Filme schauen', label: 'Watching Movies' },
    { value: 'Sport treiben', label: 'Doing Sports' },
    { value: 'Zeichnen', label: 'Drawing' },
    { value: 'Joggen', label: 'Jogging' },
    { value: 'Schwimmen', label: 'Swimming' },
    { value: 'Fahrrad fahren', label: 'Cycling' },
    { value: 'Kochen', label: 'Cooking' },
    { value: 'Fotografieren', label: 'Photography' },
    { value: 'Spiele spielen', label: 'Playing Games' },
    { value: 'Instrument spielen', label: 'Playing Instrument' },
    { value: 'Tanzen', label: 'Dancing' },
    { value: 'Basteln', label: 'Crafting' },
    { value: 'Origami', label: 'Origami' },
    { value: 'Programmieren', label: 'Programming' },
    { value: 'Schreiben', label: 'Writing' },
    { value: 'Wandern', label: 'Hiking' },
    { value: 'Puzzle lösen', label: 'Solving Puzzles' },
    { value: 'Sprachen lernen', label: 'Learning Languages' }
  ],
  de: [
    { value: 'Bücher lesen', label: 'Bücher lesen' },
    { value: 'Musik hören', label: 'Musik hören' },
    { value: 'Filme schauen', label: 'Filme schauen' },
    { value: 'Sport treiben', label: 'Sport treiben' },
    { value: 'Zeichnen', label: 'Zeichnen' },
    { value: 'Joggen', label: 'Joggen' },
    { value: 'Schwimmen', label: 'Schwimmen' },
    { value: 'Fahrrad fahren', label: 'Fahrrad fahren' },
    { value: 'Kochen', label: 'Kochen' },
    { value: 'Fotografieren', label: 'Fotografieren' },
    { value: 'Spiele spielen', label: 'Spiele spielen' },
    { value: 'Instrument spielen', label: 'Instrument spielen' },
    { value: 'Tanzen', label: 'Tanzen' },
    { value: 'Basteln', label: 'Basteln' },
    { value: 'Origami', label: 'Origami' },
    { value: 'Programmieren', label: 'Programmieren' },
    { value: 'Schreiben', label: 'Schreiben' },
    { value: 'Wandern', label: 'Wandern' },
    { value: 'Puzzle lösen', label: 'Puzzle lösen' },
    { value: 'Sprachen lernen', label: 'Sprachen lernen' }
  ]
};

const SKILLS_LIST = {
  tr: [
    { value: 'Grundkenntnisse am Computer', label: 'Temel Bilgisayar Bilgisi' },
    { value: 'MS Office Kenntnisse (Word, Excel, PowerPoint)', label: 'MS Office Bilgisi' },
    { value: 'Internetrecherche', label: 'İnternet Araştırması' },
    { value: 'Teamfähigkeit', label: 'Takım Çalışması' },
    { value: 'Kommunikationsfähigkeit', label: 'İletişim Becerisi' },
    { value: 'Zuverlässigkeit', label: 'Güvenilirlik' },
    { value: 'Lernbereitschaft', label: 'Öğrenme İsteği' },
    { value: 'Pünktlichkeit', label: 'Dakiklik' },
    { value: 'Kreativität', label: 'Yaratıcılık' },
    { value: 'Verantwortungsbewusstsein', label: 'Sorumluluk Bilinci' },
    { value: 'Selbstständiges Arbeiten', label: 'Bağımsız Çalışabilme' },
    { value: 'Soziale Kompetenz', label: 'Sosyal Yetkinlik' }
  ],
  en: [
    { value: 'Grundkenntnisse am Computer', label: 'Basic Computer Knowledge' },
    { value: 'MS Office Kenntnisse (Word, Excel, PowerPoint)', label: 'MS Office Skills' },
    { value: 'Internetrecherche', label: 'Internet Research' },
    { value: 'Teamfähigkeit', label: 'Teamwork' },
    { value: 'Kommunikationsfähigkeit', label: 'Communication Skills' },
    { value: 'Zuverlässigkeit', label: 'Reliability' },
    { value: 'Lernbereitschaft', label: 'Willingness to Learn' },
    { value: 'Pünktlichkeit', label: 'Punctuality' },
    { value: 'Kreativität', label: 'Creativity' },
    { value: 'Verantwortungsbewusstsein', label: 'Sense of Responsibility' },
    { value: 'Selbstständiges Arbeiten', label: 'Independent Work' },
    { value: 'Soziale Kompetenz', label: 'Social Competence' }
  ],
  de: [
    { value: 'Grundkenntnisse am Computer', label: 'Grundkenntnisse am Computer' },
    { value: 'MS Office Kenntnisse (Word, Excel, PowerPoint)', label: 'MS Office Kenntnisse' },
    { value: 'Internetrecherche', label: 'Internetrecherche' },
    { value: 'Teamfähigkeit', label: 'Teamfähigkeit' },
    { value: 'Kommunikationsfähigkeit', label: 'Kommunikationsfähigkeit' },
    { value: 'Zuverlässigkeit', label: 'Zuverlässigkeit' },
    { value: 'Lernbereitschaft', label: 'Lernbereitschaft' },
    { value: 'Pünktlichkeit', label: 'Pünktlichkeit' },
    { value: 'Kreativität', label: 'Kreativität' },
    { value: 'Verantwortungsbewusstsein', label: 'Verantwortungsbewusstsein' },
    { value: 'Selbstständiges Arbeiten', label: 'Selbstständiges Arbeiten' },
    { value: 'Soziale Kompetenz', label: 'Soziale Kompetenz' }
  ]
};

const CV_INSTRUCTIONS = {
  tr: {
    title: 'CV Doldurma Yönergeleri',
    sections: {
      personalInfo: {
        title: '📋 Kişisel Bilgiler',
        items: [
          'Ad ve Soyadınızı tam olarak yazın (pasaport/kimlikte yazdığı gibi)',
          'Doğum tarihi formatı: GG.AA.YYYY (örn: 31.08.2006)',
          'Doğum yeri şehir adı olarak yazılmalı',
          'Medeni durum: Ledig (Bekar) veya Verheiratet (Evli)',
          'Uyruk: Türke, Deutsch, vb.',
          'Adres tam ve net olmalı - mahalle, sokak, bina no',
          'Email ve telefon aktif olmalı',
          'Fotoğraf profesyonel, pasaport fotoğrafı tarzında'
        ]
      },
      education: {
        title: '🎓 Eğitim Bilgileri (Schulbildung)',
        items: [
          'En son eğitimden başlayarak geriye doğru yazın',
          'Her eğitim için başlangıç ve bitiş tarihini AA.YYYY formatında yazın',
          'Okul adlarını tam yazın',
          'Eğer hala devam ediyorsa bitiş tarihi boş bırakılabilir',
          'Alınan diploma türünü belirtin (Diplom, Abschluss)',
          'Şehir ve ülke bilgisi ekleyin'
        ]
      },
      languages: {
        title: '🌍 Dil Becerileri (Sprachkenntnisse)',
        items: [
          'Ana dilinizi "Muttersprache" olarak belirtin',
          'Diğer diller için seviye: A1, A2, B1, B2, C1, C2',
          'Veya: Anfänger, Mittelstufe, Fortgeschritten',
          'Sertifika varsa belirtin (TELC, Goethe, vb.)'
        ]
      },
      computer: {
        title: '💻 Bilgisayar Becerileri',
        items: [
          'MS Office programlarını (Word, Excel, PowerPoint) belirtin',
          'Programlama dilleri varsa yazın',
          'İnternet araştırma becerilerini ekleyin',
          'Özel yazılımlar varsa belirtin'
        ]
      },
      hobbies: {
        title: '🎨 İlgi Alanları ve Hobiler',
        items: [
          'Kısa ve öz yazın',
          'Film izlemek, spor yapmak gibi genel ifadeler kullanın',
          'Çok fazla detaya girmeyin (2-4 hobi yeterli)'
        ]
      }
    }
  },
  en: {
    title: 'CV Filling Instructions',
    sections: {
      personalInfo: { title: '📋 Personal Information', items: ['Write your full name as on passport/ID','Date of birth: DD.MM.YYYY (e.g: 31.08.2006)','Place of birth: city name','Marital status: Ledig (Single) or Verheiratet (Married)','Nationality: Türke, Deutsch, etc.','Complete address - district, street, building no','Active email and phone','Professional passport-style photo'] },
      education: { title: '🎓 Education (Schulbildung)', items: ['Start from most recent backwards','Start/end dates in MM.YYYY format','Full school names','End date can be empty if ongoing','Specify diploma type','Include city and country'] },
      languages: { title: '🌍 Language Skills', items: ['Native language as "Muttersprache"','Levels: A1, A2, B1, B2, C1, C2','Or: Anfänger, Mittelstufe, Fortgeschritten','Mention certificates (TELC, Goethe, etc.)'] },
      computer: { title: '💻 Computer Skills', items: ['Specify MS Office (Word, Excel, PowerPoint)','List programming languages','Include internet research','Mention special software'] },
      hobbies: { title: '🎨 Hobbies', items: ['Keep short and concise','General expressions like watching movies, sports','2-4 hobbies sufficient'] }
    }
  },
  de: {
    title: 'Anleitung zum Ausfüllen des Lebenslaufs',
    sections: {
      personalInfo: { title: '📋 Persönliche Daten', items: ['Vollständigen Namen wie im Reisepass','Geburtsdatum: TT.MM.JJJJ','Geburtsort: Stadtname','Familienstand: Ledig oder Verheiratet','Staatsangehörigkeit: Türke, Deutsch','Vollständige Adresse','Aktive E-Mail und Telefon','Professionelles Passfoto'] },
      education: { title: '🎓 Schulbildung', items: ['Aktuelle Ausbildung zuerst','Start/Enddatum im Format MM.JJJJ','Vollständige Schulnamen','Enddatum leer wenn laufend','Abschlusstyp angeben','Stadt und Land hinzufügen'] },
      languages: { title: '🌍 Sprachkenntnisse', items: ['Muttersprache als "Muttersprache"','Niveau: A1, A2, B1, B2, C1, C2','Oder: Anfänger, Mittelstufe, Fortgeschritten','Zertifikate erwähnen (TELC, Goethe)'] },
      computer: { title: '💻 Computerkenntnisse', items: ['MS Office Programme angeben','Programmiersprachen auflisten','Internetrecherche hinzufügen','Spezielle Software erwähnen'] },
      hobbies: { title: '🎨 Interessen und Hobbys', items: ['Kurz und prägnant','Allgemeine Ausdrücke: Filme schauen, Sport','2-4 Hobbys ausreichend'] }
    }
  }
};

const VIDEO_TUTORIALS = {
  tr: [
    { id:1, title:'CV Nedir ve Neden Önemlidir?', duration:'5:30', thumbnail:'🎯', description:'Almanya\'da CV\'nin önemi ve genel formatı hakkında', url:'#' },
    { id:2, title:'Kişisel Bilgileri Doğru Doldurma', duration:'8:15', thumbnail:'📋', description:'Kişisel bilgiler bölümünü adım adım doldurma', url:'#' },
    { id:3, title:'Eğitim Geçmişi Nasıl Yazılır?', duration:'6:45', thumbnail:'🎓', description:'Schulbildung bölümünü eksiksiz doldurma', url:'#' },
    { id:4, title:'Dil ve Bilgisayar Becerileri', duration:'7:20', thumbnail:'💻', description:'Yeteneklerinizi etkili bir şekilde sunma', url:'#' },
    { id:5, title:'Profesyonel Fotoğraf İpuçları', duration:'4:10', thumbnail:'📸', description:'CV için uygun fotoğraf hazırlama', url:'#' }
  ],
  en: [
    { id:1, title:'What is a CV and Why is it Important?', duration:'5:30', thumbnail:'🎯', description:'Importance of CV in Germany and general format', url:'#' },
    { id:2, title:'Filling Personal Information Correctly', duration:'8:15', thumbnail:'📋', description:'Step by step personal information section', url:'#' },
    { id:3, title:'How to Write Education History?', duration:'6:45', thumbnail:'🎓', description:'Completing Schulbildung section thoroughly', url:'#' },
    { id:4, title:'Language and Computer Skills', duration:'7:20', thumbnail:'💻', description:'Presenting your skills effectively', url:'#' },
    { id:5, title:'Professional Photo Tips', duration:'4:10', thumbnail:'📸', description:'Preparing suitable photo for CV', url:'#' }
  ],
  de: [
    { id:1, title:'Was ist ein Lebenslauf?', duration:'5:30', thumbnail:'🎯', description:'Bedeutung des Lebenslaufs in Deutschland', url:'#' },
    { id:2, title:'Persönliche Daten ausfüllen', duration:'8:15', thumbnail:'📋', description:'Schritt für Schritt persönliche Daten', url:'#' },
    { id:3, title:'Bildungsgeschichte schreiben', duration:'6:45', thumbnail:'🎓', description:'Schulbildung Abschnitt vollständig', url:'#' },
    { id:4, title:'Sprach- und Computerkenntnisse', duration:'7:20', thumbnail:'💻', description:'Fähigkeiten effektiv präsentieren', url:'#' },
    { id:5, title:'Professionelle Foto-Tipps', duration:'4:10', thumbnail:'📸', description:'Geeignetes Foto für Lebenslauf', url:'#' }
  ]
};

// ─── Helpers ───────────────────────────────────────────────────────────────────

const normalizeDateForCv = (value) => {
  if (!value) return '';
  const str = String(value).trim();
  if (!str) return '';
  if (/^\d{2}\.\d{2}\.\d{4}$/.test(str)) return str;
  const match = str.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (match) return `${match[3]}.${match[2]}.${match[1]}`;
  return str;
};

const buildInitialCvData = (bridge = {}) => {
  const p = bridge.prefill || {};
  const fullName = [p.first_name || '', p.last_name || ''].join(' ').trim();
  const addressParts = [p.address_line, p.district, p.city, p.country, p.postal_code].filter(Boolean);
  return {
    personalInfo: {
      name: fullName,
      geburtsdatum: normalizeDateForCv(p.birth_date),
      geburtsort: p.birth_place || '',
      familienstand: p.marital_status_label || p.marital_status || '',
      staatsangehorigkeit: p.nationality || '',
      anschrift: addressParts.join(', '),
      email: p.email || '',
      telefon: p.phone || '',
      photo: null
    },
    education: [],
    languages: [],
    selectedSkills: [],
    customSkills: p.cv_computer_skills_tr || p.cv_skills_tr || '',
    selectedHobbies: [],
    customHobbies: p.cv_hobbies_tr || ''
  };
};

// ─── Style helpers ─────────────────────────────────────────────────────────────

const S = {
  card: { background: 'var(--u-card)', border: '1px solid var(--u-line)', borderRadius: 10, padding: 20, marginBottom: 16 },
  label: { display: 'block', fontSize: 11, fontWeight: 700, color: 'var(--u-muted)', marginBottom: 6, textTransform: 'uppercase', letterSpacing: '0.06em' },
  input: { border: '1px solid var(--u-line)', borderRadius: 6, padding: '8px 12px', width: '100%', fontSize: 14, color: 'var(--u-text)', background: 'transparent', outline: 'none', boxSizing: 'border-box' },
  btnPrimary: { background: '#7c3aed', color: '#fff', border: 'none', borderRadius: 8, padding: '10px 20px', fontSize: 14, fontWeight: 600, cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 6 },
  btnSecondary: { background: 'var(--u-line)', color: 'var(--u-text)', border: 'none', borderRadius: 8, padding: '10px 20px', fontSize: 14, fontWeight: 500, cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 6 },
  btnDanger: { background: '#fee2e2', color: '#dc2626', border: 'none', borderRadius: 6, padding: '6px 12px', fontSize: 13, cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 4 },
  btnGhost: { background: 'transparent', color: '#7c3aed', border: '1px dashed #7c3aed', borderRadius: 8, padding: '8px 16px', fontSize: 13, cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 6 },
  row2: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14 },
  row3: { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 14 },
  sectionTitle: { fontSize: 15, fontWeight: 700, color: 'var(--u-text)', marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 },
};

// Field component
const Field = ({ label, required, children, style }) => (
  <div style={style}>
    <label style={S.label}>{label}{required && <span style={{ color: '#dc2626' }}> *</span>}</label>
    {children}
  </div>
);

// ─── Step definitions ──────────────────────────────────────────────────────────

const STEPS = [
  { id: 1, icon: <User size={14} />, labelTr: 'Kişisel Bilgiler', labelEn: 'Personal Data', labelDe: 'Persönliche Daten' },
  { id: 2, icon: <GraduationCap size={14} />, labelTr: 'Eğitim', labelEn: 'Education', labelDe: 'Schulbildung' },
  { id: 3, icon: <Code size={14} />, labelTr: 'Beceriler', labelEn: 'Skills', labelDe: 'Fähigkeiten' },
  { id: 4, icon: <Eye size={14} />, labelTr: 'Önizleme', labelEn: 'Preview', labelDe: 'Vorschau' },
];

const VIDEO_STEP = { id: 0, icon: <Play size={14} />, labelTr: 'Video Rehber', labelEn: 'Video Guide', labelDe: 'Video-Guide' };

const stepLabel = (step, lang) =>
  lang === 'en' ? step.labelEn : lang === 'de' ? step.labelDe : step.labelTr;

// ─── Main Component ────────────────────────────────────────────────────────────

const CVBuilderModule = ({ bridge = {} }) => {
  const defaultLang = ['tr', 'en', 'de'].includes(bridge.defaultLanguage) ? bridge.defaultLanguage : 'tr';
  const [lang, setLang] = useState(defaultLang);
  const [showLangPicker, setShowLangPicker] = useState(Boolean(bridge.allowLanguageSelector));
  const [currentStep, setCurrentStep] = useState(1); // start at Personal Data
  const [cvData, setCvData] = useState(() => buildInitialCvData(bridge));
  const [showInstructions, setShowInstructions] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [saveState, setSaveState] = useState({ status: 'idle', message: '' });

  if (showLangPicker) {
    return <LanguageSelector onSelect={(l) => { setLang(l); setShowLangPicker(false); }} />;
  }

  // Handlers
  const handlePersonalInfoChange = (field, value) =>
    setCvData(prev => ({ ...prev, personalInfo: { ...prev.personalInfo, [field]: value } }));

  const addEducation = () =>
    setCvData(prev => ({ ...prev, education: [...prev.education, { id: Date.now(), startDate: '', endDate: '', institution: '', city: '', country: '', degree: '' }] }));

  const updateEducation = (id, field, value) =>
    setCvData(prev => ({ ...prev, education: prev.education.map(e => e.id === id ? { ...e, [field]: value } : e) }));

  const removeEducation = (id) =>
    setCvData(prev => ({ ...prev, education: prev.education.filter(e => e.id !== id) }));

  const addLanguage = () =>
    setCvData(prev => ({ ...prev, languages: [...prev.languages, { id: Date.now(), language: '', level: '' }] }));

  const updateLanguage = (id, field, value) =>
    setCvData(prev => ({ ...prev, languages: prev.languages.map(l => l.id === id ? { ...l, [field]: value } : l) }));

  const removeLanguage = (id) =>
    setCvData(prev => ({ ...prev, languages: prev.languages.filter(l => l.id !== id) }));

  const toggleSkill = (val) =>
    setCvData(prev => ({
      ...prev,
      selectedSkills: prev.selectedSkills.includes(val)
        ? prev.selectedSkills.filter(s => s !== val)
        : [...prev.selectedSkills, val]
    }));

  const toggleHobby = (val) =>
    setCvData(prev => ({
      ...prev,
      selectedHobbies: prev.selectedHobbies.includes(val)
        ? prev.selectedHobbies.filter(h => h !== val)
        : [...prev.selectedHobbies, val]
    }));

  const saveToLaravel = async () => {
    if (!bridge.generateUrl || !bridge.csrfToken) {
      setSaveState({ status: 'error', message: 'Laravel bridge ayarı eksik.' });
      return;
    }
    const educationText = (cvData.education || [])
      .filter(e => e && (e.institution || e.city || e.degree))
      .map(e => `${e.startDate||'-'} - ${e.endDate||'-'} | ${e.institution||'-'} | ${e.city||'-'} / ${e.country||'-'} | ${e.degree||'-'}`)
      .join('\n');
    const languageText = (cvData.languages || [])
      .filter(l => l && (l.language || l.level))
      .map(l => `${l.language||'-'}: ${l.level||'-'}`)
      .join(', ');
    const skillsCombined = [...(cvData.selectedSkills || [])];
    if (cvData.customSkills) skillsCombined.push(...String(cvData.customSkills).split('\n').map(v => v.trim()).filter(Boolean));
    const hobbiesCombined = [...(cvData.selectedHobbies || [])];
    if (cvData.customHobbies) hobbiesCombined.push(...String(cvData.customHobbies).split('\n').map(v => v.trim()).filter(Boolean));

    const payload = {
      document_type: 'cv', language: 'de',
      output_format: bridge.outputFormat || 'docx',
      ai_mode: bridge.aiMode || 'template',
      title: `Lebenslauf - ${cvData.personalInfo?.name || 'Student'}`,
      notes: bridge.saveNote || 'React CV Builder',
      cv_profile_summary_tr: [
        cvData.personalInfo?.name && `Ad Soyad: ${cvData.personalInfo.name}`,
        cvData.personalInfo?.geburtsdatum && `Dogum Tarihi: ${cvData.personalInfo.geburtsdatum}`,
        cvData.personalInfo?.geburtsort && `Dogum Yeri: ${cvData.personalInfo.geburtsort}`,
        cvData.personalInfo?.familienstand && `Medeni Hali: ${cvData.personalInfo.familienstand}`,
        cvData.personalInfo?.staatsangehorigkeit && `Uyruk: ${cvData.personalInfo.staatsangehorigkeit}`,
        cvData.personalInfo?.anschrift && `Adres: ${cvData.personalInfo.anschrift}`,
        cvData.personalInfo?.email && `E-posta: ${cvData.personalInfo.email}`,
        cvData.personalInfo?.telefon && `Telefon: ${cvData.personalInfo.telefon}`,
      ].filter(Boolean).join('\n'),
      cv_education_tr: educationText,
      cv_languages_tr: languageText,
      cv_computer_skills_tr: skillsCombined.join(', '),
      cv_hobbies_tr: hobbiesCombined.join(', '),
      cv_skills_tr: skillsCombined.join(', '),
      cv_city_signature_tr: bridge.signatureCity || ''
    };

    setSaveState({ status: 'saving', message: 'Kaydediliyor...' });
    try {
      const res = await fetch(bridge.generateUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': bridge.csrfToken },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new Error(firstError || data?.message || 'Kayıt başarısız');
      }
      setSaveState({ status: 'success', message: data?.message || 'Belge oluşturuldu!' });
      if (bridge.documentCenterUrl) {
        setTimeout(() => { window.location.href = bridge.documentCenterUrl; }, 1000);
      }
    } catch (err) {
      setSaveState({ status: 'error', message: err?.message || 'Kayıt hatası' });
    }
  };

  const completedSteps = (() => {
    const done = [];
    if (cvData.personalInfo.name && cvData.personalInfo.geburtsdatum) done.push(1);
    if (cvData.education.length > 0) done.push(2);
    if (cvData.selectedSkills.length > 0 || cvData.customSkills) done.push(3);
    return done;
  })();

  return (
    <div style={{ fontFamily: 'inherit', color: 'var(--u-text)' }}>
      {/* Top bar */}
      <div className="cv-topbar" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16, flexWrap: 'wrap', gap: 10 }}>
        {/* Step navigator */}
        <div className="cv-steps" style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
          {STEPS.map((step) => {
            const isDone = completedSteps.includes(step.id);
            const isActive = currentStep === step.id;
            let bg = 'var(--u-line)', color = 'var(--u-muted)', border = 'none';
            if (isActive) { bg = '#7c3aed'; color = '#fff'; }
            else if (isDone) { bg = '#dcfce7'; color = '#16a34a'; border = '1px solid #bbf7d0'; }
            return (
              <button
                key={step.id}
                onClick={() => setCurrentStep(step.id)}
                style={{ background: bg, color, border, borderRadius: 20, padding: '6px 14px', fontSize: 12, fontWeight: 600, cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 5, transition: 'all .15s' }}
              >
                {isDone && !isActive ? <Check size={12} /> : step.icon}
                {stepLabel(step, lang)}
              </button>
            );
          })}
        </div>

        {/* Actions */}
        <div className="cv-actions" style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
          {saveState.message && (
            <span style={{ fontSize: 13, color: saveState.status === 'error' ? '#dc2626' : '#16a34a', fontWeight: 500 }}>
              {saveState.message}
            </span>
          )}
          {bridge.allowLanguageSelector && (
            <button onClick={() => setShowLangPicker(true)} style={{ ...S.btnSecondary, padding: '7px 12px', fontSize: 12 }}>
              <Globe size={14} /> {lang.toUpperCase()}
            </button>
          )}
          <button onClick={() => setCurrentStep(0)} style={{ ...S.btnSecondary, padding: '7px 12px', fontSize: 12, background: currentStep === 0 ? '#7c3aed' : 'var(--u-line)', color: currentStep === 0 ? '#fff' : 'var(--u-text)' }}>
            <Play size={14} /> {stepLabel(VIDEO_STEP, lang)}
          </button>
          <button onClick={() => setShowInstructions(true)} style={{ ...S.btnSecondary, padding: '7px 12px', fontSize: 12 }}>
            <Info size={14} /> Rehber
          </button>
          {bridge.documentCenterUrl && (
            <a href={bridge.documentCenterUrl} style={{ ...S.btnSecondary, padding: '7px 12px', fontSize: 12, textDecoration: 'none' }}>
              <FileText size={14} /> Belgeler
            </a>
          )}
          <button onClick={saveToLaravel} disabled={saveState.status === 'saving'} style={{ ...S.btnPrimary, opacity: saveState.status === 'saving' ? 0.7 : 1 }}>
            <Save size={15} />
            {saveState.status === 'saving' ? 'Kaydediliyor...' : lang === 'de' ? 'Speichern' : lang === 'en' ? 'Save' : 'Kaydet & Oluştur'}
          </button>
        </div>
      </div>

      {/* Step content */}
      <div>
        {currentStep === 0 && <VideoTutorialsStep lang={lang} />}
        {currentStep === 1 && <PersonalDataStep cvData={cvData} lang={lang} onChange={handlePersonalInfoChange} />}
        {currentStep === 2 && <EducationStep cvData={cvData} lang={lang} onAdd={addEducation} onUpdate={updateEducation} onRemove={removeEducation} onAddLang={addLanguage} onUpdateLang={updateLanguage} onRemoveLang={removeLanguage} />}
        {currentStep === 3 && <SkillsStep cvData={cvData} lang={lang} onToggleSkill={toggleSkill} onToggleHobby={toggleHobby} onCustomSkillsChange={(v) => setCvData(p => ({ ...p, customSkills: v }))} onCustomHobbiesChange={(v) => setCvData(p => ({ ...p, customHobbies: v }))} />}
        {currentStep === 4 && <PreviewStep cvData={cvData} lang={lang} onSave={saveToLaravel} onShowPreview={() => setShowPreview(true)} saveState={saveState} />}
      </div>

      {/* Navigation buttons */}
      <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 20, paddingTop: 16, borderTop: '1px solid var(--u-line)' }}>
        <button
          onClick={() => setCurrentStep(s => Math.max(0, s - 1))}
          style={{ ...S.btnSecondary, visibility: currentStep === 0 ? 'hidden' : 'visible' }}
        >
          <ChevronLeft size={16} />
          {lang === 'de' ? 'Zurück' : lang === 'en' ? 'Back' : 'Geri'}
        </button>
        {currentStep < 4 ? (
          <button onClick={() => setCurrentStep(s => Math.min(4, s + 1))} style={S.btnPrimary}>
            {lang === 'de' ? 'Weiter' : lang === 'en' ? 'Next' : 'İleri'}
            <ChevronRight size={16} />
          </button>
        ) : (
          <button onClick={saveToLaravel} disabled={saveState.status === 'saving'} style={{ ...S.btnPrimary, background: '#16a34a', opacity: saveState.status === 'saving' ? 0.7 : 1 }}>
            <Save size={15} />
            {lang === 'de' ? 'Speichern' : lang === 'en' ? 'Save CV' : 'CV\'yi Kaydet'}
          </button>
        )}
      </div>

      {/* Modals */}
      {showInstructions && <InstructionsModal lang={lang} onClose={() => setShowInstructions(false)} />}
      {showPreview && <GermanCVPreviewModal cvData={cvData} lang={lang} onClose={() => setShowPreview(false)} />}
    </div>
  );
};

// ─── Step: Video Tutorials ─────────────────────────────────────────────────────

const VideoTutorialsStep = ({ lang }) => {
  const tutorials = VIDEO_TUTORIALS[lang] || VIDEO_TUTORIALS.tr;
  return (
    <div>
      <div style={{ ...S.card, background: 'linear-gradient(135deg, #7c3aed15, #7c3aed08)', borderColor: '#7c3aed30', marginBottom: 16 }}>
        <p style={{ margin: 0, fontSize: 14, color: 'var(--u-muted)', lineHeight: 1.6 }}>
          {lang === 'tr' && 'Alman CV formatı hakkında kısa video eğitimler. Bu videolar CV oluşturma sürecinde size rehberlik edecek.'}
          {lang === 'en' && 'Short video tutorials about the German CV format. These videos will guide you through the CV creation process.'}
          {lang === 'de' && 'Kurze Video-Tutorials über das deutsche Lebenslauf-Format.'}
        </p>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: 12 }}>
        {tutorials.map((video) => (
          <div key={video.id} style={{ ...S.card, marginBottom: 0, cursor: 'pointer', transition: 'box-shadow .15s' }}
            onMouseEnter={e => e.currentTarget.style.boxShadow = '0 4px 16px rgba(124,58,237,.12)'}
            onMouseLeave={e => e.currentTarget.style.boxShadow = 'none'}
          >
            <div style={{ fontSize: 32, marginBottom: 10 }}>{video.thumbnail}</div>
            <div style={{ fontSize: 13, fontWeight: 700, color: 'var(--u-text)', marginBottom: 4, lineHeight: 1.4 }}>{video.title}</div>
            <div style={{ fontSize: 12, color: 'var(--u-muted)', marginBottom: 10 }}>{video.description}</div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span style={{ fontSize: 11, color: 'var(--u-muted)', background: 'var(--u-line)', borderRadius: 4, padding: '2px 8px' }}>{video.duration}</span>
              <span style={{ fontSize: 11, color: '#7c3aed', fontWeight: 600, display: 'flex', alignItems: 'center', gap: 3 }}>
                <Play size={11} /> {lang === 'de' ? 'Abspielen' : lang === 'en' ? 'Play' : 'İzle'}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// ─── Step: Personal Data ───────────────────────────────────────────────────────

const PersonalDataStep = ({ cvData, lang, onChange }) => {
  const p = cvData.personalInfo;
  const inp = (field, placeholder, required) => (
    <input
      style={S.input}
      value={p[field] || ''}
      placeholder={placeholder}
      onChange={e => onChange(field, e.target.value)}
      required={required}
    />
  );
  return (
    <div>
      <div style={S.card}>
        <div style={S.sectionTitle}>
          <User size={18} style={{ color: '#7c3aed' }} />
          {lang === 'de' ? 'Persönliche Daten' : lang === 'en' ? 'Personal Information' : 'Kişisel Bilgiler'}
        </div>
        <div style={{ ...S.row2, marginBottom: 14 }}>
          <Field label={lang === 'de' ? 'Vollständiger Name' : lang === 'en' ? 'Full Name' : 'Ad Soyad'} required>
            {inp('name', 'Max Mustermann', true)}
          </Field>
          <Field label={lang === 'de' ? 'Geburtsdatum' : lang === 'en' ? 'Date of Birth' : 'Doğum Tarihi'} required>
            {inp('geburtsdatum', 'GG.AA.YYYY', true)}
          </Field>
        </div>
        <div style={{ ...S.row2, marginBottom: 14 }}>
          <Field label={lang === 'de' ? 'Geburtsort' : lang === 'en' ? 'Place of Birth' : 'Doğum Yeri'}>
            {inp('geburtsort', lang === 'de' ? 'Berlin' : 'İstanbul')}
          </Field>
          <Field label={lang === 'de' ? 'Familienstand' : lang === 'en' ? 'Marital Status' : 'Medeni Durum'}>
            <select style={S.input} value={p.familienstand || ''} onChange={e => onChange('familienstand', e.target.value)}>
              <option value="">—</option>
              <option value="ledig">Ledig (Bekar)</option>
              <option value="verheiratet">Verheiratet (Evli)</option>
              <option value="geschieden">Geschieden (Boşanmış)</option>
              <option value="verwitwet">Verwitwet (Dul)</option>
            </select>
          </Field>
        </div>
        <div style={{ ...S.row2, marginBottom: 14 }}>
          <Field label={lang === 'de' ? 'Staatsangehörigkeit' : lang === 'en' ? 'Nationality' : 'Uyruk'}>
            {inp('staatsangehorigkeit', 'Türke / Deutsch')}
          </Field>
          <Field label={lang === 'de' ? 'Telefon' : 'Telefon'}>
            {inp('telefon', '+49 170 123 4567')}
          </Field>
        </div>
        <div style={{ marginBottom: 14 }}>
          <Field label={lang === 'de' ? 'E-Mail' : 'E-Mail'} required>
            <input style={S.input} type="email" value={p.email || ''} placeholder="example@gmail.com" onChange={e => onChange('email', e.target.value)} />
          </Field>
        </div>
        <div>
          <Field label={lang === 'de' ? 'Anschrift' : lang === 'en' ? 'Address' : 'Adres'}>
            <textarea
              style={{ ...S.input, height: 70, resize: 'vertical' }}
              value={p.anschrift || ''}
              placeholder={lang === 'de' ? 'Musterstraße 1, 12345 Berlin' : 'Mahalle, Sokak, Bina No, Şehir'}
              onChange={e => onChange('anschrift', e.target.value)}
            />
          </Field>
        </div>
      </div>

      <div style={{ ...S.card, background: '#fffbeb', borderColor: '#fde68a' }}>
        <div style={{ fontSize: 12, color: '#92400e', lineHeight: 1.6 }}>
          <strong>💡 {lang === 'de' ? 'Hinweis' : lang === 'en' ? 'Tip' : 'İpucu'}:</strong>{' '}
          {lang === 'tr' && 'Doğum tarihinizi GG.AA.YYYY formatında yazın (örn: 15.03.2000). Ad soyadınızı pasaportunuzdaki gibi tam yazın.'}
          {lang === 'en' && 'Write your date of birth in DD.MM.YYYY format (e.g: 15.03.2000). Write your full name exactly as in your passport.'}
          {lang === 'de' && 'Schreiben Sie Ihr Geburtsdatum im Format TT.MM.JJJJ (z.B: 15.03.2000). Schreiben Sie Ihren vollständigen Namen wie im Reisepass.'}
        </div>
      </div>
    </div>
  );
};

// ─── Step: Education ───────────────────────────────────────────────────────────

const EducationStep = ({ cvData, lang, onAdd, onUpdate, onRemove, onAddLang, onUpdateLang, onRemoveLang }) => {
  const LANG_LEVELS = ['Muttersprache', 'C2 – Mastery', 'C1 – Advanced', 'B2 – Upper-Intermediate', 'B1 – Intermediate', 'A2 – Elementary', 'A1 – Beginner', 'Anfänger', 'Mittelstufe', 'Fortgeschritten'];
  return (
    <div>
      {/* Education */}
      <div style={S.card}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
          <div style={S.sectionTitle}>
            <GraduationCap size={18} style={{ color: '#7c3aed' }} />
            {lang === 'de' ? 'Schulbildung' : lang === 'en' ? 'Education' : 'Eğitim Geçmişi'}
          </div>
          <button onClick={onAdd} style={S.btnGhost}>
            <Plus size={14} />
            {lang === 'de' ? 'Hinzufügen' : lang === 'en' ? 'Add' : 'Eğitim Ekle'}
          </button>
        </div>

        {cvData.education.length === 0 && (
          <div style={{ textAlign: 'center', padding: '32px 20px', color: 'var(--u-muted)', fontSize: 14 }}>
            <GraduationCap size={32} style={{ opacity: 0.3, display: 'block', margin: '0 auto 10px' }} />
            {lang === 'tr' ? 'Henüz eğitim bilgisi eklenmedi.' : lang === 'en' ? 'No education added yet.' : 'Noch keine Ausbildung hinzugefügt.'}
          </div>
        )}

        {cvData.education.map((edu, idx) => (
          <div key={edu.id} style={{ border: '1px solid var(--u-line)', borderRadius: 8, padding: 16, marginBottom: 12, background: 'var(--u-bg)' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
              <span style={{ fontSize: 12, fontWeight: 700, color: '#7c3aed', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                {lang === 'tr' ? `${idx + 1}. Eğitim` : lang === 'en' ? `Education ${idx + 1}` : `Ausbildung ${idx + 1}`}
              </span>
              <button onClick={() => onRemove(edu.id)} style={S.btnDanger}>
                <Trash2 size={13} />
              </button>
            </div>
            <div style={{ ...S.row2, marginBottom: 12 }}>
              <Field label={lang === 'de' ? 'Von (MM.JJJJ)' : lang === 'en' ? 'From (MM.YYYY)' : 'Başlangıç (AA.YYYY)'}>
                <input style={S.input} value={edu.startDate} placeholder="09.2020" onChange={e => onUpdate(edu.id, 'startDate', e.target.value)} />
              </Field>
              <Field label={lang === 'de' ? 'Bis (MM.JJJJ)' : lang === 'en' ? 'Until (MM.YYYY)' : 'Bitiş (AA.YYYY)'}>
                <input style={S.input} value={edu.endDate} placeholder="06.2024" onChange={e => onUpdate(edu.id, 'endDate', e.target.value)} />
              </Field>
            </div>
            <div style={{ marginBottom: 12 }}>
              <Field label={lang === 'de' ? 'Schule / Universität' : lang === 'en' ? 'School / University' : 'Okul / Üniversite'}>
                <input style={S.input} value={edu.institution} placeholder="Anadolu Lisesi" onChange={e => onUpdate(edu.id, 'institution', e.target.value)} />
              </Field>
            </div>
            <div style={S.row3}>
              <Field label={lang === 'de' ? 'Stadt' : lang === 'en' ? 'City' : 'Şehir'}>
                <input style={S.input} value={edu.city} placeholder="İstanbul" onChange={e => onUpdate(edu.id, 'city', e.target.value)} />
              </Field>
              <Field label={lang === 'de' ? 'Land' : lang === 'en' ? 'Country' : 'Ülke'}>
                <input style={S.input} value={edu.country} placeholder="Türkei / Türkiye" onChange={e => onUpdate(edu.id, 'country', e.target.value)} />
              </Field>
              <Field label={lang === 'de' ? 'Abschluss' : lang === 'en' ? 'Degree' : 'Diploma'}>
                <input style={S.input} value={edu.degree} placeholder="Diplom / Abitur" onChange={e => onUpdate(edu.id, 'degree', e.target.value)} />
              </Field>
            </div>
          </div>
        ))}
      </div>

      {/* Languages */}
      <div style={S.card}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
          <div style={S.sectionTitle}>
            <Globe size={18} style={{ color: '#7c3aed' }} />
            {lang === 'de' ? 'Sprachkenntnisse' : lang === 'en' ? 'Language Skills' : 'Dil Becerileri'}
          </div>
          <button onClick={onAddLang} style={S.btnGhost}>
            <Plus size={14} />
            {lang === 'de' ? 'Sprache' : lang === 'en' ? 'Add Language' : 'Dil Ekle'}
          </button>
        </div>

        {cvData.languages.length === 0 && (
          <div style={{ textAlign: 'center', padding: '24px 20px', color: 'var(--u-muted)', fontSize: 14 }}>
            <Globe size={28} style={{ opacity: 0.3, display: 'block', margin: '0 auto 8px' }} />
            {lang === 'tr' ? 'Henüz dil bilgisi eklenmedi.' : lang === 'en' ? 'No languages added yet.' : 'Noch keine Sprachen hinzugefügt.'}
          </div>
        )}

        {cvData.languages.map((lng) => (
          <div key={lng.id} style={{ display: 'grid', gridTemplateColumns: '1fr 1fr auto', gap: 10, marginBottom: 10, alignItems: 'end' }}>
            <Field label={lang === 'de' ? 'Sprache' : lang === 'en' ? 'Language' : 'Dil'}>
              <input style={S.input} value={lng.language} placeholder="Türkisch / Deutsch" onChange={e => onUpdateLang(lng.id, 'language', e.target.value)} />
            </Field>
            <Field label={lang === 'de' ? 'Niveau' : lang === 'en' ? 'Level' : 'Seviye'}>
              <select style={S.input} value={lng.level} onChange={e => onUpdateLang(lng.id, 'level', e.target.value)}>
                <option value="">— Seviye seçin —</option>
                {LANG_LEVELS.map(l => <option key={l} value={l}>{l}</option>)}
              </select>
            </Field>
            <button onClick={() => onRemoveLang(lng.id)} style={{ ...S.btnDanger, marginBottom: 0 }}>
              <Trash2 size={13} />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
};

// ─── Step: Skills ──────────────────────────────────────────────────────────────

const SkillsStep = ({ cvData, lang, onToggleSkill, onToggleHobby, onCustomSkillsChange, onCustomHobbiesChange }) => {
  const skills = SKILLS_LIST[lang] || SKILLS_LIST.tr;
  const hobbies = HOBBIES_LIST[lang] || HOBBIES_LIST.tr;

  const ChipGrid = ({ items, selected, onToggle }) => (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
      {items.map((item) => {
        const active = selected.includes(item.value);
        return (
          <button
            key={item.value}
            onClick={() => onToggle(item.value)}
            style={{
              background: active ? '#7c3aed' : 'var(--u-line)',
              color: active ? '#fff' : 'var(--u-text)',
              border: active ? '1px solid #7c3aed' : '1px solid var(--u-line)',
              borderRadius: 20, padding: '5px 14px', fontSize: 13, cursor: 'pointer',
              display: 'inline-flex', alignItems: 'center', gap: 5, transition: 'all .12s'
            }}
          >
            {active && <Check size={12} />}
            {item.label}
          </button>
        );
      })}
    </div>
  );

  return (
    <div>
      {/* Computer Skills */}
      <div style={S.card}>
        <div style={S.sectionTitle}>
          <Code size={18} style={{ color: '#7c3aed' }} />
          {lang === 'de' ? 'Computerkenntnisse' : lang === 'en' ? 'Computer & Soft Skills' : 'Bilgisayar & Soft Skills'}
        </div>
        <ChipGrid items={skills} selected={cvData.selectedSkills} onToggle={onToggleSkill} />
        <div style={{ marginTop: 14 }}>
          <Field label={lang === 'de' ? 'Weitere Kenntnisse (optional)' : lang === 'en' ? 'Additional Skills (optional)' : 'Ek Beceriler (isteğe bağlı)'}>
            <textarea
              style={{ ...S.input, height: 80, resize: 'vertical' }}
              value={cvData.customSkills}
              placeholder={lang === 'tr' ? 'Her satıra bir beceri yazın...' : lang === 'en' ? 'One skill per line...' : 'Eine Fähigkeit pro Zeile...'}
              onChange={e => onCustomSkillsChange(e.target.value)}
            />
          </Field>
        </div>
        {cvData.selectedSkills.length > 0 && (
          <div style={{ marginTop: 10, fontSize: 12, color: 'var(--u-muted)' }}>
            ✓ {cvData.selectedSkills.length} {lang === 'tr' ? 'beceri seçildi' : lang === 'en' ? 'skills selected' : 'Fähigkeiten ausgewählt'}
          </div>
        )}
      </div>

      {/* Hobbies */}
      <div style={S.card}>
        <div style={S.sectionTitle}>
          <span style={{ fontSize: 18 }}>🎨</span>
          {lang === 'de' ? 'Interessen und Hobbys' : lang === 'en' ? 'Interests & Hobbies' : 'İlgi Alanları ve Hobiler'}
        </div>
        <ChipGrid items={hobbies} selected={cvData.selectedHobbies} onToggle={onToggleHobby} />
        <div style={{ marginTop: 14 }}>
          <Field label={lang === 'de' ? 'Weitere Hobbys (optional)' : lang === 'en' ? 'Additional Hobbies (optional)' : 'Ek Hobiler (isteğe bağlı)'}>
            <textarea
              style={{ ...S.input, height: 60, resize: 'vertical' }}
              value={cvData.customHobbies}
              placeholder={lang === 'tr' ? 'Her satıra bir hobi...' : lang === 'en' ? 'One hobby per line...' : 'Ein Hobby pro Zeile...'}
              onChange={e => onCustomHobbiesChange(e.target.value)}
            />
          </Field>
        </div>
      </div>
    </div>
  );
};

// ─── Step: Preview ─────────────────────────────────────────────────────────────

const PreviewStep = ({ cvData, lang, onSave, onShowPreview, saveState }) => {
  const p = cvData.personalInfo;
  const skillsCombined = [...cvData.selectedSkills, ...(cvData.customSkills ? cvData.customSkills.split('\n').filter(s => s.trim()) : [])];
  const hobbiesCombined = [...cvData.selectedHobbies, ...(cvData.customHobbies ? cvData.customHobbies.split('\n').filter(s => s.trim()) : [])];

  const SummaryRow = ({ label, value }) => value ? (
    <div style={{ display: 'flex', gap: 12, padding: '8px 0', borderBottom: '1px solid var(--u-line)', fontSize: 13 }}>
      <span style={{ color: 'var(--u-muted)', minWidth: 140, fontWeight: 600 }}>{label}</span>
      <span style={{ color: 'var(--u-text)' }}>{value}</span>
    </div>
  ) : null;

  const CheckItem = ({ ok, label }) => (
    <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '6px 0', fontSize: 13 }}>
      <span style={{ width: 20, height: 20, borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', background: ok ? '#dcfce7' : '#fee2e2', flexShrink: 0 }}>
        {ok ? <Check size={12} style={{ color: '#16a34a' }} /> : <X size={12} style={{ color: '#dc2626' }} />}
      </span>
      <span style={{ color: ok ? 'var(--u-text)' : 'var(--u-muted)' }}>{label}</span>
    </div>
  );

  return (
    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
      {/* Left: Summary */}
      <div>
        <div style={S.card}>
          <div style={S.sectionTitle}>
            <User size={18} style={{ color: '#7c3aed' }} />
            {lang === 'de' ? 'Persönliche Daten' : lang === 'en' ? 'Personal Data' : 'Kişisel Bilgiler'}
          </div>
          <SummaryRow label={lang === 'de' ? 'Name' : 'Ad Soyad'} value={p.name} />
          <SummaryRow label={lang === 'de' ? 'Geburtsdatum' : 'Doğum Tarihi'} value={p.geburtsdatum} />
          <SummaryRow label={lang === 'de' ? 'Geburtsort' : 'Doğum Yeri'} value={p.geburtsort} />
          <SummaryRow label={lang === 'de' ? 'Familienstand' : 'Medeni Durum'} value={p.familienstand} />
          <SummaryRow label={lang === 'de' ? 'Staatsangehörigkeit' : 'Uyruk'} value={p.staatsangehorigkeit} />
          <SummaryRow label="E-Mail" value={p.email} />
          <SummaryRow label="Telefon" value={p.telefon} />
          <SummaryRow label={lang === 'de' ? 'Anschrift' : 'Adres'} value={p.anschrift} />
        </div>

        {cvData.education.length > 0 && (
          <div style={S.card}>
            <div style={S.sectionTitle}>
              <GraduationCap size={18} style={{ color: '#7c3aed' }} />
              {lang === 'de' ? 'Schulbildung' : 'Eğitim'}
            </div>
            {cvData.education.map((edu, i) => (
              <div key={edu.id} style={{ padding: '8px 0', borderBottom: i < cvData.education.length - 1 ? '1px solid var(--u-line)' : 'none', fontSize: 13 }}>
                <div style={{ fontWeight: 600, color: 'var(--u-text)' }}>{edu.institution}</div>
                <div style={{ color: 'var(--u-muted)' }}>{edu.startDate} – {edu.endDate} · {edu.city}, {edu.country}</div>
                {edu.degree && <div style={{ color: 'var(--u-muted)' }}>{edu.degree}</div>}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Right: Checklist + actions */}
      <div>
        <div style={S.card}>
          <div style={S.sectionTitle}>
            <Check size={18} style={{ color: '#16a34a' }} />
            {lang === 'de' ? 'Vollständigkeits-Check' : lang === 'en' ? 'Completeness Check' : 'Tamamlanma Kontrolü'}
          </div>
          <CheckItem ok={Boolean(p.name)} label={lang === 'de' ? 'Name ausgefüllt' : lang === 'en' ? 'Name filled' : 'Ad Soyad girildi'} />
          <CheckItem ok={Boolean(p.geburtsdatum)} label={lang === 'de' ? 'Geburtsdatum' : 'Doğum tarihi girildi'} />
          <CheckItem ok={Boolean(p.email)} label="E-Mail girildi" />
          <CheckItem ok={cvData.education.length > 0} label={lang === 'de' ? 'Schulbildung' : 'En az 1 eğitim eklendi'} />
          <CheckItem ok={cvData.languages.length > 0} label={lang === 'de' ? 'Sprachkenntnisse' : 'En az 1 dil eklendi'} />
          <CheckItem ok={skillsCombined.length > 0} label={lang === 'de' ? 'Kenntnisse' : 'Beceriler seçildi'} />
          <CheckItem ok={hobbiesCombined.length > 0} label={lang === 'de' ? 'Hobbys' : 'Hobiler seçildi'} />
        </div>

        {(skillsCombined.length > 0 || hobbiesCombined.length > 0) && (
          <div style={S.card}>
            {skillsCombined.length > 0 && (
              <>
                <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--u-muted)', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.06em' }}>
                  {lang === 'de' ? 'Kenntnisse' : 'Beceriler'}
                </div>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginBottom: 14 }}>
                  {skillsCombined.map((s, i) => (
                    <span key={i} style={{ background: '#ede9fe', color: '#5b21b6', borderRadius: 12, padding: '3px 10px', fontSize: 12 }}>{s}</span>
                  ))}
                </div>
              </>
            )}
            {hobbiesCombined.length > 0 && (
              <>
                <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--u-muted)', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.06em' }}>
                  {lang === 'de' ? 'Hobbys' : 'Hobiler'}
                </div>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                  {hobbiesCombined.map((h, i) => (
                    <span key={i} style={{ background: '#f0fdf4', color: '#15803d', borderRadius: 12, padding: '3px 10px', fontSize: 12 }}>{h}</span>
                  ))}
                </div>
              </>
            )}
          </div>
        )}

        <div style={S.card}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            <button onClick={onShowPreview} style={{ ...S.btnSecondary, justifyContent: 'center' }}>
              <Eye size={16} />
              {lang === 'de' ? 'Lebenslauf-Vorschau' : lang === 'en' ? 'Preview CV' : 'CV Önizleme'}
            </button>
            <button
              onClick={onSave}
              disabled={saveState.status === 'saving'}
              style={{ ...S.btnPrimary, background: '#16a34a', justifyContent: 'center', opacity: saveState.status === 'saving' ? 0.7 : 1 }}
            >
              <Save size={16} />
              {saveState.status === 'saving' ? 'Kaydediliyor...' : lang === 'de' ? 'Lebenslauf speichern' : lang === 'en' ? 'Save CV' : 'CV\'yi Kaydet & İndir'}
            </button>
            {saveState.message && (
              <div style={{ fontSize: 13, textAlign: 'center', color: saveState.status === 'error' ? '#dc2626' : '#16a34a', padding: '8px 0', fontWeight: 500 }}>
                {saveState.message}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

// ─── Instructions Modal ────────────────────────────────────────────────────────

const InstructionsModal = ({ lang, onClose }) => {
  const instructions = CV_INSTRUCTIONS[lang] || CV_INSTRUCTIONS.tr;
  return (
    <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
      <div style={{ background: '#fff', borderRadius: 14, width: '100%', maxWidth: 680, maxHeight: '85vh', overflow: 'hidden', display: 'flex', flexDirection: 'column', boxShadow: '0 20px 60px rgba(0,0,0,.25)' }}>
        <div style={{ background: 'linear-gradient(135deg,#7c3aed,#6d28d9)', color: '#fff', padding: '16px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderRadius: '14px 14px 0 0' }}>
          <h2 style={{ margin: 0, fontSize: 16, fontWeight: 700, display: 'flex', alignItems: 'center', gap: 8 }}>
            <Info size={18} /> {instructions.title}
          </h2>
          <button onClick={onClose} style={{ background: 'rgba(255,255,255,.2)', border: 'none', borderRadius: '50%', width: 32, height: 32, cursor: 'pointer', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <X size={18} />
          </button>
        </div>
        <div style={{ overflowY: 'auto', padding: 20 }}>
          {Object.values(instructions.sections).map((section) => (
            <div key={section.title} style={{ marginBottom: 20 }}>
              <div style={{ fontWeight: 700, fontSize: 14, color: '#1e293b', marginBottom: 10 }}>{section.title}</div>
              <ul style={{ margin: 0, padding: '0 0 0 20px', listStyle: 'disc' }}>
                {section.items.map((item, i) => (
                  <li key={i} style={{ fontSize: 13, color: '#475569', lineHeight: 1.6, marginBottom: 4 }}>{item}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// ─── German CV Preview Modal ───────────────────────────────────────────────────

const GermanCVPreviewModal = ({ cvData, onClose, lang }) => {
  const today = new Date();
  const formattedDate = `${cvData.personalInfo.geburtsort || 'Çorum'}, ${today.getDate()}.${today.getMonth() + 1}.${today.getFullYear()}`;
  const combinedSkills = [...cvData.selectedSkills, ...(cvData.customSkills ? cvData.customSkills.split('\n').filter(s => s.trim()) : [])];
  const combinedHobbies = [...cvData.selectedHobbies, ...(cvData.customHobbies ? cvData.customHobbies.split('\n').filter(h => h.trim()) : [])];

  return (
    <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.6)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: 20 }}>
      <div style={{ background: '#fff', borderRadius: 14, width: '100%', maxWidth: 760, maxHeight: '90vh', overflow: 'hidden', display: 'flex', flexDirection: 'column', boxShadow: '0 24px 80px rgba(0,0,0,.3)' }}>
        <div style={{ background: 'linear-gradient(135deg,#7c3aed,#6d28d9)', color: '#fff', padding: '14px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderRadius: '14px 14px 0 0', flexShrink: 0 }}>
          <h2 style={{ margin: 0, fontSize: 16, fontWeight: 700 }}>Lebenslauf — Vorschau</h2>
          <div style={{ display: 'flex', gap: 8 }}>
            <button
              onClick={() => alert('PDF download wird implementiert')}
              style={{ background: 'rgba(255,255,255,.9)', color: '#7c3aed', border: 'none', borderRadius: 8, padding: '6px 14px', fontSize: 13, fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6 }}
            >
              <Download size={15} /> PDF
            </button>
            <button onClick={onClose} style={{ background: 'rgba(255,255,255,.2)', border: 'none', borderRadius: '50%', width: 32, height: 32, cursor: 'pointer', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <X size={18} />
            </button>
          </div>
        </div>

        <div style={{ overflowY: 'auto', padding: 24 }}>
          <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: 8, padding: 32, maxWidth: '210mm', margin: '0 auto', fontFamily: 'Arial, sans-serif', fontSize: 13, color: '#1a1a1a' }}>
            {/* Header */}
            <div style={{ borderBottom: '2px solid #1d4ed8', marginBottom: 20, paddingBottom: 8 }}>
              <h1 style={{ margin: 0, fontSize: 26, fontWeight: 700, color: '#1e3a8a' }}>Lebenslauf</h1>
            </div>

            {/* Persönliche Daten */}
            <div style={{ marginBottom: 20 }}>
              <div style={{ borderBottom: '2px solid #1d4ed8', marginBottom: 12, paddingBottom: 6 }}>
                <h2 style={{ margin: 0, fontSize: 15, fontWeight: 700, color: '#1d4ed8' }}>Persönliche Daten</h2>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <div style={{ flex: 1 }}>
                  {[
                    ['Name', cvData.personalInfo.name],
                    ['Geburtsdatum', cvData.personalInfo.geburtsdatum],
                    ['Geburtsort', cvData.personalInfo.geburtsort],
                    ['Familienstand', cvData.personalInfo.familienstand],
                    ['Staatsangehörigkeit', cvData.personalInfo.staatsangehorigkeit],
                    ['Anschrift', cvData.personalInfo.anschrift],
                    ['E-Mail', cvData.personalInfo.email],
                    ['Telefon', cvData.personalInfo.telefon],
                  ].filter(([, v]) => v).map(([label, value]) => (
                    <div key={label} style={{ display: 'grid', gridTemplateColumns: '120px 1fr', gap: 12, marginBottom: 4 }}>
                      <span style={{ fontWeight: 700, fontSize: 12 }}>{label}</span>
                      <span style={{ fontSize: 12 }}>{value}</span>
                    </div>
                  ))}
                </div>
                <div style={{ width: 100, height: 130, border: '1.5px solid #93c5fd', borderRadius: 4, marginLeft: 12, flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#eff6ff', color: '#3b82f6', fontSize: 11, textAlign: 'center' }}>
                  Foto<br />3.5×4.5cm
                </div>
              </div>
            </div>

            {/* Schulbildung */}
            {cvData.education.length > 0 && (
              <div style={{ marginBottom: 20 }}>
                <div style={{ borderBottom: '2px solid #1d4ed8', marginBottom: 12, paddingBottom: 6 }}>
                  <h2 style={{ margin: 0, fontSize: 15, fontWeight: 700, color: '#1d4ed8' }}>Schulbildung</h2>
                </div>
                {cvData.education.map((edu, i) => (
                  <div key={i} style={{ display: 'grid', gridTemplateColumns: '120px 1fr', gap: 12, marginBottom: 8, fontSize: 12 }}>
                    <div style={{ fontWeight: 700 }}>{edu.startDate} – {edu.endDate}</div>
                    <div>
                      <div>{edu.institution}</div>
                      <div style={{ color: '#64748b' }}>{edu.city} / {edu.country}</div>
                      {edu.degree && <div style={{ color: '#64748b' }}>Abschluss: {edu.degree}</div>}
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Sprachkenntnisse */}
            {cvData.languages.length > 0 && (
              <div style={{ marginBottom: 20 }}>
                <div style={{ borderBottom: '2px solid #1d4ed8', marginBottom: 12, paddingBottom: 6 }}>
                  <h2 style={{ margin: 0, fontSize: 15, fontWeight: 700, color: '#1d4ed8' }}>Sprachkenntnisse</h2>
                </div>
                {cvData.languages.map((lng, i) => (
                  <div key={i} style={{ display: 'grid', gridTemplateColumns: '120px 1fr', gap: 12, marginBottom: 4, fontSize: 12 }}>
                    <span style={{ fontWeight: 700 }}>{lng.language}</span>
                    <span>: {lng.level}</span>
                  </div>
                ))}
              </div>
            )}

            {/* Computerkenntnisse */}
            {combinedSkills.length > 0 && (
              <div style={{ marginBottom: 20 }}>
                <div style={{ borderBottom: '2px solid #1d4ed8', marginBottom: 12, paddingBottom: 6 }}>
                  <h2 style={{ margin: 0, fontSize: 15, fontWeight: 700, color: '#1d4ed8' }}>Computerkenntnisse</h2>
                </div>
                <div style={{ fontSize: 12 }}>
                  {combinedSkills.map((s, i) => <div key={i}>{s}</div>)}
                </div>
              </div>
            )}

            {/* Interessen und Hobbys */}
            {combinedHobbies.length > 0 && (
              <div style={{ marginBottom: 20 }}>
                <div style={{ borderBottom: '2px solid #1d4ed8', marginBottom: 12, paddingBottom: 6 }}>
                  <h2 style={{ margin: 0, fontSize: 15, fontWeight: 700, color: '#1d4ed8' }}>Interessen und Hobbys</h2>
                </div>
                <div style={{ fontSize: 12 }}>
                  {combinedHobbies.map((h, i) => <div key={i}>{h}</div>)}
                </div>
              </div>
            )}

            {/* Footer */}
            <div style={{ marginTop: 40, paddingTop: 12, borderTop: '1px solid #e2e8f0', textAlign: 'center' }}>
              <p style={{ margin: 0, fontSize: 12, fontStyle: 'italic', color: '#1d4ed8' }}>{formattedDate}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// ─── Language Selector ─────────────────────────────────────────────────────────

const LanguageSelector = ({ onSelect }) => (
  <div style={{ minHeight: 300, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: 40 }}>
    <div style={{ fontSize: 32, marginBottom: 16 }}>🌍</div>
    <h2 style={{ margin: '0 0 8px', fontSize: 20, fontWeight: 700, color: 'var(--u-text)' }}>Dil Seçin / Select Language / Sprache wählen</h2>
    <p style={{ margin: '0 0 28px', color: 'var(--u-muted)', fontSize: 14 }}>CV oluşturucu arayüz dilini seçin</p>
    <div style={{ display: 'flex', gap: 16 }}>
      {[
        { code: 'tr', flag: '🇹🇷', label: 'Türkçe' },
        { code: 'en', flag: '🇬🇧', label: 'English' },
        { code: 'de', flag: '🇩🇪', label: 'Deutsch' },
      ].map(({ code, flag, label }) => (
        <button
          key={code}
          onClick={() => onSelect(code)}
          style={{ background: 'var(--u-card)', border: '2px solid var(--u-line)', borderRadius: 12, padding: '20px 28px', cursor: 'pointer', textAlign: 'center', transition: 'all .15s', fontSize: 14 }}
          onMouseEnter={e => { e.currentTarget.style.borderColor = '#7c3aed'; e.currentTarget.style.transform = 'translateY(-2px)'; }}
          onMouseLeave={e => { e.currentTarget.style.borderColor = 'var(--u-line)'; e.currentTarget.style.transform = 'none'; }}
        >
          <div style={{ fontSize: 28, marginBottom: 8 }}>{flag}</div>
          <div style={{ fontWeight: 700, color: 'var(--u-text)' }}>{label}</div>
        </button>
      ))}
    </div>
  </div>
);

export default CVBuilderModule;
