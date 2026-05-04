"""
Kapsamli analitik rapor — Telegram dump'tan grafik + insight uretir.

Onceden parse_telegram.py calistirilmis olmali (anonymize edilmis JSON
mevcut). Cikti:
  - analysis/charts/ (PNG)
  - analysis/telegram_analytics.md (genis rapor)

Hicbir gercek kullanici ismi rapora girmez (zaten JSON anonymized).
"""
import json
import re
from collections import Counter, defaultdict
from datetime import datetime
from pathlib import Path

import matplotlib

matplotlib.use("Agg")  # GUI yok, sadece dosya cikti
import matplotlib.pyplot as plt
import pandas as pd

ANALYSIS_DIR = Path(__file__).parent
CHARTS_DIR = ANALYSIS_DIR / "charts"
CHARTS_DIR.mkdir(exist_ok=True)

# Konu detection patterns
TOPICS = {
    "vize":         re.compile(r"\b(vize|visa|sperrkonto|sperrkont|bloke|elçilik|konsolosluk|videx)\b", re.I),
    "uni_assist":   re.compile(r"\b(uni[- ]?assist|uniassist|vpd|hzb)\b", re.I),
    "aps":          re.compile(r"\baps\b", re.I),
    "anmeldung":    re.compile(r"\b(anmeldung|anmelden|şehir kaydı|wohnsitz)\b", re.I),
    "dil":          re.compile(r"\b(testdaf|dsh|telc|goethe|c1|c2|b1|b2|almanca|deutsch|ielts|toefl)\b", re.I),
    "yurt":         re.compile(r"\b(yurt|wohnung|wg|wohnheim|kira|miete|kaution)\b", re.I),
    "sigorta":      re.compile(r"\b(sigorta|krankenkasse|krankenversicherung|tk |aok|barmer)\b", re.I),
    "burs":         re.compile(r"\b(burs|stipendium|daad|scholarship)\b", re.I),
    "para":         re.compile(r"\b(para|bütçe|euro|tl|maddi|harçlık|geçim)\b", re.I),
    "is":           re.compile(r"\b(iş|minijob|werkstudent|çalış|part time)\b", re.I),
    "sehir":        re.compile(r"\b(berlin|münih|munich|münchen|hamburg|frankfurt|köln|stuttgart|leipzig|dresden|heidelberg|freiburg)\b", re.I),
    "studienkolleg": re.compile(r"\b(studienkolleg|studkol|hazırlık|feststellungsprüfung)\b", re.I),
    "master":       re.compile(r"\b(master|yüksek lisans)\b", re.I),
    "ausbildung":   re.compile(r"\b(ausbildung|meslek eğitim)\b", re.I),
    "denklik":      re.compile(r"\b(denklik|tanıma|recognition|anerkennung|zeugnis)\b", re.I),
    "randevu":      re.compile(r"\b(randevu|appointment|termin|idata)\b", re.I),
}

# Pozitif sonuc patterns (kabul, vize geldi, vb.)
POSITIVE_OUTCOMES = re.compile(
    r"\b(vizem geldi|vize aldım|kabul aldım|kabul geldi|onaylandı|tebrikler|"
    r"hayırlı olsun|başardım|halloldu|tamamlandı|gelin geldi|geldim|"
    r"yerleştim|ev buldum|iş buldum|sigortam tamam|paramı aldım|"
    r"çıktı geldi|sevindim çok|mutluyum|yola çıkıyorum|inşallah|"
    r"hayırlısı oldu|teşekkürler|teşekkürederim|sayenizde|aldim|geldı)\b",
    re.I,
)

# Negatif sonuc patterns
NEGATIVE_OUTCOMES = re.compile(
    r"\b(reddedildim|reddedildi|red aldım|red geldi|olmadı|"
    r"başaramadım|kabul almadım|vize alamadım|sıkıntı yaşıyorum)\b",
    re.I,
)

# Soru patterns (clustering icin)
COMMON_QUESTION_STARTERS = [
    (r"\b(kim(in|de) var|var mı)\b", "var-mı / kimde-var"),
    (r"\b(nasıl|nasil)\b", "nasıl"),
    (r"\b(ne kadar|kaç para|ne kadar zaman|kaç gün|kaç ay)\b", "ne-kadar / süre"),
    (r"\b(hangi|kim)\b", "hangi / kim"),
    (r"\b(neden|niçin|niye)\b", "neden / niye"),
    (r"\b(ne zaman|zamanı geldi mi)\b", "ne-zaman"),
    (r"\b(yardımcı ol|yardım edebilir)\b", "yardım"),
    (r"\b(bilen|biliyor)\b", "bilen-var-mı"),
]


def main():
    print("[*] JSON yukleniyor...")
    json_path = ANALYSIS_DIR / "telegram_dump.json"
    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    print(f"[+] {len(data):,} mesaj")

    # ── DataFrame ──
    df = pd.DataFrame(data)
    df["dt"] = pd.to_datetime(df["date"].str[:19], format="%d.%m.%Y %H:%M:%S", errors="coerce")
    df = df.dropna(subset=["dt"])
    df["year"] = df["dt"].dt.year
    df["month"] = df["dt"].dt.to_period("M")
    df["dow"] = df["dt"].dt.dayofweek  # 0=Mon
    df["hour"] = df["dt"].dt.hour
    df["text_len"] = df["text"].str.len()

    # Topic detect
    for topic, pattern in TOPICS.items():
        df[f"t_{topic}"] = df["text"].str.contains(pattern, regex=True, na=False)
    df["positive"] = df["text"].str.contains(POSITIVE_OUTCOMES, regex=True, na=False)
    df["negative"] = df["text"].str.contains(NEGATIVE_OUTCOMES, regex=True, na=False)

    # ── 1. AYLIK MESAJ + KONU TIMELINE ──
    monthly = df.groupby("month").size().reset_index(name="count")
    fig, ax = plt.subplots(figsize=(14, 5))
    ax.bar(monthly["month"].astype(str), monthly["count"], color="#7e58bf")
    ax.set_title("Aylık mesaj hacmi (108.431 mesaj, 8 yıl)")
    ax.set_ylabel("Mesaj sayısı")
    plt.xticks(rotation=90, fontsize=7)
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "01_monthly_volume.png", dpi=110)
    plt.close()
    print("[+] 01_monthly_volume.png")

    # ── 2. KONU FREKANSI BAR ──
    topic_counts = {t: int(df[f"t_{t}"].sum()) for t in TOPICS}
    fig, ax = plt.subplots(figsize=(10, 6))
    sorted_topics = sorted(topic_counts.items(), key=lambda x: -x[1])
    names, counts = zip(*sorted_topics)
    bars = ax.barh(names, counts, color="#7e58bf")
    ax.set_title("Konu frekansı (mesaj başına 1 saymaya tabi)")
    ax.invert_yaxis()
    for bar, c in zip(bars, counts):
        ax.text(bar.get_width() + 50, bar.get_y() + bar.get_height() / 2, f"{c:,}",
                va="center", fontsize=9)
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "02_topic_frequency.png", dpi=110)
    plt.close()
    print("[+] 02_topic_frequency.png")

    # ── 3. KONU AYLIK TIMELINE (top 6) ──
    top6 = [t for t, _ in sorted_topics[:6]]
    monthly_topics = df.groupby("month")[[f"t_{t}" for t in top6]].sum().reset_index()
    fig, ax = plt.subplots(figsize=(14, 6))
    for t in top6:
        ax.plot(monthly_topics["month"].astype(str), monthly_topics[f"t_{t}"], label=t, linewidth=1.5)
    ax.set_title("Top 6 konu aylık trendi")
    ax.set_ylabel("Aylık mesaj")
    ax.legend(loc="upper left", ncol=2)
    plt.xticks(rotation=90, fontsize=7)
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "03_topic_timeline.png", dpi=110)
    plt.close()
    print("[+] 03_topic_timeline.png")

    # ── 4. ETKILESIM HEATMAP (gun × saat) ──
    heatmap = df.groupby(["dow", "hour"]).size().unstack(fill_value=0)
    fig, ax = plt.subplots(figsize=(14, 4))
    im = ax.imshow(heatmap, aspect="auto", cmap="Purples")
    ax.set_xticks(range(24))
    ax.set_xticklabels(range(24))
    ax.set_yticks(range(7))
    ax.set_yticklabels(["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Pzr"])
    ax.set_title("En çok etkileşim — gün × saat (UTC+1)")
    ax.set_xlabel("Saat")
    plt.colorbar(im, ax=ax, label="Mesaj sayısı")
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "04_engagement_heatmap.png", dpi=110)
    plt.close()
    print("[+] 04_engagement_heatmap.png")

    # ── 5. POZITIF/NEGATIF SONUC TIMELINE ──
    sentiment_monthly = df.groupby("month")[["positive", "negative"]].sum().reset_index()
    fig, ax = plt.subplots(figsize=(14, 5))
    ax.plot(sentiment_monthly["month"].astype(str), sentiment_monthly["positive"], color="#16a34a", label="Pozitif", linewidth=2)
    ax.plot(sentiment_monthly["month"].astype(str), sentiment_monthly["negative"], color="#dc2626", label="Negatif", linewidth=2)
    ax.set_title("Pozitif vs Negatif sonuç mesajları (aylık)")
    ax.set_ylabel("Mesaj sayısı")
    ax.legend()
    plt.xticks(rotation=90, fontsize=7)
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "05_sentiment_timeline.png", dpi=110)
    plt.close()
    print("[+] 05_sentiment_timeline.png")

    # ── 6. SORU/CEVAP ORANI (aylık) ──
    df["is_q"] = df["is_question"]
    qa_monthly = df.groupby("month").agg(
        total=("id", "count"),
        questions=("is_q", "sum"),
    ).reset_index()
    qa_monthly["q_ratio"] = qa_monthly["questions"] / qa_monthly["total"] * 100
    fig, ax = plt.subplots(figsize=(14, 5))
    ax.bar(qa_monthly["month"].astype(str), qa_monthly["q_ratio"], color="#a07ed9")
    ax.set_title("Soru oranı — toplam mesajların yüzde kaçı soru")
    ax.set_ylabel("% soru")
    ax.set_ylim(0, 50)
    plt.xticks(rotation=90, fontsize=7)
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "06_question_ratio.png", dpi=110)
    plt.close()
    print("[+] 06_question_ratio.png")

    # ── 7. EN AKTIF AY × KONU (heatmap detail) ──
    pivot = df.groupby([df["dt"].dt.year, df["dt"].dt.month])[
        [f"t_{t}" for t in top6]
    ].sum()
    pivot.index = [f"{y}-{m:02d}" for y, m in pivot.index]
    pivot.columns = top6
    fig, ax = plt.subplots(figsize=(10, max(6, len(pivot) * 0.15)))
    im = ax.imshow(pivot.values, aspect="auto", cmap="Purples")
    ax.set_xticks(range(len(top6)))
    ax.set_xticklabels(top6, rotation=45, ha="right")
    # Sadece her 3. yıl-ay göster
    yticks = list(range(0, len(pivot), 3))
    ax.set_yticks(yticks)
    ax.set_yticklabels([pivot.index[i] for i in yticks], fontsize=8)
    ax.set_title("Konu × Ay yoğunluk haritası")
    plt.colorbar(im, ax=ax, label="Aylık mesaj")
    plt.tight_layout()
    plt.savefig(CHARTS_DIR / "07_topic_month_heatmap.png", dpi=110)
    plt.close()
    print("[+] 07_topic_month_heatmap.png")

    # ── 8. SORU PATTERN DAĞILIMI ──
    questions = df[df["is_q"] & (df["text_len"] > 20) & ~df["is_short"]]
    pattern_counts = Counter()
    for _, row in questions.iterrows():
        for pat, label in COMMON_QUESTION_STARTERS:
            if re.search(pat, row["text"], re.I):
                pattern_counts[label] += 1
                break

    if pattern_counts:
        fig, ax = plt.subplots(figsize=(10, 5))
        labels, counts = zip(*pattern_counts.most_common())
        ax.bar(labels, counts, color="#7e58bf")
        ax.set_title("Soru kalıbı dağılımı")
        ax.set_ylabel("Soru sayısı")
        plt.xticks(rotation=20, ha="right")
        plt.tight_layout()
        plt.savefig(CHARTS_DIR / "08_question_patterns.png", dpi=110)
        plt.close()
        print("[+] 08_question_patterns.png")

    # ── 9. RAPOR (markdown) ──
    report_path = ANALYSIS_DIR / "telegram_analytics.md"
    total = len(df)
    total_users = df["sender"].nunique()
    total_questions = int(df["is_q"].sum())
    total_pos = int(df["positive"].sum())
    total_neg = int(df["negative"].sum())

    # En aktif gün-saat
    peak_dow = heatmap.sum(axis=1).idxmax()
    peak_hour = heatmap.sum(axis=0).idxmax()
    dow_names = ["Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi", "Pazar"]
    peak_combo = heatmap.stack().idxmax()

    # En kalabalık ay
    busiest_month = monthly.loc[monthly["count"].idxmax()]

    # Konu zirve ayları
    topic_peaks = {}
    for t in TOPICS:
        col = f"t_{t}"
        if df[col].sum() > 0:
            top_month = df.groupby("month")[col].sum().idxmax()
            topic_peaks[t] = (str(top_month), int(df.groupby("month")[col].sum().max()))

    # En çok yanıtlanan / hot questions: reply_to dolu mesajları kaynaktan say
    # (anonymize sonrası reply_to bos, geçici olarak text uzunluk + ? ile yaklaşık)
    # Alternative: response time avg
    # Skip: not feasible cleanly without reply chain

    with open(report_path, "w", encoding="utf-8") as f:
        f.write("# Telegram Grupları — Kapsamlı Analitik Rapor\n\n")
        f.write(f"_Üretildi: {datetime.now().strftime('%Y-%m-%d %H:%M')}_\n\n")
        f.write(f"**Tüm grafikler:** [`analysis/charts/`](charts/)\n\n")
        f.write("---\n\n")

        f.write("## 📊 Özet\n\n")
        f.write(f"- **Toplam mesaj:** {total:,}\n")
        f.write(f"- **Unique kullanıcı (anonim):** {total_users:,}\n")
        f.write(f"- **Toplam soru:** {total_questions:,} (%{total_questions/total*100:.1f})\n")
        f.write(f"- **Pozitif sonuç mesajları:** {total_pos:,}\n")
        f.write(f"- **Negatif sonuç mesajları:** {total_neg:,}\n")
        f.write(f"- **Pozitif/Negatif oranı:** {total_pos/max(1,total_neg):.1f}× (her negatif için {total_pos/max(1,total_neg):.1f} pozitif)\n\n")
        f.write("---\n\n")

        f.write("## 🕐 En çok etkileşim ne zaman?\n\n")
        f.write(f"- **En aktif gün:** {dow_names[peak_dow]}\n")
        f.write(f"- **En aktif saat:** {peak_hour}:00 (UTC+1)\n")
        f.write(f"- **Pik gün-saat kombinasyonu:** {dow_names[peak_combo[0]]} {peak_combo[1]}:00\n")
        f.write(f"- **En kalabalık ay:** {busiest_month['month']} ({busiest_month['count']:,} mesaj)\n\n")
        f.write(f"![Heatmap](charts/04_engagement_heatmap.png)\n\n")
        f.write("---\n\n")

        f.write("## 🏷 Konu zirve ayları (her konu en çok ne zaman tartışıldı?)\n\n")
        f.write("| Konu | Toplam | Pik ay | Pik mesaj sayısı |\n|---|---|---|---|\n")
        for t, count in sorted_topics:
            peak = topic_peaks.get(t, ("—", 0))
            f.write(f"| **{t}** | {count:,} | {peak[0]} | {peak[1]} |\n")
        f.write("\n")
        f.write(f"![Topic timeline](charts/03_topic_timeline.png)\n\n")
        f.write(f"![Topic-month heatmap](charts/07_topic_month_heatmap.png)\n\n")
        f.write("---\n\n")

        f.write("## ❓ Soru kalıpları — en sık sorulan ne tür sorular?\n\n")
        f.write("| Kalıp | Soru sayısı |\n|---|---|\n")
        for label, count in pattern_counts.most_common():
            f.write(f"| {label} | {count:,} |\n")
        f.write("\n")
        f.write(f"![Question patterns](charts/08_question_patterns.png)\n\n")
        f.write("---\n\n")

        f.write("## ✅ Pozitif sonuç mesajları (örnekler)\n\n")
        pos_samples = df[df["positive"] & (df["text_len"] > 30) & ~df["is_short"]].sample(min(20, total_pos))
        for _, row in pos_samples.iterrows():
            text = row["text"][:200].replace("\n", " ")
            f.write(f"- _({row['date'][:10]}):_ {text}\n")
        f.write("\n")
        f.write(f"![Sentiment timeline](charts/05_sentiment_timeline.png)\n\n")
        f.write("---\n\n")

        f.write("## 📅 Aylık mesaj hacmi\n\n")
        f.write(f"![Monthly volume](charts/01_monthly_volume.png)\n\n")
        f.write("**Top 5 en kalabalık ay:**\n\n")
        for _, row in monthly.nlargest(5, "count").iterrows():
            f.write(f"- {row['month']}: {row['count']:,} mesaj\n")
        f.write("\n---\n\n")

        f.write("## 📈 Konu frekansı dağılımı\n\n")
        f.write(f"![Topic frequency](charts/02_topic_frequency.png)\n\n")
        f.write("---\n\n")

        f.write("## 📊 Soru oranı (aylık)\n\n")
        f.write(f"![Question ratio](charts/06_question_ratio.png)\n\n")
        f.write(f"- **Ortalama soru oranı:** %{qa_monthly['q_ratio'].mean():.1f}\n")
        f.write(f"- **En soru-yoğun ay:** {qa_monthly.loc[qa_monthly['q_ratio'].idxmax(), 'month']} (%{qa_monthly['q_ratio'].max():.1f})\n\n")
        f.write("---\n\n")

        f.write("## 🎯 MentorDE için aksiyonlar\n\n")
        f.write("**Yeni rehber adayları (talep yüksek, sistem'de yok):**\n")
        for t in ["denklik", "is", "yurt", "sigorta", "anmeldung"]:
            if topic_counts.get(t, 0) > 200:
                f.write(f"- **{t}** rehberi — {topic_counts[t]:,} mesaj talep\n")
        f.write("\n**Sezonluk pazarlama:**\n")
        f.write(f"- En kalabalık dönem: {busiest_month['month']} → bu zaman zarfında ad spend artırılabilir\n")
        f.write(f"- En aktif zaman: {dow_names[peak_dow]} {peak_hour}:00 → drip mail / WhatsApp gönderim saati\n\n")
        f.write("**AI Labs / FAQ seed:**\n")
        f.write(f"- {total_questions:,} anonim soru → top 200 tema bazında kümele → AI Labs knowledge base\n")
        f.write(f"- En sık konular: {', '.join([t for t, _ in sorted_topics[:5]])}\n")

    print(f"\n[+] Rapor: {report_path}")
    print(f"[+] Grafikler: {CHARTS_DIR}/ ({len(list(CHARTS_DIR.glob('*.png')))} PNG)")


if __name__ == "__main__":
    main()
