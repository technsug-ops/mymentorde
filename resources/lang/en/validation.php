<?php

/**
 * MentorDE — Sifre & form validation mesajlari (Türkçe).
 *
 * APP_LOCALE=en (config/app.php) oldugu icin Laravel default English mesajlari
 * kullaniyordu. Bu dosya en lang dizinine konuldu — Laravel'in publish ettigi
 * default mesajlari override eder; tüm validation mesajlari TR.
 *
 * Sadece Mentorde'nin kullandigi rule'lar tanimli; tanimsiz rule icin
 * Laravel default English'e dusuyor.
 */
return [
    'required'             => ':attribute alanı zorunludur.',
    'string'               => ':attribute metin olmalı.',
    'email'                => ':attribute geçerli bir e-posta olmalı.',
    'url'                  => ':attribute geçerli bir URL olmalı.',
    'min'                  => [
        'string'           => ':attribute en az :min karakter olmalı.',
        'numeric'          => ':attribute en az :min olmalı.',
        'array'            => ':attribute en az :min öğe içermeli.',
    ],
    'max'                  => [
        'string'           => ':attribute en fazla :max karakter olabilir.',
        'numeric'          => ':attribute en fazla :max olabilir.',
        'array'            => ':attribute en fazla :max öğe içerebilir.',
        'file'             => ':attribute en fazla :max KB olabilir.',
    ],
    'in'                   => 'Geçersiz :attribute seçimi.',
    'integer'              => ':attribute tam sayı olmalı.',
    'boolean'              => ':attribute true/false olmalı.',
    'numeric'              => ':attribute sayı olmalı.',
    'array'                => ':attribute liste olmalı.',
    'file'                 => ':attribute dosya olmalı.',
    'image'                => ':attribute resim olmalı.',
    'mimes'                => ':attribute şu tipte olmalı: :values.',
    'confirmed'            => ':attribute onayı eşleşmiyor.',
    'unique'               => ':attribute zaten kullanılıyor.',
    'exists'               => 'Seçilen :attribute geçersiz.',
    'date'                 => ':attribute geçerli bir tarih olmalı.',
    'after'                => ':attribute :date tarihinden sonra olmalı.',
    'before'               => ':attribute :date tarihinden önce olmalı.',
    'regex'                => ':attribute formatı geçersiz.',
    'accepted'             => ':attribute kabul edilmelidir.',
    'required_if'          => ':other şu değerle :value :attribute zorunlu.',

    // Laravel Password rule sub-mesajlari
    'password' => [
        'letters'          => ':attribute en az bir harf içermeli.',
        'mixed'            => ':attribute hem büyük hem küçük harf içermeli.',
        'numbers'          => ':attribute en az bir rakam içermeli.',
        'symbols'          => ':attribute en az bir özel karakter içermeli (!@#$%& vb.).',
        'uncompromised'    => ':attribute veri sızıntılarında bulunmuş, lütfen farklı bir şifre seçin.',
    ],

    // Field adlarinin Türkçe karsiligi
    'attributes' => [
        'current_password'           => 'Mevcut şifre',
        'new_password'               => 'Yeni şifre',
        'new_password_confirmation'  => 'Yeni şifre tekrarı',
        'password'                   => 'Şifre',
        'email'                      => 'E-posta',
        'phone'                      => 'Telefon',
        'first_name'                 => 'Ad',
        'last_name'                  => 'Soyad',
        'name'                       => 'İsim',
        'message'                    => 'Mesaj',
        'subject'                    => 'Konu',
        'title'                      => 'Başlık',
        'category'                   => 'Kategori',
        'file'                       => 'Dosya',
        'doc_file'                   => 'Belge',
        'profile_photo'              => 'Profil fotoğrafı',
        'invitee_name'               => 'Adınız',
        'invitee_email'              => 'E-posta',
        'invitee_phone'              => 'Telefon',
    ],

    // Custom rule'lar — controller'lardan ek mesajlar buradan inherit eder
    'custom' => [],
];
