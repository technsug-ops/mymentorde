"""
Telegram Chat Analyzer — Streamlit interactive UI

Kullanim:
    pip install streamlit pandas matplotlib beautifulsoup4 lxml
    streamlit run analysis/telegram_analyzer_app.py

Browser otomatik acilir: http://localhost:8501

Ozellikler:
- Telegram ChatExport ZIP / klasor secimi (drag-drop)
- Otomatik PII anonim parsing
- Filtreler: tarih araligi, grup, konu, soru turu
- Gorseller: aylik aktivite, konu frekansi, gun-saat heatmap
- Soru arama (regex destekli)
- Custom topic regex tanimla
- Export: CSV / JSON / Markdown rapor
"""
import hashlib
import io
import json
import os
import re
import tempfile
import zipfile
from collections import Counter, defaultdict
from datetime import datetime
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import pandas as pd
import streamlit as st
from bs4 import BeautifulSoup

# ─────────────────────────────────────────────────────────────────────
# UI CONFIG
# ─────────────────────────────────────────────────────────────────────
st.set_page_config(
    page_title="Telegram Chat Analyzer — MentorDE",
    page_icon="📊",
    layout="wide",
    initial_sidebar_state="expanded",
)

ACCENT = "#7e58bf"
ACCENT_DARK = "#5b3a8f"

st.markdown(f"""
<style>
    .stApp {{ background: #faf7ff; }}
    h1, h2, h3 {{ font-family: 'Space Grotesk', system-ui, sans-serif; color: #1a0f2e; letter-spacing: -0.025em; }}
    .stSidebar {{ background: linear-gradient(180deg, {ACCENT}10, #fff); }}
    .stat-card {{
        background: #fff;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 2px 12px rgba(15,23,42,0.05);
        border-left: 4px solid {ACCENT};
    }}
    .stat-num {{ font-size: 28px; font-weight: 700; color: {ACCENT_DARK}; }}
    .stat-label {{ font-size: 12px; color: #5b4a7a; text-transform: uppercase; letter-spacing: 0.06em; }}
    .pill {{ display: inline-block; background: {ACCENT}15; color: {ACCENT_DARK};
            padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;
            margin: 2px; }}
</style>
""", unsafe_allow_html=True)

# ─────────────────────────────────────────────────────────────────────
# PARSING (parse_telegram.py'dan adapte)
# ─────────────────────────────────────────────────────────────────────
def anon_sender(name: str) -> str:
    name = (name or "").strip()
    if not name or name.lower() in {"deleted account", "(bilinmiyor)"}:
        return "anon_deleted"
    h = hashlib.sha1(name.encode("utf-8")).hexdigest()[:8]
    return f"user_{h}"

def strip_pii_from_text(text: str) -> str:
    if not text:
        return text
    text = re.sub(r"@\w+", "@user", text)
    text = re.sub(r"\b[\w.+-]+@[\w-]+\.[\w.-]+\b", "***@***", text)
    text = re.sub(r"\+?\d[\d\s\-\(\)]{9,14}\d", "***", text)
    return text

QUESTION_RE = re.compile(r"\?\s*$")
SHORT_RE = re.compile(r"^(merhaba|selam|teşekkür|sağol|tşk|👍|❤|✅|merhabalar|selamlar)$", re.I)


def parse_html_file(html_content: str, source_label: str) -> list:
    soup = BeautifulSoup(html_content, "lxml")
    messages = []
    last_sender = None
    for div in soup.find_all("div", class_="message"):
        if "service" in div.get("class", []):
            continue
        date_div = div.find("div", class_="date")
        date_str = date_div.get("title", "").strip() if date_div else ""
        from_div = div.find("div", class_="from_name")
        if from_div:
            sender = from_div.get_text(strip=True)
            last_sender = sender
        else:
            sender = last_sender or "(bilinmiyor)"
        text_div = div.find("div", class_="text")
        text = text_div.get_text(separator=" ", strip=True) if text_div else ""
        msg_id = div.get("id", "").replace("message", "")
        clean_text = strip_pii_from_text(text)
        if clean_text:
            messages.append({
                "id":        msg_id,
                "date":      date_str,
                "source":    source_label,
                "sender":    anon_sender(sender),
                "text":      clean_text,
                "is_question": bool(QUESTION_RE.search(clean_text)),
                "is_short":  bool(SHORT_RE.match(clean_text.strip())),
            })
    return messages


@st.cache_data(show_spinner=False)
def parse_zip_bytes(zip_bytes: bytes, label: str) -> list:
    """ZIP'den HTML dosyalarini cek + parse et."""
    msgs = []
    with tempfile.TemporaryDirectory() as tmpdir:
        zpath = Path(tmpdir) / "x.zip"
        zpath.write_bytes(zip_bytes)
        with zipfile.ZipFile(zpath, "r") as zf:
            zf.extractall(tmpdir)
        for root, _, files in os.walk(tmpdir):
            for fn in files:
                if fn.startswith("messages") and fn.endswith(".html"):
                    fp = os.path.join(root, fn)
                    with open(fp, "r", encoding="utf-8") as f:
                        msgs.extend(parse_html_file(f.read(), label))
    return msgs


@st.cache_data(show_spinner=False)
def parse_folder(folder_path: str) -> list:
    """Disk klasorundeki ChatExport_* alt klasorlerini parse et."""
    msgs = []
    if not os.path.isdir(folder_path):
        return msgs
    for entry in sorted(os.listdir(folder_path)):
        sub = os.path.join(folder_path, entry)
        if not os.path.isdir(sub):
            continue
        for fn in sorted(os.listdir(sub)):
            if fn.startswith("messages") and fn.endswith(".html"):
                fp = os.path.join(sub, fn)
                try:
                    with open(fp, "r", encoding="utf-8") as f:
                        msgs.extend(parse_html_file(f.read(), entry))
                except Exception as e:
                    st.warning(f"Hata: {fp} — {e}")
    return msgs


# ─────────────────────────────────────────────────────────────────────
# TOPIC CATALOG
# ─────────────────────────────────────────────────────────────────────
DEFAULT_TOPICS = {
    "vize":         r"\b(vize|visa|sperrkonto|sperrkont|bloke|elcilik|konsolosluk|videx)\b",
    "uni_assist":   r"\b(uni[- ]?assist|uniassist|vpd|hzb)\b",
    "aps":          r"\baps\b",
    "anmeldung":    r"\b(anmeldung|anmelden|sehir kaydi|wohnsitz)\b",
    "dil":          r"\b(testdaf|dsh|telc|goethe|c1|c2|b1|b2|almanca|deutsch|ielts|toefl)\b",
    "yurt":         r"\b(yurt|wohnung|wg|wohnheim|kira|miete|kaution)\b",
    "sigorta":      r"\b(sigorta|krankenkasse|krankenversicherung|tk |aok|barmer)\b",
    "burs":         r"\b(burs|stipendium|daad|scholarship)\b",
    "para":         r"\b(para|butce|euro|tl|maddi|harclik|gecim)\b",
    "is":           r"\b(is|minijob|werkstudent|calis|part time)\b",
    "studienkolleg": r"\b(studienkolleg|studkol|hazirlik|feststellungspruefung)\b",
    "master":       r"\b(master|yuksek lisans)\b",
    "ausbildung":   r"\b(ausbildung|meslek egitim)\b",
    "denklik":      r"\b(denklik|taninma|recognition|anerkennung|zeugnis)\b",
    "randevu":      r"\b(randevu|appointment|termin|idata)\b",
    "doktor_approbation":     r"\b(approbation|approbat|appro\b|approbasyon)\b",
    "doktor_fsp":             r"\b(fsp|fachsprach|fachsprache|telc med|telc-med)\b",
    "doktor_hospitation_be":  r"\b(hospitation|hospitasyon|hospitati|berufserlaubnis|be\b)\b",
    "doktor_kp_gutachten":    r"\b(kenntnispr|gutachten|kp\b)\b",
    "doktor_fachgebiet":      r"\b(asistan|fachgebiet|fachartz|cerrahi|psikiyatri|pediatri|kardio|nefro|onkoloji|jinekoloji|noroloji|patoloji|radyoloji|anestezi|dermatoloji|uroloji|ortopedi|aile hekimli|tukmos)\b",
}


# ─────────────────────────────────────────────────────────────────────
# DATAFRAME PREP
# ─────────────────────────────────────────────────────────────────────
@st.cache_data(show_spinner=False)
def build_dataframe(messages: list, topic_patterns: dict) -> pd.DataFrame:
    if not messages:
        return pd.DataFrame()
    df = pd.DataFrame(messages)
    df["dt"] = pd.to_datetime(df["date"].str[:19], format="%d.%m.%Y %H:%M:%S", errors="coerce")
    df = df.dropna(subset=["dt"])
    df["year"] = df["dt"].dt.year
    df["month"] = df["dt"].dt.to_period("M").astype(str)
    df["dow"]  = df["dt"].dt.dayofweek
    df["hour"] = df["dt"].dt.hour
    df["text_len"] = df["text"].str.len()

    # Topic flag columns
    for topic, pattern in topic_patterns.items():
        try:
            df[f"t_{topic}"] = df["text"].str.contains(pattern, regex=True, case=False, na=False)
        except re.error as e:
            st.error(f"Regex hatasi ({topic}): {e}")
            df[f"t_{topic}"] = False
    return df


# ─────────────────────────────────────────────────────────────────────
# SIDEBAR — DATA SOURCE
# ─────────────────────────────────────────────────────────────────────
st.sidebar.title("📊 Telegram Analyzer")
st.sidebar.caption("MentorDE — anonim chat analiz aracı")
st.sidebar.markdown("---")

source_mode = st.sidebar.radio(
    "Veri kaynagi",
    ["📦 Mevcut JSON dump (analysis/telegram_dump.json)",
     "📁 Klasör yolu (ChatExport_* alt klasörleri)",
     "📤 ZIP yükle (drag-drop)"],
    label_visibility="collapsed",
)

raw_messages = []

if source_mode.startswith("📦"):
    json_path = Path(__file__).parent / "telegram_dump.json"
    if json_path.exists():
        with open(json_path, "r", encoding="utf-8") as f:
            raw_messages = json.load(f)
        st.sidebar.success(f"✅ {len(raw_messages):,} mesaj yüklendi")
    else:
        st.sidebar.error(f"Dosya yok: {json_path}")
        st.sidebar.info("Önce `python analysis/parse_telegram.py` çalıştır.")

elif source_mode.startswith("📁"):
    folder = st.sidebar.text_input("Klasör yolu",
                                    value=r"C:/Users/User/Downloads/Telegram Desktop/chat Export")
    if st.sidebar.button("Klasörü tara"):
        with st.spinner("HTML dosyaları parse ediliyor..."):
            raw_messages = parse_folder(folder)
        st.sidebar.success(f"✅ {len(raw_messages):,} mesaj parse edildi")
        st.session_state["raw_messages"] = raw_messages
    if "raw_messages" in st.session_state and not raw_messages:
        raw_messages = st.session_state["raw_messages"]

else:  # ZIP upload
    uploaded = st.sidebar.file_uploader("Telegram export ZIP", type=["zip"], accept_multiple_files=True)
    if uploaded:
        with st.spinner("ZIP'ler parse ediliyor..."):
            for up in uploaded:
                msgs = parse_zip_bytes(up.read(), label=up.name.replace(".zip", ""))
                raw_messages.extend(msgs)
                st.sidebar.success(f"✅ {up.name}: {len(msgs):,} mesaj")
        st.session_state["raw_messages"] = raw_messages
    if "raw_messages" in st.session_state and not raw_messages:
        raw_messages = st.session_state["raw_messages"]


# ─────────────────────────────────────────────────────────────────────
# SIDEBAR — TOPIC EDITOR
# ─────────────────────────────────────────────────────────────────────
st.sidebar.markdown("---")
st.sidebar.subheader("🏷 Topic Pattern Editor")
st.sidebar.caption("Regex tanımlı, case-insensitive. Yeni topic eklemek için JSON formatına yapıştır.")

if "topic_patterns" not in st.session_state:
    st.session_state["topic_patterns"] = DEFAULT_TOPICS.copy()

topics_text = st.sidebar.text_area(
    "Topic regex map",
    value=json.dumps(st.session_state["topic_patterns"], ensure_ascii=False, indent=2),
    height=300,
    label_visibility="collapsed",
)
if st.sidebar.button("Topic'leri uygula"):
    try:
        new_topics = json.loads(topics_text)
        st.session_state["topic_patterns"] = new_topics
        st.sidebar.success(f"✅ {len(new_topics)} topic uygulandı")
    except json.JSONDecodeError as e:
        st.sidebar.error(f"JSON hatası: {e}")

if st.sidebar.button("Defaultlara dön"):
    st.session_state["topic_patterns"] = DEFAULT_TOPICS.copy()
    st.rerun()

topic_patterns = st.session_state["topic_patterns"]


# ─────────────────────────────────────────────────────────────────────
# MAIN AREA
# ─────────────────────────────────────────────────────────────────────
st.title("📊 Telegram Chat Analyzer")
st.caption("Anonim chat analiz aracı — privacy-first parse + interactive filter + chart export")

if not raw_messages:
    st.info("👈 Sol kenardan veri kaynağı seç ve yükle.")
    st.markdown("""
### Kullanım

1. **Mevcut JSON dump** — `analysis/telegram_dump.json` (varsa) doğrudan açar
2. **Klasör yolu** — Telegram'ın oluşturduğu `ChatExport_*` klasörlerinin bulunduğu üst klasör
3. **ZIP yükle** — Tek tek ChatExport_*.zip dosyalarını drag-drop

### Privacy notu

Tüm gönderici isimleri SHA1 hash'lenir (`user_HASHX`). Mesaj içindeki `@username`, e-posta ve telefonlar regex temizlenir. Hiçbir kişisel bilgi disk'e yazılmaz.

### Topic editor

Sol panelden kendi konularını tanımla — regex desteği var. Tıbbi/hukuki/teknik domain için özel topic'ler ekleyebilirsin.
    """)
    st.stop()


df = build_dataframe(raw_messages, topic_patterns)
if df.empty:
    st.error("Mesajlar tarih parse edilemedi. Veri kaynağını kontrol et.")
    st.stop()

# Filters
st.subheader("🔧 Filtreler")
col1, col2, col3, col4 = st.columns(4)

with col1:
    min_dt, max_dt = df["dt"].min(), df["dt"].max()
    date_range = st.date_input("Tarih aralığı",
                                value=(min_dt.date(), max_dt.date()),
                                min_value=min_dt.date(),
                                max_value=max_dt.date())
    if isinstance(date_range, tuple) and len(date_range) == 2:
        df = df[(df["dt"].dt.date >= date_range[0]) & (df["dt"].dt.date <= date_range[1])]

with col2:
    sources = sorted(df["source"].unique())
    selected_sources = st.multiselect("Kaynak grup", sources, default=sources)
    df = df[df["source"].isin(selected_sources)]

with col3:
    only_questions = st.checkbox("Sadece sorular (?)", value=False)
    if only_questions:
        df = df[df["is_question"]]
    skip_short = st.checkbox("Kısa mesajları atla", value=True)
    if skip_short:
        df = df[~df["is_short"] & (df["text_len"] >= 30)]

with col4:
    topic_filter = st.selectbox("Konu filtresi", ["(hepsi)"] + list(topic_patterns.keys()))
    if topic_filter != "(hepsi)":
        df = df[df[f"t_{topic_filter}"]]

# Stats
st.markdown("---")
c1, c2, c3, c4, c5 = st.columns(5)
with c1: st.markdown(f'<div class="stat-card"><div class="stat-num">{len(df):,}</div><div class="stat-label">Mesaj</div></div>', unsafe_allow_html=True)
with c2: st.markdown(f'<div class="stat-card"><div class="stat-num">{df["sender"].nunique():,}</div><div class="stat-label">Anonim Sender</div></div>', unsafe_allow_html=True)
with c3: st.markdown(f'<div class="stat-card"><div class="stat-num">{int(df["is_question"].sum()):,}</div><div class="stat-label">Soru</div></div>', unsafe_allow_html=True)
with c4: st.markdown(f'<div class="stat-card"><div class="stat-num">{df["source"].nunique()}</div><div class="stat-label">Grup</div></div>', unsafe_allow_html=True)
with c5: st.markdown(f'<div class="stat-card"><div class="stat-num">{int(df["text_len"].mean()) if len(df) else 0}</div><div class="stat-label">Ort. Uzunluk</div></div>', unsafe_allow_html=True)

# Tabs
tab1, tab2, tab3, tab4, tab5 = st.tabs(["📈 Zaman Serisi", "🏷 Konu Analizi", "🔥 Heatmap", "🔍 Soru Arama", "💾 Export"])

with tab1:
    st.subheader("Aylık mesaj hacmi")
    monthly = df.groupby("month").size().reset_index(name="count")
    fig, ax = plt.subplots(figsize=(12, 4))
    ax.bar(monthly["month"], monthly["count"], color=ACCENT)
    ax.set_xticklabels(monthly["month"], rotation=90, fontsize=7)
    ax.set_ylabel("Mesaj")
    plt.tight_layout()
    st.pyplot(fig)
    plt.close()

    # Top 6 topic timeline
    st.subheader("Top 6 konu — aylık trend")
    topic_cols = [c for c in df.columns if c.startswith("t_")]
    topic_totals = {c[2:]: int(df[c].sum()) for c in topic_cols}
    top6 = [t for t, _ in sorted(topic_totals.items(), key=lambda x: -x[1])[:6] if topic_totals[t] > 0]
    if top6:
        monthly_topics = df.groupby("month")[[f"t_{t}" for t in top6]].sum().reset_index()
        fig, ax = plt.subplots(figsize=(12, 5))
        for t in top6:
            ax.plot(monthly_topics["month"], monthly_topics[f"t_{t}"], label=t, linewidth=1.5)
        ax.set_xticklabels(monthly_topics["month"], rotation=90, fontsize=7)
        ax.legend(loc="upper left", ncol=2)
        plt.tight_layout()
        st.pyplot(fig)
        plt.close()

with tab2:
    st.subheader("Konu frekansı")
    topic_cols = [c for c in df.columns if c.startswith("t_")]
    topic_totals = {c[2:]: int(df[c].sum()) for c in topic_cols}
    sorted_topics = sorted(topic_totals.items(), key=lambda x: -x[1])
    if sorted_topics:
        names, counts = zip(*sorted_topics)
        fig, ax = plt.subplots(figsize=(10, max(4, len(names) * 0.32)))
        bars = ax.barh(names, counts, color=ACCENT)
        ax.invert_yaxis()
        for b, c in zip(bars, counts):
            ax.text(b.get_width() + max(counts) * 0.01, b.get_y() + b.get_height() / 2, f"{c:,}",
                    va="center", fontsize=9)
        plt.tight_layout()
        st.pyplot(fig)
        plt.close()

    # Topic-month heatmap
    st.subheader("Konu × ay yoğunluk haritası (Top 6)")
    top6 = [t for t, _ in sorted_topics[:6] if topic_totals[t] > 0]
    if top6:
        pivot = df.groupby("month")[[f"t_{t}" for t in top6]].sum()
        pivot.columns = top6
        fig, ax = plt.subplots(figsize=(8, max(4, len(pivot) * 0.18)))
        im = ax.imshow(pivot.values, aspect="auto", cmap="Purples")
        ax.set_xticks(range(len(top6)))
        ax.set_xticklabels(top6, rotation=45, ha="right")
        ax.set_yticks(range(len(pivot)))
        ax.set_yticklabels(pivot.index, fontsize=7)
        plt.colorbar(im, ax=ax)
        plt.tight_layout()
        st.pyplot(fig)
        plt.close()

with tab3:
    st.subheader("Etkileşim heatmap — gün × saat")
    heatmap = df.groupby(["dow", "hour"]).size().unstack(fill_value=0)
    fig, ax = plt.subplots(figsize=(12, 4))
    im = ax.imshow(heatmap, aspect="auto", cmap="Purples")
    ax.set_xticks(range(24))
    ax.set_xticklabels(range(24))
    ax.set_yticks(range(7))
    ax.set_yticklabels(["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Pzr"])
    plt.colorbar(im, ax=ax)
    plt.tight_layout()
    st.pyplot(fig)
    plt.close()

    # Top senders
    st.subheader("Top 20 aktif anonim sender")
    top_senders = df["sender"].value_counts().head(20).reset_index()
    top_senders.columns = ["sender", "mesaj"]
    st.dataframe(top_senders, use_container_width=True, hide_index=True)

with tab4:
    st.subheader("Soru arama")
    search_q = st.text_input("Anahtar kelime / regex", placeholder="ör. sperrkonto, ^vize.*sure, b2 c1")
    case_sensitive = st.checkbox("Büyük/küçük harf duyarlı", value=False)
    use_regex = st.checkbox("Regex modu", value=False)

    only_q_df = df[df["is_question"]] if not only_questions else df
    if search_q.strip():
        try:
            if use_regex:
                mask = only_q_df["text"].str.contains(search_q, case=case_sensitive, regex=True, na=False)
            else:
                mask = only_q_df["text"].str.contains(re.escape(search_q), case=case_sensitive, regex=True, na=False)
            results = only_q_df[mask]
        except re.error as e:
            st.error(f"Regex hatası: {e}")
            results = only_q_df.iloc[0:0]
    else:
        results = only_q_df

    st.caption(f"📌 {len(results):,} eşleşme bulundu")
    show = results[["dt", "source", "sender", "text"]].head(200).copy()
    show["dt"] = show["dt"].dt.strftime("%Y-%m-%d %H:%M")
    st.dataframe(show, use_container_width=True, hide_index=True, height=500)

with tab5:
    st.subheader("Export")

    csv_bytes = df.to_csv(index=False).encode("utf-8")
    st.download_button("⬇ CSV indir", csv_bytes, "telegram_filtered.csv", "text/csv")

    json_bytes = df.drop(columns=[c for c in df.columns if c.startswith("t_")] + ["dt"], errors="ignore").to_json(orient="records", force_ascii=False, indent=2).encode("utf-8")
    st.download_button("⬇ JSON indir", json_bytes, "telegram_filtered.json", "application/json")

    # Markdown report
    md = io.StringIO()
    md.write("# Telegram Analiz Raporu\n\n")
    md.write(f"_Üretildi: {datetime.now().strftime('%Y-%m-%d %H:%M')}_\n\n")
    md.write(f"- Toplam mesaj: {len(df):,}\n")
    md.write(f"- Unique sender: {df['sender'].nunique():,}\n")
    md.write(f"- Soru sayısı: {int(df['is_question'].sum()):,}\n")
    md.write(f"- Kaynak grup: {df['source'].nunique()}\n\n")
    md.write("## Konu frekansı\n\n| Konu | Mesaj |\n|---|---|\n")
    for t, c in sorted_topics:
        md.write(f"| {t} | {c:,} |\n")
    st.download_button("⬇ Markdown rapor indir", md.getvalue().encode("utf-8"),
                       "telegram_report.md", "text/markdown")

    st.markdown("---")
    st.caption("💡 İpucu: `streamlit run analysis/telegram_analyzer_app.py --server.port 8502` ile farklı port'ta çalıştırabilirsin.")
