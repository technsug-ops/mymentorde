# Entegrasyon: Üniversite Başvuru & Belge Haritası

---

## Amaç

Öğrencilerin Almanya'daki üniversitelere başvuru süreçlerini takip etmek, kurumsal belgelerini yönetmek ve üniversite bazlı gerekli belge haritasını tanımlamak.

---

## 1. Üniversite Başvuru Takibi

### StudentUniversityApplication Modeli

**Dosya:** `app/Models/StudentUniversityApplication.php`
**Tablo:** `student_university_applications`
**Trait'ler:** `SoftDeletes`

| Alan | Açıklama |
|------|----------|
| `student_id` | Öğrenci ID |
| `university_code` | Üniversite kodu |
| `university_name` | Üniversite adı |
| `city` / `state` | Şehir / Eyalet |
| `department_code` | Bölüm kodu |
| `department_name` | Bölüm adı |
| `degree_type` | `bachelor / master / phd` |
| `semester` | `WS / SS` (Kış/Yaz dönemi) |
| `application_portal` | `uni-assist / hochschulstart / direkt` |
| `application_number` | Başvuru numarası |
| `status` | Başvuru durumu |
| `priority` | Öncelik sırası (integer) |
| `deadline` | Son başvuru tarihi |
| `submitted_at` | Gönderim tarihi |
| `result_at` | Sonuç tarihi |
| `is_visible_to_student` | Öğrenci görebilir mi? |
| `is_visible_to_dealer` | Bayi görebilir mi? |

### Başvuru Durum Akışı

| Durum | Açıklama |
|-------|----------|
| `planned` | Planlandı |
| `submitted` | Gönderildi |
| `under_review` | İnceleniyor |
| `accepted` | Kabul |
| `conditional_accepted` | Şartlı Kabul |
| `rejected` | Ret |
| `withdrawn` | Geri Çekildi |

### Route'lar (Senior)

| Metod | Route | Açıklama |
|-------|-------|----------|
| GET | `/senior/university-applications` | Liste |
| POST | `/senior/university-applications` | Ekle |
| PUT | `/senior/university-applications/{id}` | Güncelle |
| DELETE | `/senior/university-applications/{id}` | Sil |
| POST | `/senior/university-applications/{id}/toggle-visibility` | Görünürlük |

---

## 2. Kurumsal Belge Takibi

### StudentInstitutionDocument Modeli

**Dosya:** `app/Models/StudentInstitutionDocument.php`
**Trait'ler:** `SoftDeletes`

Öğrencinin kuruma teslim ettiği veya kurumdan aldığı resmi belgeler.

### Belge Kataloğu

**Dosya:** `config/institution_document_catalog.php`
~80 belge kodu ile önceden tanımlı katalog.

Örnek kodlar: `UA-VPD` (Vize Pasaport Dossier), `UNI-ZULAS` (Üniversite Kabul Mektubu), `SPERR-KONTO` (Bloke Hesap), `ANAHTARLIK` vb.

### Route'lar (Senior)

| Metod | Route | Açıklama |
|-------|-------|----------|
| GET | `/senior/institution-documents` | Liste |
| POST | `/senior/institution-documents` | Ekle |
| PUT | `/senior/institution-documents/{id}` | Güncelle |
| DELETE | `/senior/institution-documents/{id}` | Sil |
| POST | `/senior/institution-documents/{id}/toggle-visibility` | Görünürlük |

### Görünürlük

- `is_visible_to_student = true` → Student portal'da görünür
- `is_visible_to_dealer = true` → Dealer portal'da görünür
- Her iki portal da read-only

---

## 3. Üniversite Belge Haritası (v5.1)

### UniversityRequirementMap Modeli

**Dosya:** `app/Models/UniversityRequirementMap.php`
**Tablo:** `university_requirement_maps`

Her üniversite ve/veya bölüm için hangi belgelerin gerekli olduğunu tanımlar.

| Alan | Açıklama |
|------|----------|
| `university_code` | Üniversite kodu |
| `department_code` | Bölüm kodu (null = üniversite geneli) |
| `degree_type` | `bachelor / master / phd` |
| `semester` | `WS / SS` |
| `required_document_codes` | JSON array: gerekli belge kodları |
| `portal_name` | `uni-assist / hochschulstart / direkt` |
| `deadline_month` | Son başvuru ayı (1-12) |
| `notes` | Ek notlar |

### Seeder

**Dosya:** `database/seeders/UniversityRequirementMapSeeder.php`
5 başlangıç üniversitesi ile seed edilmiştir.

### Manager: Gereklilik Yönetimi

**Route'lar:**

| Metod | Route | Açıklama |
|-------|-------|----------|
| GET | `/manager/university-requirements` | Liste |
| POST | `/manager/university-requirements` | Yeni harita ekle |
| PUT | `/manager/university-requirements/{id}` | Güncelle |
| DELETE | `/manager/university-requirements/{id}` | Sil |
| GET | `/api/university-requirements/{university_code}` | JSON lookup |

**API örneği:**
```
GET /api/university-requirements/TU-BERLIN?degree=master&semester=WS
→ { required_documents: ['UA-VPD', 'UNI-ZULAS', ...], deadline_month: 7 }
```

### Senior: Checklist Entegrasyonu

Öğrenciye üniversite atandığında `university_requirement_maps` tablosundan gerekli belgeler checklist olarak oluşturulur ve `StudentInstitutionDocument` kayıtlarıyla karşılaştırılır.

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Model | `app/Models/StudentUniversityApplication.php` |
| Model | `app/Models/StudentInstitutionDocument.php` |
| Model | `app/Models/UniversityRequirementMap.php` |
| Config | `config/institution_document_catalog.php` |
| Migration | `database/migrations/*_create_student_university_applications_table.php` |
| Migration | `database/migrations/*_create_university_requirement_maps_table.php` |
| Seeder | `database/seeders/UniversityRequirementMapSeeder.php` |
| View (Senior) | `resources/views/senior/university-applications.blade.php` |
| View (Student) | `resources/views/student/university-applications.blade.php` |
| JS | `public/js/institution-documents.js` |
