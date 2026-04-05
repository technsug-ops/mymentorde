{{-- Cookie Consent Banner --}}
<div id="cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:99999;
     background:#0f172a;color:#fff;padding:16px 24px;
     flex-direction:row;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
     border-top:3px solid #1e40af;box-shadow:0 -4px 20px rgba(0,0,0,.3);">
    <div style="flex:1;min-width:250px;">
        <div style="font-size:14px;font-weight:700;margin-bottom:4px;">Cerez Politikasi</div>
        <div style="font-size:12px;color:rgba(255,255,255,.75);line-height:1.5;">
            Bu site, daha iyi bir deneyim sunmak icin zorunlu cerezler kullanmaktadir.
            <a href="/privacy" style="color:#93c5fd;text-decoration:underline;">Gizlilik Politikasi</a>
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-shrink:0;">
        <button onclick="cookieConsent('essential')"
                style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);
                       border-radius:7px;padding:8px 16px;font-size:12px;font-weight:600;cursor:pointer;">
            Sadece Zorunlu
        </button>
        <button onclick="cookieConsent('all')"
                style="background:#1e40af;color:#fff;border:none;
                       border-radius:7px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer;">
            Tumunu Kabul Et
        </button>
    </div>
</div>
<script>
(function(){
    if(!localStorage.getItem('cookie_consent')){
        var b=document.getElementById('cookie-banner');
        if(b) b.style.display='flex';
    }
})();
function cookieConsent(type){
    localStorage.setItem('cookie_consent', type);
    localStorage.setItem('cookie_consent_date', new Date().toISOString());
    var b=document.getElementById('cookie-banner');
    if(b) b.style.display='none';
    // CSRF ile DB'ye kaydet
    var csrf=document.querySelector('meta[name=csrf-token]');
    fetch('/cookie-consent', {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN': csrf ? csrf.content : '',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({type:type})
    }).catch(function(){});
}
</script>
