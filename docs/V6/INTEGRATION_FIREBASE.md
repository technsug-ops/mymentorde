# Entegrasyon: Firebase

---

## Amaç

Firebase Storage (dosya yükleme/indirme) ve Firestore (NoSQL döküman veritabanı) entegrasyonu. Sistem genelinde medya dosyaları ve gerçek zamanlı veri depolama için kullanılır.

---

## FirebaseStorageService

**Dosya:** `app/Services/FirebaseStorageService.php`
**Kütüphane:** `google/cloud-storage`

### Metodlar

| Metod | Açıklama |
|-------|----------|
| `getStatus(): array` | Konfigürasyon durumunu döndürür (bucket, credentials, configured) |
| `uploadPublic(localPath, targetPath): string` | Dosyayı bucket'a yükler, public URL döner |

### uploadPublic()

```php
// Google Cloud Storage'a yükler
$url = $service->uploadPublic('/tmp/file.pdf', 'documents/student-123/cv.pdf');
// Döner: 'https://storage.googleapis.com/{bucket}/documents/student-123/cv.pdf'
```

`predefinedAcl: 'publicRead'` — herkese açık erişim.

---

## FirestoreRestService

**Dosya:** `app/Services/FirestoreRestService.php`

Firestore REST API üzerinden döküman okuma/yazma işlemleri. Google Cloud Firestore SDK yerine HTTP client kullanır (hafif bağımlılık).

---

## Konfigürasyon

**Dosya:** `config/firebase.php`

```php
return [
    'credentials'    => env('FIREBASE_CREDENTIALS', storage_path('firebase-credentials.json')),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'project_id'     => env('FIREBASE_PROJECT_ID'),
];
```

**`.env` değişkenleri:**

```
FIREBASE_CREDENTIALS=/path/to/firebase-credentials.json
FIREBASE_STORAGE_BUCKET=mentorde-app.appspot.com
FIREBASE_PROJECT_ID=mentorde-app
```

---

## API Endpoint'leri

**Dosya:** `app/Http/Controllers/Api/FirebaseStorageController.php`
**Dosya:** `app/Http/Controllers/Api/FirestoreController.php`

Manager ve Senior portali için Firebase işlemlerini API üzerinden tetikler.

---

## Güvenlik

- Service account credentials JSON dosyası `storage/` altında saklanır, `public/` altında değil
- `FIREBASE_CREDENTIALS` env var ile path konfigüre edilir
- `getStatus()` methodu credentials dosyasının varlığını kontrol eder
- `uploadPublic()` sadece explicit çağrıldığında dosya yükler — otomatik yükleme yok

---

## Mevcut Durum

Firebase entegrasyonu opsiyoneldir. `FIREBASE_STORAGE_BUCKET` veya `FIREBASE_CREDENTIALS` boşsa:
- `getStatus()` → `configured: false`
- `uploadPublic()` → `RuntimeException` fırlatır

Sistem Firebase olmadan da çalışır (MySQL + local storage fallback).

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Service (Storage) | `app/Services/FirebaseStorageService.php` |
| Service (Firestore) | `app/Services/FirestoreRestService.php` |
| Controller | `app/Http/Controllers/Api/FirebaseStorageController.php` |
| Controller | `app/Http/Controllers/Api/FirestoreController.php` |
| Config | `config/firebase.php` |
