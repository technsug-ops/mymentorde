# Telegram Chat Analysis Toolkit

MentorDE analiz alt sistemi — Türkçe-Almanca eğitim/vize toplulukluk forumlarından alınan Telegram ChatExport HTML dosyalarından **anonim** insight çıkarma araçları.

## Privacy

Tüm araçlar privacy-first çalışır:

- Sender isimleri 8-char SHA1 hash'lenir (`user_HASHX`)
- Mesaj metnindeki `@username`, e-posta, telefonlar regex temizlenir
- Hiçbir kişisel bilgi diske yazılmaz; CSV/JSON dump'ları anonimleştirilmiş hâlleriyle üretilir

## Araçlar

### 1. `parse_telegram.py` — Toplu parse + birleşik rapor

```
python analysis/parse_telegram.py
```

Üretir:
- `analysis/telegram_dump.json` — anonim mesaj havuzu
- `analysis/telegram_messages.csv` — aynı veri CSV
- `analysis/telegram_insights.md` — markdown özet rapor (top kullanıcı, kelime, soru örneklemi)

Input: `C:/Users/User/Downloads/Telegram Desktop/chat Export/ChatExport_*` klasörleri

### 2. `analytics_report.py` — Görsel analitik rapor

```
python analysis/analytics_report.py
```

Üretir:
- `analysis/charts/01_monthly_volume.png` ... `08_question_patterns.png` (8 chart)
- `analysis/telegram_analytics.md` — kapsamlı insights raporu

Önkoşul: `parse_telegram.py` çalıştırılmış, `telegram_dump.json` mevcut.

### 3. `extract_faq_seeds.py` — FAQ seed çıkarımı

```
python analysis/extract_faq_seeds.py
```

Konuya göre top 25 soruyu seçer, dedup + kalite skoru uygular.

Üretir:
- `analysis/faq_seeds.json` — yapısal liste
- `analysis/faq_seeds.md` — insan okur

### 4. `telegram_analyzer_app.py` — İnteraktif Streamlit UI ⭐

Drag-drop ZIP yükle, tarih/grup/konu filtrele, chart üret, custom topic regex tanımla.

```
pip install -r analysis/requirements.txt
streamlit run analysis/telegram_analyzer_app.py
```

Browser otomatik açılır → http://localhost:8501

**Windows'da `streamlit` komutu bulunamazsa** (PATH sorunu — `--user` install'da yaygın):

```
python -m streamlit run analysis/telegram_analyzer_app.py
```

Aynı işi yapar, Scripts/ klasörünü PATH'a eklemeye gerek kalmaz.

**Tab'ler:**
- 📈 Zaman Serisi — aylık hacim + top 6 konu trendi
- 🏷 Konu Analizi — frekans bar + konu-ay heatmap
- 🔥 Heatmap — gün × saat etkileşim + top 20 sender
- 🔍 Soru Arama — regex destekli filtre + 200 sonuç ön izleme
- 💾 Export — CSV/JSON/Markdown indirme

**Veri kaynak seçenekleri:**
- Mevcut `telegram_dump.json` (instant load)
- Disk klasör yolu (multi-folder tarama)
- ZIP drag-drop (multi-file destekli)

**Sidebar — Topic Editor:**
- 20 default topic regex (vize, dil, doktor_*, vb.)
- JSON formatında inline edit, "Uygula" → o anda chart yeniden hesaplanır
- "Default'lara dön" reset butonu

## Çıktıların gitignore durumu

`/analysis/*.json`, `/analysis/*.csv`, `/analysis/telegram_*.md`, `/analysis/faq_seeds.md` repo'ya **GİRMEZ** (gitignore). Sadece `*.py`, `*.txt`, `README.md`, `charts/*.png` commit'lenir.

## Yeni topic eklemek

Streamlit UI'da sidebar'da JSON editör var. Persistent yapmak için `extract_faq_seeds.py`'daki `TOPICS` dict'ine ve `parse_telegram.py`'daki `REL_PATTERNS` dict'ine de yansıt — sonra `python analysis/parse_telegram.py && python analysis/extract_faq_seeds.py` zincirini çalıştır.
