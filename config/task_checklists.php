<?php

/**
 * Görev kaynağına (source_type) göre otomatik checklist şablonları.
 * TaskAutomationService::createTask() bu config'i okuyarak görev oluştururken
 * checklist item'larını otomatik ekler.
 */
return [

    'guest_registration_submit' => [
        'Başvuru formunu kontrol et',
        'Zorunlu alanları doğrula',
        'Senior atama yap',
        'İlk iletişim bildirimini gönder',
    ],

    'guest_contract_requested' => [
        'Sözleşme şablonunu hazırla',
        'Fiyatlandırmayı kontrol et',
        'Ek maddeleri ekle',
        "Guest'e gönder",
    ],

    'guest_contract_signed_uploaded' => [
        'İmzalı sözleşmeyi kontrol et',
        'İmza alanlarını doğrula',
        'Tarih ve imzaların uygunluğunu onayla',
        'Onay kararını ver',
    ],

    'guest_document_uploaded' => [
        'Belgeyi incele',
        'Geçerlilik tarihini kontrol et',
        'Belge tipini doğrula',
        'Onay veya ret kararını ver',
    ],

    'dealer_lead_submitted' => [
        'Lead bilgilerini doğrula',
        'Bayi profili kontrol et',
        'UTM kaynağını kaydet',
        'İlk iletişim için senior ata',
    ],

    'student_assignment' => [
        'Öğrencinin dosyasını incele',
        'Randevu planla',
        'Başlangıç belge kontrolü yap',
        'Danışmanlık sürecini başlat',
    ],

    'escalation' => [
        'Eskalasyon nedenini incele',
        'İlgili kişilerle görüş',
        'Aksiyon planı oluştur',
        'Çözümü kayıt altına al',
    ],

];
