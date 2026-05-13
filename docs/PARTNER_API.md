# MentorDE Partner API — Entegrasyon Rehberi

**Versiyon:** v1
**Base URL:** `https://panel.mentorde.com/api/v1/partner`
**Auth:** `Authorization: Bearer mtde_live_…`
**Rate limit:** Saatte 1000 request (key bazında, varsayılan)

---

## 1. Kimlik Doğrulama

Her istekte `Authorization` header'ı zorunludur:

```http
GET /api/v1/partner/programs HTTP/1.1
Host: panel.mentorde.com
Authorization: Bearer mtde_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Hata yanıtları:**

| Kod | Sebep |
|-----|-------|
| `401 unauthorized` | Key eksik veya geçersiz |
| `403 forbidden` | Key devre dışı bırakılmış |
| `429 rate_limited` | Saatlik limit aşıldı (Retry-After header döner) |

---

## 2. Endpoint Listesi

### `GET /programs` — Program listesi

**Query parametreleri (tümü opsiyonel):**

| Param | Tip | Açıklama |
|-------|-----|----------|
| `q` | string | Genel arama (program adı, üni, açıklama, bölüm) |
| `university` | string | Tam üniversite adı (exact match) |
| `state` | string | Bundesland key: `bayern`, `berlin`, `nordrhein_westfalen` vb. (16 eyalet — bkz. `/states`) |
| `city` | string | Şehir adı — Münih/Munich gibi varyantlar otomatik birleşir |
| `degree` | string | `bachelor` \| `master` \| `phd` |
| `language` | string | `de` (Almanca) \| `en` (İngilizce) \| `both` (DE+EN karışık) |
| `subject` | string | Bölüm araması (Almanca/İngilizce: "Informatik", "Medizin", "Engineering") |
| `top_uni` | string | `top10` \| `top20` \| `top40` (Excellence Strategy + CHE/QS sıralaması) |
| `fields[]` | array | Bölüm kategorisi (multi-select OR) — bkz. `/study-fields` |
| `sort` | string | `relevance` \| `name` \| `recent` |
| `page` | int | Sayfa numarası (1 default) |
| `per_page` | int | Sayfa başına sonuç (max 100, default 30) |

**Örnek istek:**

```bash
curl -H "Authorization: Bearer mtde_live_xxx" \
  "https://panel.mentorde.com/api/v1/partner/programs?top_uni=top10&language=en&per_page=20"
```

**Örnek yanıt:**

```json
{
  "data": [
    {
      "id": "019ddbba-6297-72e5-bf3d-8dc0a3103b75",
      "university_id": "019ddbba-050f-735a-b562-92c17e3826f1",
      "university_name": "Rheinische Friedrich-Wilhelms-Universität Bonn",
      "course_name": "Agricultural and Food Economics",
      "degree_type": "master",
      "degree_specification": "Master of Science (M.Sc.)",
      "language": "en",
      "location": "Bonn",
      "duration_semesters": 4,
      "tuition_eur_per_semester": 0,
      "is_tuition_free": true,
      "study_fields": ["Business Management and Economics", "Agricultural and Forestry Sciences"],
      "referral_url": "https://panel.mentorde.com/uni-match?utm_source=partner&utm_medium=api&utm_campaign={your_slug}&pid=019ddbba-..."
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 407, "last_page": 21 },
  "links": { "self": "...", "first": "...", "last": "...", "next": "...", "prev": null }
}
```

> **referral_url**: Partner sitesinde kullanıcı "Detaylı öneri al" / "Başvur" tıkladığında bu URL'e yönlendir. UniMatch sihirbazımıza yönlenir, ilgili program ön-seçili olur, lead'i partner kampanyana atfederiz.

---

### `GET /programs/{uuid}` — Program detayı

```bash
curl -H "Authorization: Bearer mtde_live_xxx" \
  "https://panel.mentorde.com/api/v1/partner/programs/019ddbba-6297-72e5-bf3d-8dc0a3103b75"
```

Yanıt içinde tüm public alanlar: course_name, degree, language, location, tuition (3 alt alan), deadlines (summer/winter), admission (type + nc_value), study_fields, subjects, Türkçe açıklama + nitelik gereksinimleri + dil gereksinimleri + gerekli belgeler.

---

### `GET /universities` — Üniversite listesi

**Parametreler:** `q`, `state`, `city`, `top_uni`, `page`, `per_page`

```bash
curl -H "Authorization: Bearer mtde_live_xxx" \
  "https://panel.mentorde.com/api/v1/partner/universities?state=bayern&per_page=30"
```

---

### `GET /universities/{uuid}` — Üniversite detayı + programları

Üniversite bilgisi + o üniversiteye ait paginated programlar listesi tek yanıtta.

---

### `GET /states` — 16 Bundesländer

Eyalet keyleri + her birinde kaç program var (cache'li, 1 saat).

```json
{
  "data": [
    { "key": "bayern", "name": "Bayern", "program_count": 1886 },
    { "key": "nordrhein_westfalen", "name": "Nordrhein-Westfalen", "program_count": 2042 },
    ...
  ]
}
```

---

### `GET /study-fields` — Bölüm kategorileri

20 üst kategori (Engineering, Computer Science and IT, Medicine and Health vb.) + program sayıları.

---

### `GET /meta` — Hesap durumu

Kendi quota'nı ve kullanım istatistiğini görür:

```json
{
  "data": {
    "partner_name": "...",
    "partner_slug": "...",
    "rate_limit_per_hour": 1000,
    "total_requests": 14523,
    "last_used_at": "2026-05-13T20:15:17+00:00",
    "api_version": "v1"
  }
}
```

---

## 3. Kod Örnekleri

### PHP

```php
<?php
$apiKey = 'mtde_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$ch = curl_init('https://panel.mentorde.com/api/v1/partner/programs?language=en&per_page=20');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

foreach ($response['data'] as $program) {
    echo $program['course_name'] . ' @ ' . $program['university_name'] . "\n";
    // referral_url'i "Daha fazla bilgi" butonuna ata
}
```

### JavaScript (fetch)

```javascript
const API_KEY = 'mtde_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

const response = await fetch(
  'https://panel.mentorde.com/api/v1/partner/programs?language=en&per_page=20',
  { headers: { 'Authorization': 'Bearer ' + API_KEY } }
);
const data = await response.json();

data.data.forEach(program => {
  console.log(`${program.course_name} @ ${program.university_name}`);
  // <a href={program.referral_url}>Detaylı öneri al →</a>
});
```

### Python

```python
import requests

API_KEY = 'mtde_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
headers = {'Authorization': f'Bearer {API_KEY}'}

response = requests.get(
    'https://panel.mentorde.com/api/v1/partner/programs',
    params={'language': 'en', 'per_page': 20},
    headers=headers,
)
data = response.json()

for program in data['data']:
    print(f"{program['course_name']} @ {program['university_name']}")
```

---

## 4. Güvenlik Notları

- API anahtarını sunucu tarafında tut, **client-side JS'e gömme** (kullanıcılar görür)
- Anahtarı git/Github'a commit etme — `.env` dosyası kullan
- Anahtar sızdırıldıysa MentorDE'ye haber ver, **rotate** edelim (eski anahtar anında geçersiz olur)

---

## 5. Lead-gen Tracking

`referral_url` field'ı her program/üniversite yanıtında otomatik gelir:

```
https://panel.mentorde.com/uni-match?utm_source=partner&utm_medium=api&utm_campaign={your_slug}&pid={program_id}
```

Partner sitesinde "Detaylı öneri al" / "Başvur" butonunda bu URL'i kullan. MentorDE tarafında:
1. Kullanıcı UniMatch landing'e gelir
2. Sihirbazı başlatınca UTM bilgileri kaydedilir
3. Conversion gerçekleşirse partner kampanyana atfedilir
4. Manager dashboard'da per-partner conversion stats görünür

---

## 6. Destek

- E-posta: technsug@gmail.com
- Acil revoke / rotate isteği: yine yukarıdaki e-posta
- Status / kalan quota: `GET /meta` endpoint'i

---

**Bu doküman sürüm:** 2026-05-12
**API sürümü:** v1 (breaking change'lerde `/v2/` açılır, v1 desteği 12 ay sürer)
