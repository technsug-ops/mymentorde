"""
Telegram dump'tan AI Labs icin FAQ seed adaylari cikarir.

Her konu icin top N (default 25) en bilgi yogun soru segmentini secer.
Cikti:
  analysis/faq_seeds.json — yapisal liste (konu, ornek soru, freq)
  analysis/faq_seeds.md   — insan okur formati

Hicbir gercek isim/iletisim girmez (JSON zaten anonymized).
"""
import json
import re
from collections import defaultdict
from pathlib import Path

ANALYSIS_DIR = Path(__file__).parent

TOPICS = {
    "vize":         re.compile(r"\b(vize|visa|sperrkonto|sperrkont|bloke|elcilik|konsolosluk|videx)\b", re.I),
    "uni_assist":   re.compile(r"\b(uni[- ]?assist|uniassist|vpd|hzb)\b", re.I),
    "aps":          re.compile(r"\baps\b", re.I),
    "anmeldung":    re.compile(r"\b(anmeldung|anmelden|sehir kaydi|wohnsitz)\b", re.I),
    "dil":          re.compile(r"\b(testdaf|dsh|telc|goethe|c1|c2|b1|b2|almanca|deutsch|ielts|toefl)\b", re.I),
    "yurt":         re.compile(r"\b(yurt|wohnung|wg|wohnheim|kira|miete|kaution)\b", re.I),
    "sigorta":      re.compile(r"\b(sigorta|krankenkasse|krankenversicherung|tk |aok|barmer)\b", re.I),
    "burs":         re.compile(r"\b(burs|stipendium|daad|scholarship)\b", re.I),
    "para":         re.compile(r"\b(para|butce|euro|tl|maddi|harclik|gecim)\b", re.I),
    "is":           re.compile(r"\b(is|minijob|werkstudent|calis|part time)\b", re.I),
    "studienkolleg": re.compile(r"\b(studienkolleg|studkol|hazirlik|feststellungspruefung)\b", re.I),
    "master":       re.compile(r"\b(master|yuksek lisans)\b", re.I),
    "ausbildung":   re.compile(r"\b(ausbildung|meslek egitim)\b", re.I),
    "denklik":      re.compile(r"\b(denklik|taninma|recognition|anerkennung|zeugnis)\b", re.I),
    "randevu":      re.compile(r"\b(randevu|appointment|termin|idata)\b", re.I),
    "sehir":        re.compile(r"\b(berlin|munih|munich|munchen|hamburg|frankfurt|koln|stuttgart|leipzig|dresden|heidelberg|freiburg)\b", re.I),
    # ── Doktorluk alt-kategorileri (yalnızca ana doktor grubu için filtre) ──
    "doktor_approbation":     re.compile(r"\b(approbation|approbat|appro\b|approbasyon)\b", re.I),
    "doktor_fsp":             re.compile(r"\b(fsp|fachsprach|fachsprache|telc med|telc-med)\b", re.I),
    "doktor_hospitation_be":  re.compile(r"\b(hospitation|hospitasyon|hospitati|berufserlaubnis|be\b)\b", re.I),
    "doktor_kp_gutachten":    re.compile(r"\b(kenntnispr|gutachten|kp\b)\b", re.I),
    "doktor_fachgebiet":      re.compile(r"\b(asistan|fachgebiet|fachartz|fachartzt|cerrahi|psikiyatri|pediatri|kardio|nefro|onkoloji|jinekoloji|noroloji|patoloji|radyoloji|anestezi|dermatoloji|uroloji|ortopedi|aile hekimli|tukmos|stellenangebot|stellen)\b", re.I),
}

# Doktorluk topic'leri sadece doktorluk grubuna uygulanir (tek ana grup)
DOCTOR_GROUP_NAME = "ChatExport_2026-05-04"
DOCTOR_TOPICS = {"doktor_approbation", "doktor_fsp", "doktor_hospitation_be",
                 "doktor_kp_gutachten", "doktor_fachgebiet"}

# Soru kalitesi heuristikleri — coklu aliasli ve dolgu kelime az olan tercih
PROBABLY_LOW_QUALITY = re.compile(r"^(evet|hayir|tamam|tesekkurler|merhaba|selam|peki|anladim|olur|ok|asagi|yukari|hocam|merhabalar)\b", re.I)
NOISE_TOKENS = re.compile(r"(@user|@\*+|\*\*\*)", re.I)


def normalize_question(text: str) -> str:
    """Anonim isaretleri at, fazla bosluklari topla, ilk 280 char."""
    t = NOISE_TOKENS.sub("", text)
    t = re.sub(r"\s+", " ", t).strip()
    return t[:280]


def question_quality_score(text: str) -> float:
    """Yuksek = bilgi yogun, dusuk = dolgu/selam.
    Kriter: uzunluk + sorunun spesifikligi + dolgu yokluğu.
    """
    if PROBABLY_LOW_QUALITY.match(text):
        return 0.0
    length = len(text)
    if length < 30:
        return 0.5
    if length < 60:
        return 1.0
    if length < 200:
        return 2.0
    return 1.5  # cok uzun = belki ranta gidiyordur


def main():
    print("[*] JSON yukleniyor...")
    with open(ANALYSIS_DIR / "telegram_dump.json", "r", encoding="utf-8") as f:
        data = json.load(f)
    print(f"[+] {len(data):,} mesaj yuklendi")

    questions = [m for m in data if m.get("is_question")]
    print(f"[+] {len(questions):,} soru bulundu")

    # Konuya gore grupla
    topic_questions = defaultdict(list)
    for q in questions:
        text = q.get("text", "") or ""
        if len(text) < 20:
            continue
        source = q.get("source", "")
        for topic, pattern in TOPICS.items():
            # Doktorluk alt-kategorileri yalnizca ana doktorluk grubuna uygulanir
            if topic in DOCTOR_TOPICS and source != DOCTOR_GROUP_NAME:
                continue
            if pattern.search(text):
                normalized = normalize_question(text)
                if not normalized:
                    continue
                topic_questions[topic].append({
                    "text": normalized,
                    "date": q.get("date", "")[:10],
                    "score": question_quality_score(normalized),
                    "raw_len": len(text),
                })

    # Her konu icin: dedup + score'a gore sirala + top 25
    seeds = {}
    for topic, qs in topic_questions.items():
        # Basit dedup: ilk 80 karakteri ayni olanlari at
        seen = set()
        unique = []
        for q in qs:
            key = q["text"][:80].lower()
            if key in seen:
                continue
            seen.add(key)
            unique.append(q)
        unique.sort(key=lambda x: -x["score"])
        seeds[topic] = unique[:25]
        print(f"[+] {topic:14s} -> {len(qs):5d} soru, dedup sonrasi {len(unique):4d}, top25 alindi")

    # JSON cikti
    out_json = ANALYSIS_DIR / "faq_seeds.json"
    with open(out_json, "w", encoding="utf-8") as f:
        json.dump(seeds, f, ensure_ascii=False, indent=2)
    print(f"[+] {out_json.name} yazildi")

    # MD cikti — insan okur
    out_md = ANALYSIS_DIR / "faq_seeds.md"
    lines = [
        "# Telegram FAQ Seed Adaylari",
        "",
        "_AI Labs knowledge base + yeni rehber draft'lari icin tematik kume._",
        "",
        f"Toplam: {sum(len(qs) for qs in seeds.values())} soru, {len(seeds)} konu.",
        "",
        "Tum sorular anonim. Eposta/telefon/mention temizlenmis.",
        "",
        "---",
        "",
    ]
    # Konulari toplam soru sayisina gore sirala
    sorted_topics = sorted(seeds.items(), key=lambda x: -len(topic_questions.get(x[0], [])))
    for topic, qs in sorted_topics:
        if not qs:
            continue
        total = len(topic_questions[topic])
        lines.append(f"## {topic.upper()} ({total} toplam soru, top {len(qs)} secildi)")
        lines.append("")
        for i, q in enumerate(qs, 1):
            lines.append(f"{i}. _{q['date']}_  {q['text']}")
        lines.append("")
        lines.append("---")
        lines.append("")

    with open(out_md, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))
    print(f"[+] {out_md.name} yazildi")
    print(f"[+] Toplam {sum(len(qs) for qs in seeds.values())} FAQ adayi.")


if __name__ == "__main__":
    main()
