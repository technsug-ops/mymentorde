<?php

/**
 * İndirim kodu paylaşım kartı (public landing /promo/{code}) için
 * template kayıt yeri.
 *
 * Yeni template eklemek için:
 *   1. Bu dosyaya yeni numaralı bir entry ekle (id artarak)
 *   2. resources/views/promo/templates/{id}.blade.php oluştur
 *   3. resources/views/promo/templates/styles.blade.php'ye CSS ekle
 *   4. (Opsiyonel) controller/AI service'a tone tanımı ekle
 *
 * 'preview_gradient' manager form'undaki seçici thumbnail'ında kullanılır.
 */

return [
    1 => [
        'name'             => 'Classic',
        'mood'             => 'Sade · Profesyonel',
        'preview_gradient' => 'linear-gradient(135deg, #6d28d9 0%, #4f46e5 100%)',
        'preview_color'    => 'white',
        'tone'             => 'Profesyonel ama sıcak. 1-2 emoji. Güven veren, net, sade.',
    ],
    2 => [
        'name'             => 'Bold',
        'mood'             => 'Canlı · Genç',
        'preview_gradient' => 'linear-gradient(135deg, #ec4899 0%, #f97316 100%)',
        'preview_color'    => 'white',
        'tone'             => 'Canlı, genç, enerjik. Emoji kullan. Heyecanlı kelimeler.',
    ],
    3 => [
        'name'             => 'Premium',
        'mood'             => 'Lüks · Şık',
        'preview_gradient' => 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
        'preview_color'    => '#fbbf24',
        'tone'             => 'Lüks, ayrıcalıklı, sofistike. Az emoji. Şık, hafif resmi ama soğuk değil.',
    ],
    4 => [
        'name'             => 'Playful',
        'mood'             => 'Eğlenceli · Renkli',
        'preview_gradient' => 'linear-gradient(135deg, #c084fc 0%, #fde047 100%)',
        'preview_color'    => '#581c87',
        'tone'             => 'Eğlenceli, samimi, oyuncu. Bol emoji. Genç-arkadaş gibi konuşur.',
    ],
    5 => [
        'name'             => 'Urgency',
        'mood'             => 'Aciliyet · Limited',
        'preview_gradient' => 'linear-gradient(135deg, #dc2626 0%, #f97316 100%)',
        'preview_color'    => 'white',
        'tone'             => 'Aciliyet hissi. "Kaçırma", "sadece X gün", "son fırsat" tarzı. Direkt eylem çağrısı.',
    ],
];
