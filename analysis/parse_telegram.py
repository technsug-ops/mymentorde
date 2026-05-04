"""
Telegram chat export (HTML) → structured data + insights.

Privacy-first: Tum kullanici isimleri 8-char SHA1 hash ile anonimlestirilir.
'Deleted Account' / numerik / cok kisa isimler sayim disi sayilir.
JSON ve CSV PII icermez, sadece istatistik + soru icerigi (zaten public grup
mesaji).

Input:  C:/Users/User/Downloads/Telegram Desktop/chat Export/ChatExport_*/messages*.html
Output: analysis/telegram_dump.json + telegram_messages.csv + telegram_insights.md

Usage: python analysis/parse_telegram.py
"""
import csv
import glob
import hashlib
import json
import os
import re
from collections import Counter
from datetime import datetime
from pathlib import Path

from bs4 import BeautifulSoup


def anon_sender(name: str) -> str:
    """Kullanici ismini 8-char hash'e cevir (PII koruma)."""
    name = (name or "").strip()
    if not name or name.lower() in {"deleted account", "(bilinmiyor)"}:
        return "anon_deleted"
    h = hashlib.sha1(name.encode("utf-8")).hexdigest()[:8]
    return f"user_{h}"


def strip_pii_from_text(text: str) -> str:
    """Mesaj icindeki @username, telefon, e-mail patterns'leri anonymize et."""
    if not text:
        return text
    # @username → @user
    text = re.sub(r"@\w+", "@user", text)
    # email → ***@***
    text = re.sub(r"\b[\w.+-]+@[\w-]+\.[\w.-]+\b", "***@***", text)
    # 10-15 digit telefon → ***
    text = re.sub(r"\+?\d[\d\s\-\(\)]{9,14}\d", "***", text)
    return text

EXPORT_ROOT = r"C:/Users/User/Downloads/Telegram Desktop/chat Export"
OUTPUT_DIR = Path(__file__).parent
# Tüm ChatExport_* klasörlerini tara; DataExport_* (1-on-1 DM'ler — privacy)
# bu MVP'de hariç tutuldu.
EXPORT_PATTERN = "ChatExport_*"

# Türkçe stopwords — yaygın bağlaç + zamir + ekler (kelime frekansı için filtre)
TR_STOP = set("""
ve veya ya da ama fakat ancak ile için de da te ta bir bu şu o ben sen biz siz
ki mi mu mı mı mı mu mı mu çok az daha en gibi kadar nasıl ne neden niçin
acaba acep belki çünkü dolayı eğer hatta hem hep her hiç işte yine yoksa
ben ki ne ya ön ki bü bu şu o
yani aslında hala mesela dahi bile sadece yalnız tek beri sonra önce şimdi
arkadaşlar merhaba selam herkese teşekkürler teşekkür rica ederim iyi
arkadaşlar var mı yok mu olur mu bilen biri varsa lütfen
oldu olmuş olur olmaz olacak olabilir olabilirim olabiliyor
yapmak yapan yapıp yaparken yapacak yapıyor yaptım yapacağım
edilir edilen ediliyor edilebilir edilmeli
biri kendi kendisi kendine kişi kişiler
gün ay yıl saat dakika
biraz pek çok az kez defa
""".split())

REL_PATTERNS = {
    "vize":        re.compile(r"\b(vize|visa|sperrkonto|sperrkont|bloke|elçilik|konsolosluk|visafragebogen|videx)\b", re.I),
    "uni_assist":  re.compile(r"\b(uni[- ]?assist|uniassist|vpd|hzb)\b", re.I),
    "aps":         re.compile(r"\baps\b", re.I),
    "anmeldung":   re.compile(r"\b(anmeldung|anmelden|kayıt yaptırma|şehir kaydı|wohnsitz)\b", re.I),
    "dil":         re.compile(r"\b(testdaf|dsh|telc|goethe|c1|c2|b1|b2|almanca|deutsch|ielts|toefl)\b", re.I),
    "yurt":        re.compile(r"\b(yurt|wohnung|wg|wohnheim|kira|miete|kaution)\b", re.I),
    "sigorta":     re.compile(r"\b(sigorta|krankenkasse|krankenversicherung|tk |aok|barmer|tk öğrenci)\b", re.I),
    "burs":        re.compile(r"\b(burs|stipendium|daad|scholarship)\b", re.I),
    "para":        re.compile(r"\b(para|bütçe|euro|tl|maddi|harçlık|geçim)\b", re.I),
    "is":          re.compile(r"\b(iş|minijob|werkstudent|çalış|part time|freelance)\b", re.I),
    "sehir":       re.compile(r"\b(berlin|münih|munich|münchen|hamburg|frankfurt|köln|cologne|stuttgart|leipzig|dresden|heidelberg|freiburg|tübingen|aachen)\b", re.I),
    "studienkolleg": re.compile(r"\b(studienkolleg|studkol|hazırlık|feststellungsprüfung)\b", re.I),
    "master":      re.compile(r"\b(master|yüksek lisans|master programı)\b", re.I),
    "ausbildung":  re.compile(r"\b(ausbildung|meslek eğitim)\b", re.I),
}

QUESTION_RE = re.compile(r"\?\s*$")
SHORT_RE = re.compile(r"^(merhaba|selam|teşekkür|sağol|tşk|👍|❤|✅|merhabalar|selamlar)$", re.I)


def extract_messages_from_file(path: str):
    """Bir HTML dosyasından mesajları çek."""
    with open(path, "r", encoding="utf-8") as f:
        soup = BeautifulSoup(f.read(), "lxml")

    messages = []
    last_sender = None  # 'joined' message'lar için
    for div in soup.find_all("div", class_="message"):
        # Service message'ları atla
        if "service" in div.get("class", []):
            continue

        # date
        date_div = div.find("div", class_="date")
        date_str = date_div.get("title", "").strip() if date_div else ""

        # sender (joined message'larda yok → önceki sender)
        from_div = div.find("div", class_="from_name")
        if from_div:
            sender = from_div.get_text(strip=True)
            last_sender = sender
        else:
            sender = last_sender or "(bilinmiyor)"

        # text
        text_div = div.find("div", class_="text")
        text = text_div.get_text(separator=" ", strip=True) if text_div else ""

        # reply
        reply_div = div.find("div", class_="reply_to")
        reply_to = reply_div.get_text(strip=True) if reply_div else ""

        msg_id = div.get("id", "").replace("message", "")

        # PII koruma: sender hash, text icindeki @username/email/phone strip
        clean_text = strip_pii_from_text(text)

        if clean_text:  # boş mesajları atla
            messages.append({
                "id":        msg_id,
                "date":      date_str,
                "sender":    anon_sender(sender),  # PII: name → user_HASH
                "text":      clean_text,           # PII stripped
                "reply_to":  "",                   # reply target da gercek isim icerir → strip
                "is_question": bool(QUESTION_RE.search(clean_text)),
                "is_short":  bool(SHORT_RE.match(clean_text.strip())),
            })

    return messages


def main():
    # Multi-folder tarama: ChatExport_* klasorlerinin hepsi
    folders = sorted(glob.glob(os.path.join(EXPORT_ROOT, EXPORT_PATTERN)))
    print(f"[*] {len(folders)} klasor bulundu")

    all_messages = []
    folder_stats = []
    for folder in folders:
        folder_name = os.path.basename(folder)
        files = sorted(glob.glob(os.path.join(folder, "messages*.html")))
        folder_msg_count = 0
        for f in files:
            msgs = extract_messages_from_file(f)
            # Her mesaja kaynak klasor etiketi ekle (grup ayrımı icin)
            for m in msgs:
                m["source"] = folder_name
            all_messages.extend(msgs)
            folder_msg_count += len(msgs)
        folder_stats.append((folder_name, len(files), folder_msg_count))
        print(f"    {folder_name}: {len(files)} HTML, {folder_msg_count} mesaj")

    print(f"\n[+] Toplam: {len(all_messages)} mesaj ({len(folders)} klasor)")

    # ── 1. JSON dump ──
    json_path = OUTPUT_DIR / "telegram_dump.json"
    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(all_messages, f, ensure_ascii=False, indent=2)
    print(f"[+] JSON: {json_path} ({json_path.stat().st_size // 1024} KB)")

    # ── 2. CSV ──
    csv_path = OUTPUT_DIR / "telegram_messages.csv"
    with open(csv_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=["id", "source", "date", "sender", "text", "reply_to", "is_question", "is_short"])
        writer.writeheader()
        writer.writerows(all_messages)
    print(f"[+] CSV: {csv_path} ({csv_path.stat().st_size // 1024} KB)")

    # ── 3. Stats ──
    senders = Counter(m["sender"] for m in all_messages)
    questions = [m for m in all_messages if m["is_question"] and not m["is_short"] and len(m["text"]) > 20]
    long_messages = [m for m in all_messages if not m["is_short"] and len(m["text"]) > 30]

    # Aylık zaman serisi
    by_month = Counter()
    date_re = re.compile(r"^(\d{2})\.(\d{2})\.(\d{4})")
    for m in all_messages:
        match = date_re.match(m["date"])
        if match:
            by_month[f"{match.group(3)}-{match.group(2)}"] += 1

    # Topic frequency
    topic_counts = Counter()
    for m in all_messages:
        for topic, pattern in REL_PATTERNS.items():
            if pattern.search(m["text"]):
                topic_counts[topic] += 1

    # Top kelimeler (stopword filtered)
    word_freq = Counter()
    word_re = re.compile(r"[a-zA-ZçğıöşüÇĞİÖŞÜ]+")
    for m in long_messages:
        for word in word_re.findall(m["text"].lower()):
            if len(word) >= 4 and word not in TR_STOP:
                word_freq[word] += 1

    # ── 4. Markdown rapor ──
    report = OUTPUT_DIR / "telegram_insights.md"
    with open(report, "w", encoding="utf-8") as f:
        f.write("# Telegram Grupları Insights — Birleşik Analiz\n\n")
        f.write(f"**Veri:** {len(all_messages):,} mesaj, {len(senders):,} unique kullanıcı, {len(folder_stats)} grup\n\n")
        if all_messages:
            f.write(f"**Tarih aralığı:** {all_messages[0]['date'][:10]} → {all_messages[-1]['date'][:10]}\n\n")
        f.write(f"**Soru sayısı (uzun + ?):** {len(questions):,}\n\n")
        f.write(f"**Uzun mesaj (>30 char, kısa selam değil):** {len(long_messages):,}\n\n")
        f.write("---\n\n")

        f.write("## 📂 Kaynak gruplar\n\n")
        f.write("| Grup | HTML | Mesaj |\n|---|---|---|\n")
        for name, files_cnt, msg_cnt in folder_stats:
            f.write(f"| {name} | {files_cnt} | {msg_cnt:,} |\n")
        f.write("\n---\n\n")

        f.write("## 📅 Aylık aktivite\n\n")
        f.write("| Ay | Mesaj |\n|---|---|\n")
        for month, cnt in sorted(by_month.items()):
            f.write(f"| {month} | {cnt} |\n")
        f.write("\n---\n\n")

        f.write("## 🏷 Konu frekansı (topic detection)\n\n")
        f.write("| Konu | Mesaj sayısı |\n|---|---|\n")
        for topic, cnt in topic_counts.most_common():
            f.write(f"| **{topic}** | {cnt:,} |\n")
        f.write("\n---\n\n")

        f.write("## 👤 Top 20 aktif kullanıcı\n\n")
        f.write("| Kullanıcı | Mesaj |\n|---|---|\n")
        for sender, cnt in senders.most_common(20):
            f.write(f"| {sender[:40]} | {cnt} |\n")
        f.write("\n---\n\n")

        f.write("## 🔑 Top 50 kelime (stopword filtered, ≥4 char)\n\n")
        f.write("| Kelime | Frekans |\n|---|---|\n")
        for word, cnt in word_freq.most_common(50):
            f.write(f"| {word} | {cnt} |\n")
        f.write("\n---\n\n")

        f.write("## ❓ Top 50 ÖRNEK soru (rastgele örneklem)\n\n")
        # Sample from middle (avoid intro/outro bias)
        sample = questions[len(questions)//4:3*len(questions)//4][::max(1, len(questions)//100)][:50]
        for i, m in enumerate(sample, 1):
            text = m['text'][:200].replace('\n', ' ')
            f.write(f"{i}. **{m['sender'][:25]}** ({m['date'][:10]}): _{text}_\n")
        f.write("\n")

    print(f"[+] Rapor: {report} ({report.stat().st_size // 1024} KB)")
    print(f"\n=== ÖZET ===")
    print(f"Toplam mesaj: {len(all_messages):,}")
    print(f"Unique kullanıcı: {len(senders):,}")
    print(f"Soru: {len(questions):,}")
    print(f"Top 5 konu: {dict(topic_counts.most_common(5))}")


if __name__ == "__main__":
    main()
