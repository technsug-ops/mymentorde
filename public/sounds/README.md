# Notification Sounds

## notif.mp3

Real-time toast bildirimlerinde calinan ses (1-2 sn ping).

**Su an:** placeholder (bos dosya).

**Yapilacak:** uretim oncesi gercek bir ses dosyasi koyulmali.

### Onerilen kaynaklar (telif sorunsuz)

- https://www.soundjay.com/buttons/sounds/button-09a.mp3
- https://mixkit.co/free-sound-effects/notification/  (Mixkit License — ucretsiz)
- https://notificationsounds.com/notification-sounds/  (CC BY)

### Format gereklilikleri

- MP3 (en yaygin browser destekli)
- Bitrate: 96-128 kbps yeterli (boyut: ~15-30 KB)
- Sure: 0.5 - 2 saniye
- LUFS: -16 ila -14 (toast kullanicinin dikkatini cekmeli ama agresif olmamali)

Dosyayi public/sounds/notif.mp3 olarak yerlestir; build/asset yenilemesi gerekmez,
notifications.js direkt /sounds/notif.mp3 path'inden cekiyor.
