# Entegrasyon: Document Builder

---

## Amaç

Senior danışmanların öğrenciler için CV, motivasyon mektubu ve referans mektubu üretmesini sağlayan AI destekli belge üretim sistemi. Üretilen belgeler `Document` modeline kaydedilir.

---

## Desteklenen Belge Tipleri (6 adet)

| Kod | Türkçe | Almanca |
|-----|--------|---------|
| `cv` | Özgeçmiş | Lebenslauf |
| `motivation` | Motivasyon Mektubu | Motivationsschreiben |
| `reference` | Referans Mektubu | Empfehlungsschreiben |
| `cover_letter` | Başvuru Mektubu | Anschreiben |
| `sperrkonto` | Bloke Hesap Başvurusu | Sperrkonto-Antrag |
| `housing` | Yurt Başvurusu | Wohnheimsantrag |

**Çıktı Formatları:** `docx`, `md` (Markdown)

---

## Servisler

### DocumentBuilderService

**Dosya:** `app/Services/DocumentBuilderService.php`

| Metod | Açıklama |
|-------|----------|
| `buildDocumentText($guest, $draft, $type, $lang, $notes, $mode)` | Ana belge metni oluşturma |
| `preview($guest, $draft, $type, $lang, $notes)` | Önizleme (kaydetmeden) |
| `qualityScore(string $content, string $docType)` | İçerik kalite puanı (0-100) |
| `applyAiAssist($type, $built, $guest, $draft, $notes)` | AI iyileştirme (ai_assist mod) |
| `composeReviewNote($notes, $aiResult)` | Belge inceleme notu oluşturma |
| `resolveBuilderCategory($docType)` | DocumentCategory eşleştirme |

### CvTemplateService

**Dosya:** `app/Services/CvTemplateService.php`

- `buildDocxFromText(string $content): string` — Markdown içeriği → DOCX binary
- `PhpWord` veya benzeri kütüphane ile DOCX üretimi

---

## AI Modları

| Mod | Açıklama |
|-----|----------|
| `template` | Şablon tabanlı, form draft verisi ile doldurulur |
| `ai_assist` | Şablon sonrası `AiWritingService` ile iyileştirme yapılır |

**AiWritingService:** `app/Services/AiWritingService.php` — opsiyonel harici AI API entegrasyonu

### AiWritingService Konfigürasyonu (`config/services.php → ai_writer.*`)

| Anahtar | Varsayılan | Açıklama |
|---------|-----------|----------|
| `ai_writer.enabled` | `false` | Servisi etkinleştir |
| `ai_writer.api_key` | `''` | API anahtarı |
| `ai_writer.base_url` | `https://api.openai.com/v1` | Endpoint base URL |
| `ai_writer.provider` | `openai_compatible` | Sağlayıcı (log'da görünür) |
| `ai_writer.model` | `gpt-4o-mini` | Model adı |
| `ai_writer.timeout` | `30` | HTTP timeout (saniye) |

**HTTP:** `temperature: 0.3`, POST `{base_url}/chat/completions`, `Authorization: Bearer {api_key}`
**Kontrol metodları:** `isEnabled()`, `isConfigured()`, `isAvailable()`
**Hata kodları:** `ai_writer_disabled`, `ai_writer_not_configured`, `ai_http_{status}` — hepsi `Log::warning()` ile kaydedilir

---

## Controller Akışı

**Controller:** `SeniorDashboardController`

### 1. Sayfa Yükleme (`documentBuilder()`)
```
GET /senior/document-builder?guest_id=X
→ Atanmış öğrenci listesi
→ Seçili öğrencinin registration_form_draft
→ window.__documentBuilderBridge = {...} JS köprüsü
```

### 2. Belge Üretme (`generateDocumentBuilderFile()`)
```
POST /senior/document-builder/generate
→ validate (guest_application_id, document_type, language, output_format)
→ Senior'ın atanmış öğrencisi kontrolü (403 abort)
→ draft + override alanları merge
→ DocumentBuilderService::buildDocumentText()
→ (ai_assist ise) DocumentBuilderService::applyAiAssist()
→ Storage::disk('public')->put($path, ...)
→ Document::create() — status: 'generated'
→ document_id: 'DOC-STB-XXXXXXX'
→ Redirect + status flash
```

### 3. Önizleme (`previewDocumentBuilder()`)
```
POST /senior/api/preview-document-builder (JSON)
→ draft + doc_type + language + extra_notes
→ DocumentBuilderService::preview()
→ DocumentBuilderService::qualityScore()
→ JSON response: {ok, preview, quality}
```

### 4. İndirme (`downloadDocument()`)
```
GET /senior/documents/{id}/download
→ Senior'ın öğrencisi kontrolü
→ Storage::disk('public')->download()
```

---

## Veri Köprüsü (JS Bridge)

**Senior:**
```js
window.__documentBuilderBridge = { role: 'senior', students: [{id, student_id, name, email}] }
```

**Student (AI Wizard):**
```js
window.__DOC_BUILDER_CFG__ = { csrf, aiDraftUrl, studentId, ... }
```

**React CV Builder:**
```js
window.__STUDENT_CV_BUILDER__ = { bridge: { guest, draft, ... } }
```

**Frontend:** `public/js/document-builder-ai.js` — Vanilla JS wizard (motivasyon/referans A–J bölüm toplama, AI draft POST)
**React:** `resources/js/student-document-builder.jsx` → `ReactDOM.createRoot('#student-cv-builder-root')`

---

## Belge Kaydı

Üretilen belgeler `documents` tablosuna kaydedilir:

| Alan | Değer |
|------|-------|
| `student_id` | `{student_id}` veya `GST-{guest.id}` |
| `category_id` | `resolveBuilderCategory()` döner |
| `process_tags` | `['student_document_builder', type, lang, 'senior_generate']` |
| `status` | `generated` |
| `uploaded_by` | Senior'ın email'i |
| `document_id` | `DOC-STB-XXXXXXX` |
| `storage_path` | `student-builder/{guest_id}/{filename}` |
| `mime_type` | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` veya `text/markdown` |

---

## Kalite Skoru

`DocumentBuilderService::qualityScore()`:
- 0-100 arası puan
- Belge uzunluğu, bölüm başlıkları, anahtar kelime varlığı kontrolü
- Frontend'de progress bar ile gösterilir

---

## Konfigürasyon

**Dosya:** `config/document_builder.php`

6 belge tipi tanımı:
- İçerik yapısı (bölümler)
- Dil seçenekleri
- Şablon verisi
- AI assist prompt şablonu

---

## Student Document Builder

**Route:** `/student/document-builder`
**View:** `resources/views/student/document-builder.blade.php`
**JS:** `resources/js/student-document-builder.jsx`

Öğrenciler kendi CV/motivasyon mektuplarını oluşturabilir. Senior builder'a kıyasla daha kısıtlı (sadece kendi verisi, AI assist yok).

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller | `app/Http/Controllers/SeniorDashboardController.php` |
| Service | `app/Services/DocumentBuilderService.php` |
| Service (DOCX) | `app/Services/CvTemplateService.php` |
| Service (AI) | `app/Services/AiWritingService.php` |
| Config | `config/document_builder.php` |
| View (Senior) | `resources/views/senior/document-builder.blade.php` |
| View (Student) | `resources/views/student/document-builder.blade.php` |
| JS (Senior) | `public/js/senior-document-builder.jsx` |
| JS (Student) | `resources/js/student-document-builder.jsx` |
