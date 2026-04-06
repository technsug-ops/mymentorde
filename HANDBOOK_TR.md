# MentorDE — Sistem El Kitabı (Türkçe)
**Versiyon 1.0 | 2026**

> Bu belge MentorDE ERP sisteminin tüm modüllerini, kullanıcı rollerini ve iş akışlarını kapsar.
> Teknik personel, yöneticiler ve son kullanıcılar için hazırlanmıştır.

---

## İçindekiler

1. [Sistem Genel Bakış](#1-sistem-genel-bakış)
2. [Kullanıcı Rolleri ve Yetkiler](#2-kullanıcı-rolleri-ve-yetkiler)
3. [Portal Kılavuzları](#3-portal-kılavuzları)
   - 3.1 Manager Portalı
   - 3.2 Senior / Danışman Portalı
   - 3.3 Guest Portalı
   - 3.4 Student Portalı
   - 3.5 Dealer Portalı
   - 3.6 Marketing Admin Portalı
4. [Modül Dokümantasyonu](#4-modül-dokümantasyonu)
5. [Entegrasyonlar](#5-entegrasyonlar)
6. [Admin Yönetimi](#6-admin-yönetimi)
7. [Güvenlik ve GDPR](#7-güvenlik-ve-gdpr)
8. [Sık Sorulan Sorular](#8-sık-sorulan-sorular)

---

## 1. Sistem Genel Bakış

### MentorDE Nedir?

MentorDE, Almanya'daki üniversitelere başvuru süreçlerini yöneten **çok portalı bir ERP sistemidir**. Danışmanlık firmaları, öğrenci adayları, senior danışmanlar, bayiler ve pazarlama ekipleri için ayrı ayrı özelleştirilmiş çalışma alanları sunar.

### Temel İş Akışı

```
Aday Başvurur (Guest)
       ↓
Senior Danışman Atanır
       ↓
Belgeler Toplanır & İncelenir
       ↓
Sözleşme İmzalanır
       ↓
Öğrenci Statüsüne Geçiş
       ↓
Üniversite Başvuruları Takip Edilir
       ↓
Kabul & Ödeme Takibi
```

### Teknoloji Altyapısı

| Bileşen | Teknoloji |
|---------|-----------|
| Backend | PHP 8.4 / Laravel 12 |
| Veritabanı | MySQL |
| Ödeme | Stripe (EUR) |
| Bildirim | WhatsApp (Meta Cloud API), E-posta |
| 2FA | Google Authenticator (TOTP) |
| Depolama | Yerel disk / AWS S3 |

---

## 2. Kullanıcı Rolleri ve Yetkiler

### Rol Hiyerarşisi

```
Manager / System Admin          → En yüksek yetki
├── Operations Admin/Staff      → Operasyon yönetimi
├── Finance Admin/Staff         → Finans yönetimi
├── Marketing Admin/Staff       → Pazarlama & CMS
├── Sales Admin/Staff           → Satış & lead yönetimi
Senior / Mentor                 → Danışmanlık
Dealer                          → Bayi ağı
Guest                           → Aday başvuru sahibi
Student                         → Kabul edilmiş öğrenci
```

### Rol Tablosu

| Rol | Kod | Portal | Açıklama |
|-----|-----|--------|----------|
| Yönetici | `manager` | Manager | Tam sistem erişimi |
| Sistem Admin | `system_admin` | Manager | Teknik yönetim |
| Operasyon Admin | `operations_admin` | Manager | Süreç yönetimi |
| Finans Admin | `finance_admin` | Manager | Ödeme & gelir |
| Sistem Personel | `system_staff` | Manager | Sınırlı teknik erişim |
| Operasyon Personel | `operations_staff` | Manager | Sınırlı operasyon |
| Finans Personel | `finance_staff` | Manager | Sınırlı finans |
| Senior Danışman | `senior` | Senior | Öğrenci takibi |
| Mentor | `mentor` | Senior | Senior ile aynı panel |
| Misafir Aday | `guest` | Guest | Başvuru & belgeler |
| Öğrenci | `student` | Student | Takip & ödeme |
| Bayi | `dealer` | Dealer | Referans & komisyon |
| Pazarlama Admin | `marketing_admin` | Marketing | Kampanya & içerik |
| Satış Admin | `sales_admin` | Marketing | Lead & satış |
| Pazarlama Personel | `marketing_staff` | Marketing | Sınırlı pazarlama |
| Satış Personel | `sales_staff` | Marketing | Sınırlı satış |

### Yetki Özet Tablosu

| İşlem | Manager | Senior | Guest | Student | Dealer | Marketing |
|-------|---------|--------|-------|---------|--------|-----------|
| Tüm kullanıcıları gör | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Guest atama | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Belge onayı | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Sözleşme oluştur | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Ödeme oluştur | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Öğrenci göster | ✅ | ✅* | ❌ | ❌ | ✅* | ❌ |
| Kendi profilini gör | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| İçerik yönet | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Komisyon gör | ✅ | ❌ | ❌ | ❌ | ✅* | ❌ |

> `*` Sadece kendi atanmış kayıtları

---

## 3. Portal Kılavuzları

---

### 3.1 Manager Portalı

**Erişim:** `/manager/dashboard`
**Roller:** manager, system_admin, operations_admin, finance_admin, system_staff, operations_staff, finance_staff

#### Dashboard

Giriş yapınca şunları görürsünüz:
- **KPI Kartları:** Toplam guest, aktif öğrenci, bu ay gelir, bekleyen belgeler
- **Son Aktiviteler:** Son başvurular, son belgeler, son biletler
- **Finans Özeti** (finance_admin/finance_staff için): Bu ay EUR tahsilatı, vadesi geçen faturalar, bekleyen ödemeler
- **Hızlı Linkler:** Yeni guest ekle, rapor al, GDPR dashboard

#### Öğrenci & Guest Yönetimi

| Menü | İşlev |
|------|-------|
| Başvurular | Tüm guest başvurularını listele, filtrele, detay gör |
| Öğrenciler | Aktif öğrencileri yönet |
| Öğrenci Ata | Guest → Öğrenci geçişini başlat |
| Senior Ata | Gues'e danışman ata |

**Guest Arama & Filtreleme:**
- Adı, e-posta, takip kodu ile ara
- Başvuru türüne göre filtrele (bachelor, master, dil kursu)
- Statüye göre filtrele (yeni, incelemede, kabul, red)
- Ülkeye, şehre, üniversiteye göre filtrele

#### Personel Yönetimi

`/manager/staff`

- Yeni personel ekle (ad, e-posta, rol, şifre)
- Personeli düzenle / devre dışı bırak
- Rol şablonu ata (izin seti)
- İzin geçmişini gör

#### Belge Yönetimi

- **Belge Kategorileri:** Kategorileri yönet, zorunlu/isteğe bağlı işaretle
- **Belge İnceleme:** Yüklenen belgeleri onayla / red et / yorum yaz
- **Belge Şablonları:** Standart belge setleri oluştur

#### Sözleşme Yönetimi

- Sözleşme şablonu oluştur (HTML editör)
- Sözleşme gönder → öğrenci imzalar
- İmzalı sözleşmeleri görüntüle / indir
- Sözleşme versiyonlarını takip et

#### Ödeme & Gelir

`/manager/payments`

- Fatura oluştur (öğrenci, miktar EUR, vade tarihi)
- Ödeme durumunu takip et (bekliyor / ödendi / vadesi geçti)
- Gelir milestone'larını yönet
- Dealer komisyonlarını hesapla
- Aylık / yıllık gelir raporu

#### Görev Yönetimi (Task Board)

`/manager/tasks`

- Görev oluştur, ata, öncelik belirle
- Kanban görünümü (Yapılacak / Devam / Tamamlandı)
- Tekrarlayan görev kuralları
- Zaman takibi (başlat / durdur)
- Görev bağımlılıkları

#### Ticket (Destek Talepleri)

`/manager/tickets`

- Tüm guest ve öğrenci taleplerini gör
- Departmana yönlendir (teknik, finans, operasyon)
- Öncelik belirle (düşük / normal / yüksek / acil)
- İç not ekle (müşteriye görünmez)
- SLA takibi

#### Raporlar

- **Snapshot Raporları:** Haftalık / aylık özet
- **Audit Raporu:** Kim ne yaptı, ne zaman
- **Güvenlik Anomalileri:** Şüpheli giriş, yetki ihlali denemeleri
- **GDPR Dashboard:** Veri silme talepleri, onay kayıtları

---

### 3.2 Senior / Danışman Portalı

**Erişim:** `/senior/dashboard`
**Roller:** senior, mentor

#### Dashboard

- Atanmış guest listesi (KPI: toplam, aktif, bu ay yeni)
- Pipeline durumu (kaçı hangi aşamada)
- Bugünkü görevler ve hatırlatmalar
- Okunmamış mesajlar
- Performans hedefleri (aylık kabul hedefi vs gerçekleşen)

#### Öğrenci 360° Görünümü

Her öğrenci için 9 sekme:

| Sekme | İçerik |
|-------|--------|
| Genel | Kişisel bilgiler, başvuru durumu |
| Belgeler | Yüklenen belgeler, onay durumu |
| Sözleşme | Sözleşme durumu, imza tarihi |
| Başvurular | Üniversite başvuru takibi |
| Ödemeler | Ödeme planı, gecikme durumu |
| Mesajlar | Öğrenci ile yazışma |
| Notlar | İç notlar (öğrenciye görünmez) |
| Zaman Çizelgesi | Tüm aktivite geçmişi |
| Kargo | Belge gönderim takibi |

#### Pipeline Kanban

`/senior/guest-pipeline`

6 sütunlu drag-drop Kanban board:
1. **Yeni Başvuru** — Atanmış, henüz görüşülmedi
2. **İlk Görüşme** — İlk temas yapıldı
3. **Belge Toplama** — Belgeler istendi / toplanıyor
4. **Değerlendirme** — Başvuru dosyası hazırlanıyor
5. **Başvuru Yapıldı** — Üniversiteye gönderildi
6. **Sonuç Bekleniyor** — Kabul / red bekleniyor

Kartı sütunlar arasında sürükle → durum otomatik güncellenir.

#### Toplu İnceleme (Batch Review)

`/senior/batch-review`

Klavye kısayollarıyla hızlı inceleme:
- `A` → Onayla
- `R` → Reddet
- `N` → Sonraki

#### Mesajlaşma

`/senior/messages`

- WhatsApp benzeri split-panel arayüz
- Öğrenci / guest ile gerçek zamanlı mesajlaşma
- Dosya paylaşımı
- Mesaj iletme, tepki ekleme, düzenleme
- 10 saniyede bir otomatik yenileme

#### Performans Takibi

- Aylık hedef vs gerçekleşen
- Lead skoru (8 faktör: belge tamamlama, yanıt süresi, vb.)
- Geçmiş performans grafikleri

---

### 3.3 Guest Portalı

**Erişim:** `/guest/dashboard`
**Roller:** guest

#### Başvuru Süreci

1. `/apply` — Genel başvuru formu doldur (kayıt olmadan)
2. Sistem otomatik kullanıcı oluşturur, şifre e-posta ile gönderilir
3. Giriş yap → `/guest/dashboard`

#### Dashboard

- **İlerleme Çubuğu:** Başvuru → Belgeler → Sözleşme → Öğrenci aşamaları
- **Sonraki Adım CTA:** Ne yapman gerektiğini gösterir
- **Atanmış Danışman:** Senior'ın adı, iletişim
- **Aktif Kampanyalar:** Güncel duyurular

#### Başvuru Formu

`/guest/registration/form`

- Kişisel bilgiler (ad, soyad, doğum tarihi, uyruk)
- Eğitim geçmişi
- Hedef üniversite / bölüm / şehir
- Dil sertifikaları
- Motivasyon notu
- Otomatik kayıt (her 30 saniyede bir)

#### Belge Yükleme

`/guest/registration/documents`

- Zorunlu belge listesi (danışman tarafından belirlenir)
- Belge başına yükleme (PDF, JPG, PNG — maks 10MB)
- Yükleme durumu: Bekleniyor / İncelemede / Onaylandı / Reddedildi
- Red edilirse danışman yorumu görünür

#### Sözleşme

`/guest/contract`

- Sözleşme metni görüntüle
- Dijital imzala (ad, tarih, onay kutusu)
- İmzalı PDF indir

#### Keşfet (Content Hub)

`/guest/discover`

- Blog yazıları, videolar, podcast'ler, sunumlar
- Kategori filtreleri: Öğrenci Hayatı / Kariyer / Kültür / İpuçları
- Şehir rehberleri (Berlin, Münih, Hamburg...)
- Üniversite rehberi

#### Destek Talebi

`/guest/tickets`

- Yeni talep aç (konu, açıklama, öncelik)
- Talep durumunu takip et
- Danışmanla yazışma

#### Maliyet Hesaplayıcı

`/guest/cost-calculator`

- Hedef şehre göre yaşam maliyeti tahmini
- Kira, yemek, ulaşım, eğlence kalemleri
- EUR cinsinden gösterim

---

### 3.4 Student Portalı

**Erişim:** `/student/dashboard`
**Roller:** student

> Guest başvurusu onaylandığında ve sözleşme imzalandığında hesap otomatik olarak Student statüsüne geçer.

#### Dashboard

- Ödeme planı özeti (ödenen / bekleyen / geciken)
- Üniversite başvuru durumu
- Son belgeler
- Danışman mesajları
- Döviz kuru (TRY chip)

#### Ödeme Takibi

`/student/payments`

- Fatura listesi (fatura no, tutar EUR, vade, durum)
- **💳 Öde butonu** — Stripe Checkout'a yönlendirir
- Ödeme geçmişi
- Milestone takibi (örn. kayıt ücreti, ilk dönem...)

#### Stripe ile Ödeme Süreci

1. "💳 Öde" butonuna tıkla
2. Stripe'ın güvenli ödeme sayfasına yönlendir
3. Kart bilgilerini gir
4. Ödeme onaylanır → sistem otomatik güncellenir
5. Fatura durumu "Ödendi" olarak işaretlenir

#### Belge Merkezi

`/student/documents`

- Danışmanın paylaştığı belgeleri gör / indir
- Kendi belgelerini yükle
- Kurum belgelerini takip et (öğrenci kartı, sigorta, vb.)

#### Üniversite Başvuru Takibi

`/student/university-applications`

- Başvurulan üniversiteler listesi
- Her başvurunun durumu (gönderildi / bekliyor / kabul / red)
- Başvuru tarihleri ve son günler

#### Kargo Takibi

`/student/shipments`

- Fiziksel belge gönderimlerini takip et
- Kargo takip numarası
- Tahmini teslimat tarihi

#### Randevu Takvimi

`/student/appointments`

- Danışmanla randevu görüntüle
- Harici takvim entegrasyonu (Google Calendar)
- Hatırlatma bildirimi

#### Keşfet (Content Hub)

`/student/discover`

- Guest discover ile aynı içerikler + öğrenciye özel
- Kariyer rehberleri, kültür içerikleri, şehir videoları

---

### 3.5 Dealer Portalı

**Erişim:** `/dealer/dashboard`
**Roller:** dealer

#### Dashboard

- Referans ettiği öğrenci sayısı
- Bu ay kazanılan komisyon (EUR)
- Ödeme milestone'ları
- Aktif öğrenci listesi

#### Referans Yönetimi

- Kendi üzerinden kaydolan öğrencileri gör
- Öğrenci başvuru durumu (izin verilen bilgiler)
- Belge durumu takibi

#### Komisyon Takibi

`/dealer/commissions`

- Her öğrenci için komisyon tutarı
- Ödeme zamanlaması (öğrenci ödeme yaptığında tetiklenir)
- Toplam kazanç özeti
- Komisyon dökümü indir

#### Milestone Takibi

`/dealer/milestones`

- Gelir milestone hedefleri
- Gerçekleşen vs hedef
- Bir sonraki milestone'a kalan miktar

#### Eğitim & Kaynaklar

- Sistem kullanım videoları
- Satış materyalleri
- Kampanya duyuruları

#### Sözleşme

`/dealer/contract`

- Bayi sözleşmesini görüntüle
- İmzalı sözleşme indir

---

### 3.6 Marketing Admin Portalı

**Erişim:** `/mktg-admin/dashboard`
**Roller:** marketing_admin, sales_admin, marketing_staff, sales_staff

#### Panel Modu Seçimi

Sidebar'da **"Panel Modu"** butonu:
- 📣 **Pazarlama Modu** — Kampanya, içerik, sosyal medya
- 💼 **Satış Modu** — Lead, CRM, satış takibi

Tüm 4 rol her iki paneli kullanabilir.

#### Pazarlama Modu

**Kampanyalar** (`/mktg-admin/campaigns`)
- Kampanya oluştur (e-posta, WhatsApp, sosyal medya)
- Hedef kitle tanımla (ülke, yaş, eğitim durumu)
- Zamanlanmış gönderim
- Açılma / tıklanma oranları

**CMS & İçerik** (`/mktg-admin/content`)
- Blog yazısı, video, podcast, sunum ekle
- Kategori: Öğrenci Hayatı, Kariyer, Kültür...
- Hedef kitle: Guest / Student / Hepsi
- Öne çıkan içerik işaretle
- Yayınla / taslak / arşivle

**E-posta Şablonları** (`/mktg-admin/email-templates`)
- HTML e-posta şablonu oluştur
- Değişkenler: `{first_name}`, `{tracking_code}`, `{university}` vb.
- Önizleme & test gönderimi

**Takip Linkleri** (`/mktg-admin/tracking-links`)
- UTM parametreli link oluştur
- Tıklanma, dönüşüm takibi

#### Satış Modu

**Lead Yönetimi** (`/mktg-admin/leads`)
- Gelen lead'leri listele (kaynak: web, referans, kampanya)
- Lead skoru görüntüle (0-100)
- Satış temsilcisine ata
- Durum güncelle (yeni → iletişim kuruldu → nitelikli → kaybedildi)

**Etkinlikler** (`/mktg-admin/events`)
- Üniversite fuarı, webinar, bilgilendirme toplantısı ekle
- Kayıt yönetimi
- Katılımcı listesi indir

---

## 4. Modül Dokümantasyonu

---

### 4.1 Kullanıcı Kimlik Doğrulama

#### Giriş

URL: `/login`

- E-posta ve şifre ile giriş
- 10 başarısız denemede hesap 30 dakika kilitlenir
- Kilitlenme süresi ekranda gösterilir

#### Şifre Sıfırlama

1. `/forgot-password` → e-posta gir
2. Gelen linkten şifreyi sıfırla (`/reset-password/{token}`)
3. Link 60 dakika geçerlidir

#### İki Faktörlü Doğrulama (2FA)

Manager, system_admin, operations_admin, finance_admin rolleri için **zorunludur**.

**İlk kurulum:**
1. Giriş yapınca otomatik `/2fa/setup` sayfasına yönlendirilirsin
2. Google Authenticator / Microsoft Authenticator / Authy uygulamasını aç
3. QR kodu tara (veya secret'ı manuel gir)
4. Uygulamada görünen 6 haneli kodu gir
5. Kurulum tamamlandı — bir daha sorulmaz (oturum başına)

**Sonraki girişlerde:**
- Şifreni girdikten sonra 6 haneli kod ekranı açılır
- Uygulamandan kodu yaz → giriş tamamlanır
- Kod 30 saniyede bir değişir

#### E-posta Doğrulama

Yeni hesap açıldığında sistem otomatik doğrulama yapır (tüm hesaplar admin tarafından oluşturulur). Manuel doğrulama gerekirse `/email/verify` sayfasından bağlantı yeniden gönderilebilir.

---

### 4.2 Belge Yönetimi

#### Belge Kategorileri

Manager tarafından oluşturulur. Her kategori:
- Ad ve açıklama
- Zorunlu / isteğe bağlı işareti
- Hedef kitle (guest / student)

#### Belge Yükleme (Guest/Student)

- Desteklenen formatlar: PDF, JPG, JPEG, PNG
- Maksimum dosya boyutu: 10 MB
- Her belge kategori bazında yüklenir
- Aynı kategoriye yeni dosya yükleme eskisinin üzerine yazar

#### Belge İnceleme (Senior/Manager)

| Durum | Açıklama |
|-------|----------|
| Bekleniyor | Henüz yüklenmedi |
| İncelemede | Yüklendi, onay bekleniyor |
| Onaylandı | ✅ Belge kabul edildi |
| Reddedildi | ❌ Gerekçe ile birlikte iade edildi |

Red edilince otomatik bildirim gider, gerekçe öğrenciye görünür.

#### Güvenlik

- Dosya tipi sunucu tarafında magic byte kontrolü ile doğrulanır
- Dosya adı hash'lenerek depolanır (orijinal ad görünmez)
- Sadece yetkili kullanıcılar belgeye erişebilir

---

### 4.3 Sözleşme Sistemi

#### Sözleşme Şablonu Oluşturma (Manager)

`/manager/contract-templates`

1. Şablon adı ve içerik gir (HTML editör)
2. Değişkenler kullan: `{student_name}`, `{date}`, `{package_name}` vb.
3. Aktif / pasif işaretle
4. Rol bazlı şablon atama (hangi guest türüne)

#### Sözleşme Gönderme

1. Guest/Öğrenci profilinden "Sözleşme Gönder"
2. Şablon seç → önizle → gönder
3. Guest/Öğrenciye bildirim gider

#### İmzalama (Guest/Student)

1. Portal'da bildirim / menü linki
2. Sözleşme metnini oku
3. Ad-soyadını yaz, tarihi onayla, kutucuğu işaretle
4. "İmzala" butonuna tıkla
5. İmzalı PDF otomatik oluşturulur

#### Versiyon Takibi

Her sözleşme değişikliği loglanır:
- Değişiklik tarihi
- Değiştiren kullanıcı
- Ne değişti (önceki → yeni)

---

### 4.4 Dahili Mesajlaşma

**Erişim:** `/im` (tüm iç roller)

#### Özellikler

- WhatsApp benzeri split-panel arayüz
- Konuşma listesi (sol) + mesaj alanı (sağ)
- 10 saniyede bir otomatik yenileme
- Dosya paylaşımı (belge gönderme)
- Mesaj iletme
- Emoji tepkisi
- Mesaj düzenleme (gönderildikten sonra)
- Sabitlenmiş mesajlar

#### Konuşma Oluşturma

1. Sol panelde "+" ikonuna tıkla
2. Kullanıcı ara (ad veya e-posta)
3. Konuşmayı başlat

---

### 4.5 Ticket (Destek Talebi) Sistemi

#### Guest / Student Talebi Açma

1. Portal'da "Destek Talebi" menüsü
2. Konu gir (örn. "Belge onay gecikmesi")
3. Açıklama yaz
4. Dosya ekle (isteğe bağlı)
5. Gönder

#### Otomatik Yönlendirme

Ticket konusuna göre otomatik departman atanır:
- Teknik sorunlar → Teknik Destek
- Ödeme sorunları → Finans
- Belge sorunları → Operasyon

#### Manager/Senior Tarafında

- Ticket listesi (filtreli: departman, öncelik, durum)
- İç not ekle (müşteriye görünmez)
- Başka bir personele devret
- Kapat / yeniden aç

#### Durum Akışı

```
Açık → İşlemde → Yanıt Bekleniyor → Çözüldü → Kapalı
```

---

### 4.6 Ödeme Sistemi

#### Fatura Oluşturma (Manager)

1. `Manager → Ödemeler → Yeni Fatura`
2. Öğrenci seç
3. Tutar (EUR), açıklama, vade tarihi gir
4. Kaydet → öğrenciye bildirim gider

#### Stripe ile Ödeme (Student)

1. Student portalında "💳 Öde" butonuna tıkla
2. Stripe'ın güvenli sayfasına yönlendirilir
3. Kart bilgilerini gir (Visa, Mastercard)
4. Ödeme onaylanır
5. Sistem otomatik güncellenir: `status=paid`, ödeme tarihi kaydedilir

#### Webhook Akışı

```
Stripe → POST /webhooks/stripe
       → Sistem imzayı doğrular
       → StudentPayment.status = "paid"
       → StudentPayment.paid_at = şimdiki zaman
```

#### Ödeme Durumları

| Durum | Açıklama |
|-------|----------|
| `pending` | Fatura oluşturuldu, ödeme bekleniyor |
| `paid` | Ödeme alındı |
| `overdue` | Vade tarihi geçti, ödeme yapılmadı |
| `cancelled` | Fatura iptal edildi |

#### Döviz Kuru

- EUR/TRY kuru günlük otomatik güncellenir
- Student panelinde TRY karşılığı bilgi amaçlı gösterilir
- Tüm işlemler EUR üzerinden yapılır

---

### 4.7 İçerik Hub (Keşfet)

**Guest erişimi:** `/guest/discover`
**Student erişimi:** `/student/discover`

#### İçerik Tipleri

| Tip | Gösterim |
|-----|---------|
| Blog | HTML metin, kapak görseli |
| Video | YouTube embed |
| Podcast | Spotify / SoundCloud embed |
| Sunum | Google Slides / Canva iframe |
| Deneyim | Kişisel hikaye kartı |
| Kariyer Rehberi | Bölümlü metin |
| Hızlı İpucu | Kompakt kart |

#### Kategoriler

- 🎓 Öğrenci Hayatı
- 🎭 Kültür & Eğlence
- 💼 Kariyer & Meslekler
- 💡 Pratik İpuçları
- 🏙 Şehir İçerikleri
- 🏛 Üniversite Rehberi
- ⭐ Başarı Hikayeleri

#### Arama

Arama kutusuna yaz → 500ms sonra sunucu taraflı arama → tüm sayfalardaki içerikler aranır.

#### Tekil İçerik Sayfası

`/guest/content/{slug}`

- Kapak görseli (tam genişlik, başlık overlay)
- Meta bilgi (tarih, kategori, görüntülenme sayısı)
- İçerik (tipe göre: metin / video / audio / iframe)
- İlgili içerikler (aynı kategori)

#### İçerik Yönetimi (Marketing Admin)

`/mktg-admin/content`

1. Yeni içerik ekle
2. Tip ve kategori seç
3. Türkçe içerik yaz
4. Video/podcast için URL gir
5. Etiketler ekle (şehir slug'ı eklenirse şehir sayfasında da görünür)
6. Hedef kitle: Guest / Student / Hepsi
7. Yayınla

---

### 4.8 Lead Yönetimi

#### Lead Skoru (0-100)

Her lead 8 faktöre göre otomatik skorlanır:
1. Belge tamamlama oranı
2. Form yanıt süresi
3. İletişim aktivitesi
4. Başvuru kalitesi
5. Hedef uyumu
6. Referans kaynağı
7. Bütçe uygunluğu
8. Zamanlama

Günlük gece 02:30'da otomatik yeniden hesaplanır.

#### Lead Kaynakları

- Web formu (`/apply`)
- Dealer referansı
- Kampanya UTM
- Manuel girişi (sales ekibi)

---

### 4.9 Senior Performans Takibi

Aylık snapshot alınır (1. gün, 03:30):

- Atanan guest sayısı
- Tamamlanan başvurular
- Kabul oranı
- Ortalama yanıt süresi
- Öğrenci memnuniyet skoru

Geçmiş performanslar grafik olarak görüntülenir.

---

### 4.10 Bildirim Sistemi

#### Kanallar

| Kanal | Ne Zaman |
|-------|----------|
| E-posta | Önemli işlemler (belge onayı, sözleşme, ödeme) |
| In-App | Portal içi bildirim (anlık) |
| WhatsApp | Hatırlatmalar, acil bildirimler |

#### Otomatik Bildirimler

| Olay | Alıcı | Kanal |
|------|-------|-------|
| Yeni başvuru | Manager, Senior | E-posta + In-App |
| Belge yüklendi | Senior | In-App |
| Belge onaylandı | Guest/Student | E-posta + In-App |
| Belge reddedildi | Guest/Student | E-posta + In-App |
| Sözleşme gönderildi | Guest/Student | E-posta |
| Sözleşme imzalandı | Manager, Senior | In-App |
| Ödeme yapıldı | Manager | In-App |
| Ödeme gecikti | Student | E-posta + WhatsApp |
| Ticket açıldı | İlgili departman | In-App |
| Ticket yanıtlandı | Talep sahibi | E-posta + In-App |

#### Zamanlanmış Bildirimler

- **Doğum günü dilekleri** — Sabah 09:00
- **Vade tarihi hatırlatması** — 3 gün önce
- **İnaktivite hatırlatması** — 7 gün aktivite yoksa
- **Senior hatırlatmaları** — Hafta içi sabah 08:30

---

### 4.11 Zamanlanmış Görevler (Cron)

Sunucuda `php artisan schedule:run` her dakika çalışmalıdır.

| Görev | Sıklık | İşlev |
|-------|--------|-------|
| `notifications:dispatch` | Her dakika | Bekleyen bildirimleri gönder |
| `gdpr:enforce-retention` | Günlük 03:00 | Eski verileri temizle |
| `currency:sync-rates` | Günlük 06:00 | EUR/TRY kuru güncelle |
| `leads:recalculate-scores` | Günlük 02:30 | Lead puanlarını yeniden hesapla |
| `senior:snapshot-performance` | Aylık 1. gün | Performans kaydı al |
| `security:anomaly-check` | Saatlik | Güvenlik anomalilerini tara |
| `archive:inactive-records` | Günlük 01:30 | 180+ gün inaktif guest'leri arşivle |
| `contract:send-reminders` | Günlük | İmzalanmamış sözleşme hatırlatması |
| `email:process-drip` | Günlük | Drip e-posta kampanyaları |

---

## 5. Entegrasyonlar

### 5.1 Stripe (Ödeme)

**Kurulum:**
```
STRIPE_KEY=pk_live_xxxx
STRIPE_SECRET=sk_live_xxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxx
```

**Stripe Dashboard'da yapılacak:**
1. Webhook endpoint ekle: `https://siteniz.com/webhooks/stripe`
2. Event seç: `checkout.session.completed`
3. Webhook secret'ı kopyala → `.env`'e yapıştır

**Test:**
Stripe CLI ile: `stripe listen --forward-to localhost:8000/webhooks/stripe`

---

### 5.2 WhatsApp (Meta Cloud API)

**Kurulum:**
```
WHATSAPP_PHONE_NUMBER_ID=xxxx
WHATSAPP_ACCESS_TOKEN=xxxx
WHATSAPP_VERIFY_TOKEN=mentorde_verify
WHATSAPP_API_VERSION=v19.0
```

**Meta Business Suite'te yapılacak:**
1. WhatsApp Business App oluştur
2. Phone Number ID ve Access Token al
3. Webhook doğrulama token'ını eşleştir

---

### 5.3 SMTP (E-posta)

**Kurulum:**
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org       # veya smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=postmaster@...
MAIL_PASSWORD=xxxx
MAIL_FROM_ADDRESS=noreply@mentorde.com
MAIL_FROM_NAME="MentorDE"
```

---

### 5.4 Queue Worker (Bildirimler)

Bildirimler ve WhatsApp mesajları için queue worker çalışmalıdır.

**Supervisor konfigürasyonu:**
```ini
[program:mentorde-worker]
command=php /var/www/mentorde/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
```

---

### 5.5 2FA (Google Authenticator)

Kullanıcının telefonunda kurulacak:
- Google Authenticator (iOS / Android)
- Microsoft Authenticator
- Authy

QR kodu tara veya 32 karakterlik secret'ı manuel gir.

---

## 6. Admin Yönetimi

### Yeni Personel Ekleme

`Manager → Personel → Yeni Ekle`

1. Ad-soyad, e-posta, rol seç
2. Şifre belirle (kullanıcı giriş yapınca değiştirebilir)
3. Kaydet → kullanıcı sisteme otomatik eklenir, e-posta doğrulanmış sayılır

### Yeni Senior Ekleme

`Manager → Danışmanlar → Yeni Senior`

1. Kişisel bilgiler
2. Senior kodu otomatik oluşturulur (örn. `SR-2026-0001`)
3. Kapasite (maksimum guest sayısı)
4. Otomatik atama açık/kapalı

### Yeni Dealer Ekleme

`Manager → Bayiler → Yeni Bayi`

1. Firma adı, yetkili kişi, e-posta
2. Bayi türü (bireysel / kurumsal)
3. Komisyon oranı
4. Bölge / ülke

### Sistem Ayarları

`Manager → Ayarlar`

- Şirket bilgileri
- E-posta şablonları
- Bildirim kuralları
- Dosya yükleme limitleri
- GDPR ayarları

---

## 7. Güvenlik ve GDPR

### Güvenlik Katmanları

| Katman | Açıklama |
|--------|----------|
| Oturum kilitleme | 10 başarısız giriş → 30 dk kilit |
| 2FA | Manager/Admin rolleri için zorunlu TOTP |
| Rate limiting | API endpoint başına istek limiti |
| Security headers | CSP, HSTS, X-Frame-Options |
| Dosya doğrulama | Magic byte kontrolü |
| Audit trail | Her kritik işlem loglanır |

### Güvenlik Anomali Tespiti

Saatlik tarama şunları kontrol eder:
- Aynı IP'den çok sayıda başarısız giriş
- Alışılmadık saatte yönetici erişimi
- Toplu veri erişimi

### GDPR

**Dashboard:** `Manager → GDPR Dashboard`

- Veri silme talepleri
- Veri dışa aktarma talepleri
- Onay kayıtları (cookie, KVKK)
- Otomatik temizleme (180 gün inaktif → arşivle → 365 gün → sil)

**Kullanıcı hakları:**
- **Veri erişimi:** Guest/Student kendi verilerini JSON olarak indirebilir (`/guest/gdpr/export`)
- **Veri silme:** Talep açabilir → Manager onaylar → sistem temizler

---

## 8. Sık Sorulan Sorular

### Hesap & Giriş

**S: Şifremi unuttum, ne yapmalıyım?**
C: Giriş sayfasında "Şifremi Unuttum" linkine tıklayın, kayıtlı e-posta adresinizi girin. 60 dakika geçerli bir sıfırlama bağlantısı e-postanıza gelecektir.

**S: 2FA telefonumu kaybettim, ne yapmalıyım?**
C: Danışmanınız veya Manager ile iletişime geçin. Manager panelinden 2FA sıfırlama işlemi yapılabilir. Kurtarma kodlarınızı güvenli bir yerde saklayın.

**S: Hesabım kilitlendi, ne yapmalıyım?**
C: 10 başarısız giriş denemesinden sonra hesap 30 dakika kilitlenir. Süre dolmasını bekleyin veya Manager'dan kilit açmasını isteyin.

### Başvuru & Belgeler

**S: Başvuru formunu yarıda bırakabilir miyim?**
C: Evet! Form her 30 saniyede otomatik kaydedilir. Taslak olarak bırakıp dilediğiniz zaman kaldığınız yerden devam edebilirsiniz.

**S: Belge yükleme neden başarısız oluyor?**
C: Desteklenen formatlar: PDF, JPG, PNG, DOCX, WEBP. Maksimum dosya boyutu: 10 MB. Dosya boyutunu ve formatını kontrol edin.

**S: Yüklediğim belge reddedildi, ne yapmalıyım?**
C: Belgelerim sayfasında reddedilen belgenin altında red sebebi gösterilir. Sebebe göre belgeyi düzeltip aynı kategoriden yeniden yükleyin.

**S: Hangi belgeler zorunlu?**
C: Başvuru tipinize göre zorunlu belgeler kırmızı çerçeve ile işaretlenir. "Sadece Zorunlu" filtresini kullanarak eksik belgeleri hızlıca görebilirsiniz.

### Sözleşme & Ödeme

**S: Sözleşme nasıl imzalanır?**
C: Danışmanınız sözleşme taslağını hazırlar, size onay için gönderir. Sözleşmem sayfasından inceleyip dijital olarak onaylayabilirsiniz.

**S: Stripe ödeme neden başarısız oldu?**
C: Kart limitini, son kullanma tarihini ve CVV'yi kontrol edin. 3D Secure doğrulamasını tamamlayın. Sorun devam ederse farklı kart deneyin veya banka havalesi yöntemini kullanın.

**S: Taksit seçeneği var mı?**
C: Ödeme planı danışmanınızla görüşülerek belirlenir. Banka havalesi ile taksitli ödeme yapılabilir.

### Danışman & İletişim

**S: Danışmanımla nasıl iletişime geçerim?**
C: Mesajlar sayfasından doğrudan mesaj gönderebilirsiniz. Ayrıca Destek Talebi oluşturarak bilet açabilirsiniz.

**S: Danışmanım ne kadar sürede cevap verir?**
C: Danışmanlar genellikle 24 saat içinde yanıt verir. Acil durumlar için Destek Talebi açmanız önerilir.

**S: Randevu nasıl alırım?**
C: Süreç Takvimi sayfasından müsait saatleri görebilir ve online randevu oluşturabilirsiniz.

### Almanya Süreci

**S: Bloke hesap (Sperrkonto) nedir?**
C: Almanya'da öğrenci vizesi için zorunlu olan banka hesabıdır. Aylık yaşam giderlerinizi karşılamak için belirli bir miktarı (yaklaşık 11.208€) bu hesaba yatırmanız gerekir. Bu para kaybolmaz, Almanya'ya geldiğinizde aylık olarak kullanırsınız.

**S: Vize başvurusu ne kadar sürer?**
C: Konsolosluğa göre değişir ancak genellikle 4-8 hafta sürer. Tüm belgelerin eksiksiz olması süreci hızlandırır. Konsolosluk kararını biz belirleyemeyiz.

**S: Dil seviyem yeterli değilse ne olur?**
C: Bazı üniversiteler dil kursu şartlı kabul verir. Studienkolleg programları da dil seviyesini geliştirmek için bir seçenektir. Danışmanınız size en uygun yolu önerecektir.

**S: Uni-Assist nedir?**
C: Uluslararası öğrenci başvurularını değerlendiren merkezi bir kurumdur. Birçok Alman üniversitesi başvuruları Uni-Assist üzerinden kabul eder. Başvuru ücreti yaklaşık 75€'dur.

### Teknik

**S: Sistemde hata görüyorum, ne yapmalıyım?**
C: Hatanın ekran görüntüsünü alın, Destek Talebi oluşturup ekran görüntüsünü ekleyin. Tarayıcı konsolunu (F12) kontrol etmeniz de faydalı olabilir.

**S: AI Asistan ne kadar doğru bilgi verir?**
C: AI Asistan genel rehberlik sağlar. Kesin bilgi ve güncel kurallar için mutlaka danışmanınıza danışın. AI önerileri hukuki taahhüt niteliği taşımaz.

**S: Mobil cihazdan kullanabilir miyim?**
C: Evet, MentorDE tamamen responsive tasarıma sahiptir. Tüm sayfalar mobil, tablet ve masaüstü cihazlarda düzgün çalışır.

---

*MentorDE System Handbook — Türkçe Versiyon 1.0*
*Son güncelleme: 2026*
