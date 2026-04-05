<?php

namespace App\Support;

use App\Rules\ValidFileMagicBytes;

/**
 * Merkezi dosya yükleme kural seti.
 *
 * Kullanım:
 *   FileUploadRules::document()      → PDF/Office/görsel, 10MB, magic bytes
 *   FileUploadRules::image()         → Yalnızca görsel, 4MB, magic bytes
 *   FileUploadRules::signedContract()→ Yalnızca PDF, 10MB, magic bytes
 *   FileUploadRules::attachment()    → Mesajlaşma eki, 10MB, magic bytes
 *   FileUploadRules::media()         → CMS medya (geniş tip), 30MB, magic bytes
 */
final class FileUploadRules
{
    /** Desteklenen MIME whitelist'leri */
    public const MIMES_DOCUMENT  = 'pdf,jpg,jpeg,png,webp,doc,docx';
    public const MIMES_IMAGE     = 'jpg,jpeg,png,webp';
    public const MIMES_PDF_ONLY  = 'pdf';
    public const MIMES_ATTACHMENT = 'pdf,jpg,jpeg,png,webp,doc,docx';
    public const MIMES_MEDIA     = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,mp4,mov,webm,zip,txt,csv';

    /** KB cinsinden boyut limitleri */
    public const MAX_DOCUMENT_KB  = 10240;   // 10 MB
    public const MAX_IMAGE_KB     = 4096;    //  4 MB
    public const MAX_CONTRACT_KB  = 10240;   // 10 MB
    public const MAX_ATTACHMENT_KB = 10240;  // 10 MB
    public const MAX_MEDIA_KB     = 30720;   // 30 MB

    /** Zorunlu belge yüklemesi (PDF/Office/görsel) */
    public static function document(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:' . self::MIMES_DOCUMENT,
            'max:' . self::MAX_DOCUMENT_KB,
            new ValidFileMagicBytes(),
        ];
    }

    /** Opsiyonel belge yüklemesi */
    public static function documentOptional(): array
    {
        return self::document(required: false);
    }

    /** Yalnızca görsel (profil fotoğrafı vb.) */
    public static function image(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:' . self::MIMES_IMAGE,
            'max:' . self::MAX_IMAGE_KB,
            new ValidFileMagicBytes(),
        ];
    }

    /** İmzalı sözleşme — yalnızca PDF */
    public static function signedContract(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:' . self::MIMES_PDF_ONLY,
            'max:' . self::MAX_CONTRACT_KB,
            new ValidFileMagicBytes(),
        ];
    }

    /** Mesajlaşma/bilet eki (opsiyonel) */
    public static function attachment(): array
    {
        return [
            'nullable',
            'file',
            'mimes:' . self::MIMES_ATTACHMENT,
            'max:' . self::MAX_ATTACHMENT_KB,
            new ValidFileMagicBytes(),
        ];
    }

    /** CMS / medya kütüphanesi yüklemesi */
    public static function media(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:' . self::MIMES_MEDIA,
            'max:' . self::MAX_MEDIA_KB,
            new ValidFileMagicBytes(),
        ];
    }
}
