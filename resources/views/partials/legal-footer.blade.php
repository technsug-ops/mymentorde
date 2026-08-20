{{-- Yasal footer — DSGVO/KVKK uyumu için tüm public + auth sayfalarda gösterilmeli.
     Marka uyumlu (mor/sarı palette + Plus Jakarta Sans). --}}
<style>
.lf-footer {
    background: #fff;
    border-top: 1px solid #d9e2ee;
    padding: 24px 20px;
    font-size: 13px;
    color: #5e7187;
    margin-top: auto;
}
.lf-inner {
    max-width: 1080px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.lf-copy {
    font-size: 12.5px;
    color: #7e90a8;
}
.lf-links {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    align-items: center;
}
.lf-links a, .lf-links button {
    color: #5e7187;
    text-decoration: none;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font: inherit;
    font-weight: 600;
    transition: color .15s;
}
.lf-links a:hover, .lf-links button:hover {
    color: #5b2e91;
    text-decoration: none;
}
.lf-links .cookie-btn {
    color: #5b2e91;
    border: 1px solid #d9e2ee;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12px;
}
.lf-links .cookie-btn:hover {
    background: #f1e8fb;
    border-color: #5b2e91;
}
@media (max-width: 640px) {
    .lf-inner {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
    }
    .lf-links { justify-content: center; }
}
</style>

<footer class="lf-footer">
    <div class="lf-inner">
        <div class="lf-copy">
            © {{ date('Y') }} {{ config('brand.name', 'MentorDE') }}. Tüm hakları saklıdır.
            · @include('partials.vendor-credit')
        </div>
        <nav class="lf-links" aria-label="Yasal bilgiler">
            <a href="{{ route('legal.imprint') }}">Impressum</a>
            <a href="{{ route('legal.privacy') }}">Datenschutz / KVKK</a>
            <a href="{{ route('legal.cookies') }}">Çerez Politikası</a>
            <a href="{{ route('legal.terms') }}">AGB / Kullanım</a>
            <button type="button" class="cookie-btn" onclick="if(window.openCookieSettings)window.openCookieSettings()">⚙ Çerez Ayarları</button>
        </nav>
    </div>
</footer>
