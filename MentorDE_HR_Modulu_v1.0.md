# MentorDE — HR Modülü Teknik Dökümanı v1.0

> **Tarih:** 2026-04-01  
> **Stack:** Laravel 12 / PHP 8.4 / MySQL  
> **Kapsam:** Mevcut yapı analizi + geliştirme yol haritası

---

## 1. Mevcut Durum (Ne Var?)

### 1.1 Veritabanı Tabloları

| Tablo | Amaç | Önemli Kolonlar |
|-------|------|-----------------|
| `users` | Tüm sistem kullanıcıları (çalışan dahil) | `role`, `name`, `email`, `is_active`, `company_id` |
| `hr_person_profiles` | Çalışan ek profil bilgileri | `user_id`, `hire_date`, `position`, `department`, `annual_leave_quota`, `emergency_contact_name`, `emergency_contact_phone` |
| `hr_leave_requests` | İzin talepleri | `user_id`, `leave_type`, `start_date`, `end_date`, `status`, `deputy_user_id`, `manager_note` |
| `hr_leave_attachments` | İzin belgesi/link ekleri | `leave_request_id`, `file_path`, `url`, `label` |
| `hr_attendances` | Giriş/çıkış devamsızlık | `user_id`, `work_date`, `check_in`, `check_out`, `work_minutes`, `status` |
| `hr_certifications` | Sertifika takibi | `user_id`, `name`, `issuer`, `issued_at`, `expires_at`, `is_active` |
| `staff_kpi_targets` | Aylık KPI hedefleri | `user_id`, `period` (YYYY-MM), `task_target`, `ticket_target`, `hour_target` |

### 1.2 Roller & Çalışan Kapsamı

**Çalışan sayılan roller (`ALL_EMPLOYEE_ROLES`):**
```
manager, senior, system_admin, system_staff, operations_admin, operations_staff,
finance_admin, finance_staff, marketing_admin, marketing_staff, sales_admin, sales_staff
```

**Departman grupları:**
| Grup | Admin Rolü | Staff Rolü |
|------|-----------|-----------|
| System | system_admin | system_staff |
| Operations | operations_admin | operations_staff |
| Finance | finance_admin | finance_staff |
| Marketing | marketing_admin | marketing_staff |
| Sales | sales_admin | sales_staff |
| Advisory | senior | mentor |

### 1.3 Mevcut Route'lar

```
# Manager — HR Yönetimi
GET    /manager/hr                              → HrDashboardController@index
GET    /manager/hr/persons                      → HrPersonController@index
GET    /manager/hr/persons/{user}               → HrPersonController@card
POST   /manager/hr/persons/{user}/profile       → HrPersonController@updateProfile
POST   /manager/hr/persons/{user}/toggle        → HrPersonController@toggleActive

GET    /manager/hr/leaves                       → HrLeaveController@managerIndex
POST   /manager/hr/leaves                       → HrLeaveController@store         (manager izin talebi)
POST   /manager/hr/leaves/own                   → HrLeaveController@managerOwnStore
PATCH  /manager/hr/leaves/{leave}/approve       → HrLeaveController@approve
PATCH  /manager/hr/leaves/{leave}/reject        → HrLeaveController@reject

GET    /manager/hr/certifications               → HrCertificationController@index
POST   /manager/hr/certifications               → HrCertificationController@store
PUT    /manager/hr/certifications/{id}          → HrCertificationController@update
DELETE /manager/hr/certifications/{id}          → HrCertificationController@destroy

GET    /manager/hr/attendance                   → HrAttendanceController@managerIndex

# Çalışan (Staff) — Kendi işlemleri
GET    /hr/my/attendance                        → HrAttendanceController@myAttendance
POST   /hr/my/attendance/check-in               → HrAttendanceController@checkIn
POST   /hr/my/attendance/check-out              → HrAttendanceController@checkOut
GET    /hr/my/leaves                            → HrLeaveController@myIndex
POST   /hr/my/leaves                            → HrLeaveController@store
PATCH  /hr/my/leaves/{leave}/cancel             → HrLeaveController@cancel
```

### 1.4 Mevcut View Dosyaları

```
resources/views/manager/hr/
├── dashboard.blade.php          — KPI özet, bekleyen talepler, uyarılar
├── attendance.blade.php         — Devamsızlık yönetimi tablosu
├── persons/
│   ├── index.blade.php          — Çalışan listesi (filtreli)
│   └── card.blade.php           — Tekil çalışan detay kartı
├── leaves/
│   └── index.blade.php          — İzin talepleri yönetimi
└── certifications/
    └── index.blade.php          — Sertifika takip tablosu

resources/views/hr/my/
├── attendance.blade.php         — Çalışan: bugünkü durum + son 7 gün
└── leaves.blade.php             — Çalışan: kendi izin talepleri
```

---

## 2. Eksik Özellikler & Geliştirme Alanları

### 2.1 Kritik Eksikler (Şu An Çalışmayan/Olmayan)

| # | Eksik | Etki |
|---|-------|------|
| 1 | **Bordro / Maaş Sistemi** | Maaş hesaplama, kesinti, net/brüt yok | 
| 2 | **Performans Değerlendirme** | 360° değerlendirme, puan kartı yok |
| 3 | **İşe Alım Süreci** | Aday havuzu, iş ilanı, mülakat takibi yok |
| 4 | **Eğitim & Gelişim** | Eğitim planı, tamamlama takibi yok |
| 5 | **Disiplin / Uyarı** | Yazılı uyarı, tutanak sistemi yok |
| 6 | **Organizasyon Şeması** | Hiyerarşi görsel, raporlama zinciri yok |
| 7 | **Çalışan Self-Servis Paneli** | Profil güncelleme, belge indirme yok |
| 8 | **Bildirim Sistemi** | İzin onay/red bildirimi yok (email/in-app) |
| 9 | **Raporlama** | Excel export, devamsızlık raporu yok |
| 10 | **İzin Bakiyesi Takibi** | Kullanılan/kalan izin otomatik hesaplanmıyor |

### 2.2 Kısmi / İyileştirilmesi Gereken

| # | Alan | Mevcut Sorun |
|---|------|-------------|
| 1 | **Devamsızlık** | Sadece giriş/çıkış var; geç kalma, erken ayrılma tespiti yok |
| 2 | **KPI Hedefleri** | Hedef var ama gerçekleşen ile karşılaştırma dashboard'u yok |
| 3 | **İzin Onay Akışı** | Tek seviye onay; çok seviyeli onay zinciri yok |
| 4 | **Sertifika Uyarıları** | Dashboard'da görünüyor ama email uyarısı yok |
| 5 | **Çalışan Kartı** | Temel bilgi var; iş geçmişi, kariyer yolu yok |

---

## 3. Geliştirme Yol Haritası

### Faz 1 — Temel İyileştirmeler (Öncelikli)

#### F1.1 — İzin Bakiyesi Otomatik Hesaplama
**Amaç:** Yıllık izin kotasından kullanılanı düşerek kalan bakiyeyi her yerde göster.

**Yeni kolon:** `hr_person_profiles.annual_leave_used` (int, default 0)  
**Veya:** View/accessor ile `hr_leave_requests` üzerinden hesapla (daha temiz).

```php
// HrPersonProfile model'e eklenecek accessor
public function getRemainingLeaveAttribute(): int
{
    $used = $this->user->leaveRequests()
        ->where('leave_type', 'annual')
        ->where('status', 'approved')
        ->whereYear('start_date', now()->year)
        ->get()
        ->sum(fn($r) => $r->start_date->diffInWeekdays($r->end_date) + 1);

    return max(0, $this->annual_leave_quota - $used);
}
```

**Etkilenen dosyalar:**
- `app/Models/Hr/HrPersonProfile.php` — accessor ekle
- `resources/views/manager/hr/persons/card.blade.php` — kalan izin chip'i
- `resources/views/hr/my/leaves.blade.php` — bakiye göster

---

#### F1.2 — İzin Onay/Red Bildirimi
**Amaç:** Manager onaylayınca/reddedince çalışana in-app bildirim + email.

**Etkilenen dosyalar:**
- `app/Http/Controllers/Hr/HrLeaveController.php` → `approve()` / `reject()` içine `NotificationService::send()` ekle
- Mevcut `config/notification_templates.php`'ye 2 şablon ekle:
  - `hr_leave_approved` — "İzin talebiniz onaylandı"
  - `hr_leave_rejected` — "İzin talebiniz reddedildi: {reason}"

---

#### F1.3 — KPI Gerçekleşen vs Hedef Dashboard
**Amaç:** Aylık hedef ile gerçekleşen görevi/bileti karşılaştır.

**Mevcut:** `staff_kpi_targets` tablosunda `task_target`, `ticket_target`, `hour_target` var.  
**Eksik:** Gerçekleşen değerler için sorgu yok.

**Gerçekleşen veri kaynakları:**
- `tasks` tablosu → `assigned_to = user_id AND status = 'done' AND DATE_FORMAT(updated_at, '%Y-%m') = period`
- `tickets` tablosu → `assigned_to = user_id AND status = 'closed'`
- `hr_attendances` → `work_minutes` toplamı

**Yeni migration:** Gerekmez — hesaplama controller'da yapılır.

**Yeni view:** `resources/views/manager/hr/kpi-dashboard.blade.php`  
- Dönem seçici (ay/yıl)
- Her çalışan için: hedef vs gerçekleşen bar chart (CSS ile)
- Tamamlama yüzdesi badge (ok/warn/danger)

---

#### F1.4 — Devamsızlık Analitik Ekranı (Manager)
**Amaç:** Hangi çalışan kaç gün geç geldi, kaç gün erken ayrıldı, kaç gün devamsızlık yaptı — aylık özet.

**Yeni sorgu (`HrAttendanceController@managerReport`):**
```php
$summary = HrAttendance::query()
    ->selectRaw('user_id, 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
        AVG(work_minutes) as avg_minutes')
    ->whereBetween('work_date', [$startDate, $endDate])
    ->groupBy('user_id')
    ->with('user:id,name,email')
    ->get();
```

**Yeni route:** `GET /manager/hr/attendance/report`  
**Yeni view:** `resources/views/manager/hr/attendance-report.blade.php`

---

### Faz 2 — Yeni Modüller

#### F2.1 — Performans Değerlendirme Sistemi

**Yeni tablolar:**

```sql
-- Değerlendirme dönemleri
CREATE TABLE hr_review_periods (
    id, company_id, name, period_type (monthly/quarterly/annual),
    start_date, end_date, status (draft/active/closed),
    created_by, timestamps
);

-- Değerlendirme kayıtları
CREATE TABLE hr_performance_reviews (
    id, review_period_id, reviewee_user_id, reviewer_user_id,
    type (self/manager/peer/360),
    scores JSON,          -- {quality: 4, speed: 3, teamwork: 5, ...}
    overall_score DECIMAL(3,1),
    strengths TEXT, improvement_areas TEXT, goals TEXT,
    status (draft/submitted/acknowledged),
    submitted_at, acknowledged_at, timestamps
);
```

**Değerlendirme kriterleri (config):**
```php
// config/hr_review_criteria.php
return [
    'quality'    => ['label' => 'İş Kalitesi',      'weight' => 0.25],
    'speed'      => ['label' => 'Hız & Verimlilik',  'weight' => 0.20],
    'teamwork'   => ['label' => 'Ekip Çalışması',    'weight' => 0.20],
    'initiative' => ['label' => 'İnisiyatif',        'weight' => 0.15],
    'attendance' => ['label' => 'Devamsızlık',       'weight' => 0.10],
    'kpi'        => ['label' => 'KPI Hedefleri',     'weight' => 0.10],
];
```

**Route'lar:**
```
GET  /manager/hr/reviews                        → index (dönem listesi)
POST /manager/hr/reviews/periods                → yeni dönem aç
GET  /manager/hr/reviews/{period}               → dönem detayı, tüm çalışanlar
POST /manager/hr/reviews/{period}/{user}        → manager değerlendirmesi kaydet
GET  /hr/my/reviews                             → çalışan kendi değerlendirmeleri
POST /hr/my/reviews/{review}/acknowledge        → çalışan onayladı
```

---

#### F2.2 — Bordro / Maaş Modülü

**Yeni tablolar:**

```sql
-- Maaş profili (her çalışan için bir kayıt)
CREATE TABLE hr_salary_profiles (
    id, user_id, gross_salary DECIMAL(10,2),
    currency (TRY/EUR/USD), payment_day (1-28),
    bank_name, iban, tax_bracket,
    valid_from DATE, is_active BOOLEAN, timestamps
);

-- Aylık bordro
CREATE TABLE hr_payrolls (
    id, user_id, period (YYYY-MM),
    gross DECIMAL(10,2), deductions JSON,
    -- deductions: {income_tax, sgk_employee, sgk_employer, 
    --              advance_deduction, other_deductions}
    net DECIMAL(10,2), status (draft/approved/paid),
    payment_date DATE, payment_reference,
    approved_by, created_by, timestamps
);

-- Avans talepleri
CREATE TABLE hr_advance_requests (
    id, user_id, amount DECIMAL(10,2), reason TEXT,
    requested_date DATE, repayment_period (months),
    status (pending/approved/rejected/repaid),
    approved_by, manager_note, timestamps
);
```

**Route'lar:**
```
GET  /manager/hr/payroll                        → aylık bordro listesi
GET  /manager/hr/payroll/{period}               → dönem bordroları
POST /manager/hr/payroll/{period}/generate      → otomatik hesapla
POST /manager/hr/payroll/{period}/{user}/approve → onayla
GET  /manager/hr/payroll/{period}/{user}/slip   → bordro slip PDF
GET  /manager/hr/salary-profiles                → maaş profili yönetimi
POST /manager/hr/salary-profiles/{user}         → güncelle

GET  /hr/my/payroll                             → çalışan bordrolarım
GET  /hr/my/payroll/{period}/slip               → slip PDF indir
POST /hr/my/advance-requests                    → avans talebi
```

**Bordro Hesaplama Servisi:**
```php
// app/Services/Hr/PayrollCalculationService.php
class PayrollCalculationService
{
    public function calculate(User $user, string $period): array
    {
        $profile = $user->salaryProfile;
        $gross   = $profile->gross_salary;

        // Türkiye için örnek kesintiler
        $sgkEmployee   = $gross * 0.14;   // %14
        $incomeTax     = $this->calcIncomeTax($gross - $sgkEmployee);
        $stampTax      = $gross * 0.00759; // damga vergisi
        $sgkEmployer   = $gross * 0.205;   // işveren payı

        // İzinsiz devamsızlık kesintisi
        $absentDays    = $this->countAbsentDays($user, $period);
        $dailyGross    = $gross / 30;
        $absentDeduction = $absentDays * $dailyGross;

        // Avans kesintisi
        $advanceDeduction = $this->getActiveAdvanceDeduction($user, $period);

        $deductions = [
            'sgk_employee'      => round($sgkEmployee, 2),
            'income_tax'        => round($incomeTax, 2),
            'stamp_tax'         => round($stampTax, 2),
            'absent_deduction'  => round($absentDeduction, 2),
            'advance_deduction' => round($advanceDeduction, 2),
        ];

        $net = $gross - array_sum($deductions);

        return ['gross' => $gross, 'deductions' => $deductions, 'net' => round($net, 2)];
    }
}
```

---

#### F2.3 — İşe Alım (Recruitment) Modülü

**Yeni tablolar:**

```sql
-- İş ilanları
CREATE TABLE hr_job_postings (
    id, company_id, title, department, role_type,
    description TEXT, requirements TEXT,
    employment_type (full_time/part_time/internship/freelance),
    location, is_remote BOOLEAN,
    salary_min, salary_max, currency,
    status (draft/active/paused/closed),
    published_at, deadline_at, created_by, timestamps
);

-- Adaylar
CREATE TABLE hr_candidates (
    id, company_id, job_posting_id,
    first_name, last_name, email, phone,
    cv_path, cover_letter_path, portfolio_url, linkedin_url,
    source (linkedin/referral/website/agency/direct),
    status (applied/screening/interview/offer/hired/rejected),
    rating (1-5), notes TEXT,
    assigned_to (user_id — recruiter),
    timestamps
);

-- Mülakat kayıtları
CREATE TABLE hr_interviews (
    id, candidate_id, interviewer_user_id,
    scheduled_at DATETIME, duration_minutes,
    type (phone/video/onsite/technical),
    status (scheduled/completed/cancelled/no_show),
    score (1-10), feedback TEXT, recommendation (hire/reject/maybe),
    timestamps
);
```

**Route'lar:**
```
GET  /manager/hr/recruitment                    → dashboard (hunişi)
GET  /manager/hr/recruitment/postings           → ilan listesi
POST /manager/hr/recruitment/postings           → yeni ilan
GET  /manager/hr/recruitment/candidates         → aday havuzu
GET  /manager/hr/recruitment/candidates/{id}    → aday detayı
PATCH /manager/hr/recruitment/candidates/{id}/stage → aşama güncelle
POST /manager/hr/recruitment/interviews         → mülakat planla
```

---

#### F2.4 — Eğitim & Gelişim Modülü

**Yeni tablolar:**

```sql
-- Eğitim kataloğu
CREATE TABLE hr_trainings (
    id, company_id, title, description,
    category (technical/soft_skills/compliance/onboarding/other),
    delivery_type (online/classroom/workshop/self_study),
    duration_hours, provider, cost DECIMAL(8,2),
    is_mandatory BOOLEAN, mandatory_for_roles JSON,
    timestamps
);

-- Eğitim atamalar / tamamlamalar
CREATE TABLE hr_training_completions (
    id, training_id, user_id,
    assigned_at, due_date DATE,
    completed_at, score DECIMAL(5,2),
    status (assigned/in_progress/completed/overdue/waived),
    certificate_path, notes, timestamps
);
```

**Route'lar:**
```
GET  /manager/hr/trainings                      → eğitim kataloğu
POST /manager/hr/trainings                      → yeni eğitim
POST /manager/hr/trainings/{id}/assign          → çalışanlara ata
GET  /manager/hr/trainings/completions          → tamamlama raporu

GET  /hr/my/trainings                           → çalışan: atanan eğitimler
POST /hr/my/trainings/{id}/complete             → tamamlandı işaretle
```

---

#### F2.5 — Disiplin & Uyarı Sistemi

**Yeni tablo:**

```sql
CREATE TABLE hr_disciplinary_actions (
    id, user_id, type (verbal_warning/written_warning/final_warning/suspension/termination),
    incident_date DATE, description TEXT,
    improvement_plan TEXT, response_deadline DATE,
    employee_response TEXT, employee_response_at,
    issued_by (user_id), witnesses JSON,
    status (draft/issued/acknowledged/resolved/appealed),
    acknowledged_at, resolved_at, timestamps
);
```

**Route'lar:**
```
GET  /manager/hr/disciplinary                   → liste
POST /manager/hr/disciplinary                   → yeni kayıt
GET  /manager/hr/disciplinary/{id}              → detay
PATCH /manager/hr/disciplinary/{id}/issue       → çalışana bildir
GET  /hr/my/disciplinary                        → çalışan: kendi kayıtları
POST /hr/my/disciplinary/{id}/respond           → çalışan yanıtı
```

---

### Faz 3 — Raporlama & Export

#### F3.1 — HR Excel Raporları

**Raporlar:**
- Aylık devamsızlık özet raporu
- İzin kullanım raporu (dönemsel)
- KPI hedef vs gerçekleşen raporu
- Bordro özet raporu
- Çalışan bilgi formu (tekil / toplu)

**Teknik yaklaşım:** `maatwebsite/excel` paketi zaten projede varsa kullan; yoksa CSV export ile başla.

**Route:**
```
GET /manager/hr/reports/attendance?month=2026-03   → Excel indir
GET /manager/hr/reports/leaves?year=2026           → Excel indir
GET /manager/hr/reports/kpi?period=2026-03         → Excel indir
GET /manager/hr/reports/payroll?period=2026-03     → Excel indir
```

---

#### F3.2 — Organizasyon Şeması

**Yaklaşım:** `hr_person_profiles`'a `reports_to_user_id` kolonu ekle.  
Görsel: SVG/CSS tabanlı basit ağaç (D3.js gerektirmeden).

**Yeni migration:**
```php
$table->foreignId('reports_to_user_id')->nullable()->constrained('users');
```

**Route:** `GET /manager/hr/org-chart`

---

## 4. Dosya Düzeni (Hedef)

```
app/
├── Models/
│   └── Hr/
│       ├── HrPersonProfile.php         ✅ mevcut
│       ├── HrLeaveRequest.php          ✅ mevcut
│       ├── HrLeaveAttachment.php       ✅ mevcut
│       ├── HrAttendance.php            ✅ mevcut
│       ├── HrCertification.php         ✅ mevcut
│       ├── HrReviewPeriod.php          🔲 F2.1
│       ├── HrPerformanceReview.php     🔲 F2.1
│       ├── HrSalaryProfile.php         🔲 F2.2
│       ├── HrPayroll.php               🔲 F2.2
│       ├── HrAdvanceRequest.php        🔲 F2.2
│       ├── HrJobPosting.php            🔲 F2.3
│       ├── HrCandidate.php             🔲 F2.3
│       ├── HrInterview.php             🔲 F2.3
│       ├── HrTraining.php              🔲 F2.4
│       ├── HrTrainingCompletion.php    🔲 F2.4
│       └── HrDisciplinaryAction.php   🔲 F2.5
│
├── Http/Controllers/Hr/
│   ├── HrDashboardController.php       ✅ mevcut
│   ├── HrPersonController.php          ✅ mevcut
│   ├── HrLeaveController.php           ✅ mevcut
│   ├── HrAttendanceController.php      ✅ mevcut
│   ├── HrCertificationController.php   ✅ mevcut
│   ├── HrReviewController.php          🔲 F2.1
│   ├── HrPayrollController.php         🔲 F2.2
│   ├── HrRecruitmentController.php     🔲 F2.3
│   ├── HrTrainingController.php        🔲 F2.4
│   └── HrDisciplinaryController.php   🔲 F2.5
│
├── Services/Hr/
│   ├── PayrollCalculationService.php   🔲 F2.2
│   ├── LeaveBalanceService.php         🔲 F1.1
│   └── HrNotificationService.php      🔲 F1.2
│
resources/views/
├── manager/hr/
│   ├── dashboard.blade.php             ✅ mevcut
│   ├── attendance.blade.php            ✅ mevcut
│   ├── attendance-report.blade.php     🔲 F1.4
│   ├── kpi-dashboard.blade.php         🔲 F1.3
│   ├── org-chart.blade.php             🔲 F3.2
│   ├── persons/
│   │   ├── index.blade.php             ✅ mevcut
│   │   └── card.blade.php              ✅ mevcut
│   ├── leaves/
│   │   └── index.blade.php             ✅ mevcut
│   ├── certifications/
│   │   └── index.blade.php             ✅ mevcut
│   ├── reviews/
│   │   ├── index.blade.php             🔲 F2.1
│   │   └── detail.blade.php            🔲 F2.1
│   ├── payroll/
│   │   ├── index.blade.php             🔲 F2.2
│   │   └── slip.blade.php              🔲 F2.2
│   ├── recruitment/
│   │   ├── dashboard.blade.php         🔲 F2.3
│   │   ├── postings.blade.php          🔲 F2.3
│   │   └── candidate.blade.php         🔲 F2.3
│   ├── trainings/
│   │   └── index.blade.php             🔲 F2.4
│   └── disciplinary/
│       └── index.blade.php             🔲 F2.5
│
└── hr/my/
    ├── attendance.blade.php            ✅ mevcut
    ├── leaves.blade.php                ✅ mevcut
    ├── reviews.blade.php               🔲 F2.1
    ├── payroll.blade.php               🔲 F2.2
    ├── advance-requests.blade.php      🔲 F2.2
    ├── trainings.blade.php             🔲 F2.4
    └── disciplinary.blade.php         🔲 F2.5
```

---

## 5. Öncelik Sıralaması

| Sıra | Özellik | Faz | İş Günü Tahmini | Değer |
|------|---------|-----|-----------------|-------|
| 1 | İzin Bakiyesi Otomatik Hesaplama | F1.1 | 0.5 | Yüksek |
| 2 | İzin Onay/Red Bildirimi | F1.2 | 0.5 | Yüksek |
| 3 | KPI Gerçekleşen Dashboard | F1.3 | 1 | Orta |
| 4 | Devamsızlık Analitik Raporu | F1.4 | 1 | Orta |
| 5 | Performans Değerlendirme | F2.1 | 3 | Yüksek |
| 6 | Bordro Sistemi | F2.2 | 4 | Yüksek |
| 7 | Eğitim & Gelişim | F2.4 | 2 | Orta |
| 8 | Disiplin Sistemi | F2.5 | 1.5 | Orta |
| 9 | İşe Alım Modülü | F2.3 | 4 | Düşük* |
| 10 | HR Raporları (Excel) | F3.1 | 1.5 | Orta |
| 11 | Organizasyon Şeması | F3.2 | 1 | Düşük |

> *İşe alım modülü bağımsız bir büyük sistem — ayrı proje olarak ele alınabilir.

---

## 6. Teknik Notlar

### Mevcut Altyapı Kullanımı

- **Bildirimler:** `config/notification_templates.php` + `NotificationService` — HR için doğrudan kullanılabilir
- **Belge Upload:** Mevcut `ValidFileMagicBytes` middleware — izin belgesi yüklemede kullanılıyor
- **Audit Trail:** `AuditTrail` model + observer — bordro/disciplinary için kritik
- **RBAC:** `EnsureManagerRole` middleware — tüm `/manager/hr/*` route'larında aktif
- **SoftDeletes:** Tüm HR modelleri SoftDeletes kullanmalı (GDPR uyumu)

### Dikkat Edilmesi Gerekenler

1. **Bordro verileri çok hassas** — `AuditTrail` her değişikliği loglamalı
2. **İzin hesabı iş günü bazlı olmalı** — `Carbon::diffInWeekdays()` kullan; resmi tatiller için ayrı tablo gerekebilir
3. **Maaş profili değişince** eski bordroları etkilememeli — `hr_payrolls` snapshot alır, `hr_salary_profiles` geçmiş kaydı tutar
4. **GDPR:** Çalışan verisi export/silme için `GdprController` genişletilmeli
5. **Multi-company:** Tüm yeni tablolara `company_id` ekle + `BelongsToCompany` trait

---

## 7. Hızlı Başlangıç — Sıradaki Adımlar

Geliştirmeye başlamak için en hızlı kazanımlar:

```bash
# Faz 1.1 — İzin bakiyesi (0.5 gün)
# 1. HrPersonProfile'a getRemainingLeaveAttribute() accessor ekle
# 2. card.blade.php'ye kalan izin badge'i ekle
# 3. my/leaves.blade.php'ye bakiye satırı ekle

# Faz 1.2 — Bildirim (0.5 gün)
# 1. config/notification_templates.php'ye hr_leave_approved/rejected şablonları ekle
# 2. HrLeaveController@approve ve reject içine NotificationService::send() ekle

# Faz 1.3 — KPI Dashboard (1 gün)
# 1. HrPersonController'a kpiDashboard() metodu ekle
# 2. manager/hr/kpi-dashboard.blade.php oluştur
# 3. Route ekle: GET /manager/hr/kpi
```

---

*Döküman sahibi: Claude Code | Son güncelleme: 2026-04-01*
