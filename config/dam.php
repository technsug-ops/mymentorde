<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Digital Asset Management
    |--------------------------------------------------------------------------
    */

    'max_size_bytes' => (int) env('DAM_MAX_SIZE_BYTES', 50 * 1024 * 1024), // 50 MB

    // Şirket başına toplam DAM kotası. 0 = sınırsız.
    'max_storage_per_company' => (int) env('DAM_MAX_STORAGE_PER_COMPANY', 5 * 1024 * 1024 * 1024), // 5 GB

    // Bulk upload tek istekte kaç dosyaya izin versin
    'bulk_upload_max_files' => (int) env('DAM_BULK_UPLOAD_MAX', 20),

    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm',
        'audio/mpeg', 'audio/ogg', 'audio/wav',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'text/plain', 'text/csv',
    ],
];
