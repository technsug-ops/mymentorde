// ── Mimari Not: React + Vanilla JS Karma Kullanımı ────────────────────────────
// Bu projede kasıtlı olarak iki JS katmanı var:
//
//   1. React (JSX) — YALNIZCA `student-document-builder.jsx`
//      Gerçek zamanlı belge önizleme, drag-drop sıralama ve karmaşık UI state
//      gerektiren tek sayfa. React burada gerçekten değer katıyor.
//
//   2. Vanilla JS — diğer tüm 40+ dosya
//      Portal sayfaları genellikle form doğrulama, tab geçişi, fetch çağrısı
//      gibi basit etkileşimler içeriyor. Çerçeve overhead'i gereksiz.
//
// React'ı tüm projeye yaymak: +bundle büyüklüğü, +build karmaşıklığı, -hız.
// Bu trade-off bilinçli olarak yapıldı. Yeni sayfalar varsayılan olarak
// vanilla JS yazılmalı; yalnızca gerçek state yönetimi gerektiriyorsa JSX kullan.
// ──────────────────────────────────────────────────────────────────────────────
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/premium.css',
                'resources/css/portal-unified-v2.css',
                'resources/css/minimalist.css',
                'resources/js/app.js',
                'resources/js/student-document-builder.jsx',
                // ── Static portal JS (40+ files) — vanilla JS, kasıtlı seçim ──
                'resources/js/apply-form.js',
                'resources/js/auth-login.js',
                'resources/js/config-panel.js',
                'resources/js/csv-field.js',
                'resources/js/dealer-dashboard.js',
                'resources/js/document-builder-ai.js',
                'resources/js/emoji-gif-picker.js',
                'resources/js/guest-contract.js',
                'resources/js/guest-language-skills.js',
                'resources/js/guest-messages.js',
                'resources/js/guest-profile.js',
                'resources/js/guest-registration-documents.js',
                'resources/js/guest-registration-form.js',
                'resources/js/guest-services.js',
                'resources/js/guest-tickets.js',
                'resources/js/icon-switcher.js',
                'resources/js/institution-documents.js',
                'resources/js/landing-utm.js',
                'resources/js/manager-dashboard.js',
                'resources/js/manager-sidebar.js',
                'resources/js/manager-theme.js',
                'resources/js/marketing-admin-dashboard.js',
                'resources/js/marketing-admin-integrations.js',
                'resources/js/marketing-email-segments.js',
                'resources/js/messages-center.js',
                'resources/js/messaging-hub.js',
                'resources/js/messaging.js',
                'resources/js/mktg-company-switch.js',
                'resources/js/senior-batch-review.js',
                'resources/js/senior-document-builder.js',
                'resources/js/student-card.js',
                'resources/js/student-contract.js',
                'resources/js/student-messages.js',
                'resources/js/student-registration-documents.js',
                'resources/js/student-registration-form.js',
                'resources/js/student-services.js',
                'resources/js/student-tickets.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
