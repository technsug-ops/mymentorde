# MentorDE MVP Smoke Checklist

Bu liste her deploy veya buyuk degisiklikten sonra temel akislari hizli dogrulamak icin kullanilir.

## 1) Public / Guest Giris
- `/apply` aciliyor mu
- Form submit sonrasi success ve `tracking_token` olusuyor mu
- `Bizi nereden buldunuz` dinamik secenekleri geliyor mu

## 2) Guest Portal
- `/guest/dashboard` aciliyor mu
- `/guest/registration/form` taslak + final submit calisiyor mu
- `/guest/registration/documents` checklist + upload + delete calisiyor mu
- `/guest/tickets` ticket ac / yanit / kapat / yeniden ac calisiyor mu
- `/guest/contract` talep + imzali dosya upload calisiyor mu
- `/guest/profile` profil guncelle + profil fotograf yukle calisiyor mu

## 3) Manager Config
- `/config` aciliyor mu
- Guest Applications listesi + conversion checklist gorunuyor mu
- Guest Ops panelinde:
  - ticket status degisimi
  - manager reply
  - document approve/reject (reject note zorunlu)
- Event Timeline paneli son olaylari listeliyor mu

## 4) Task Board
- `/tasks` aciliyor mu
- guest eventlerinden task uretiliyor mu
- contract/ticket/document kapanisinda task `done` oluyor mu
- conversion sonrasi `student_onboarding_auto` tasklari olusuyor mu

## 5) Student Portal
- `/student/dashboard` aciliyor mu
- kimlik/surec/belge/finans/bildirim kartlari doluyor mu
- `Sonraki Adimi Talep Et` task ve event olusturuyor mu

## 6) Multi-Company
- `/config` icinden firma switch calisiyor mu
- switch sonrasi listeler firma context'e gore degisiyor mu

## 7) Integration Prep (MVP-ready)
- External Provider Connections karti aciliyor mu
- provider kaydi ekle/guncelle calisiyor mu
- status / client id / scopes kaydi persist oluyor mu

## 8) Teknik
- `php artisan migrate --force` hatasiz
- `php artisan optimize:clear` hatasiz
- kritik ekranlarda 500 yok

