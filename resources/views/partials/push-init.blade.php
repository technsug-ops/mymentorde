@auth
<script>
(function(){
    if(!('Notification' in window)||!('serviceWorker' in navigator)) return;
    if(localStorage.getItem('push_declined')==='1') return;
    if(Notification.permission==='granted'){
        registerFcmToken();
        return;
    }
    if(Notification.permission==='default'){
        setTimeout(function(){
            Notification.requestPermission().then(function(p){
                if(p==='granted') registerFcmToken();
                else localStorage.setItem('push_declined','1');
            });
        }, 3000);
    }
})();

function registerFcmToken(){
    if(typeof firebase === 'undefined') return;
    try {
        var app = firebase.initializeApp({
            apiKey:            '{{ config("services.firebase_web.api_key","") }}',
            projectId:         '{{ config("firebase.project_id","") }}',
            messagingSenderId: '{{ config("services.firebase_web.messaging_sender_id","") }}',
            appId:             '{{ config("services.firebase_web.app_id","") }}'
        });
        var messaging = firebase.messaging(app);
        messaging.getToken({vapidKey: '{{ config("services.firebase_web.vapid_key","") }}'}).then(function(token){
            if(!token) return;
            fetch('/push/register-token', {
                method:'POST',
                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
                body:JSON.stringify({token:token})
            });
        }).catch(function(){});
    } catch(e){}
}
</script>
@endauth
