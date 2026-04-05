<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class SystematicInput
{
    public static function codeLower(string $value, string $field = 'code', int $max = 64): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');
        self::assert($normalized !== '' && strlen($normalized) <= $max && preg_match('/^[a-z][a-z0-9_]*$/', $normalized) === 1, $field,
            'Sistematik kod formati: kucuk harf + rakam + altcizgi (ornek: bachelor, paid_social).');
        return $normalized;
    }

    public static function codeUpper(string $value, string $field = 'code', int $max = 64): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9_-]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-_');
        self::assert($normalized !== '' && strlen($normalized) <= $max && preg_match('/^[A-Z][A-Z0-9_-]*$/', $normalized) === 1, $field,
            'Sistematik kod formati: BUYUK harf + rakam + tire/altcizgi (ornek: DOC-DIPL).');
        return $normalized;
    }

    public static function externalId(string $value, string $field = 'external_id', int $max = 32): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9-]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');
        self::assert($normalized !== '' && strlen($normalized) <= $max && preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $normalized) === 1, $field,
            'External ID formati: BUYUK harf/rakam ve tire (ornek: PROC-ONBOARD-01).');
        return $normalized;
    }

    public static function idPrefix(string $value, string $field = 'id_prefix'): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?? '';
        self::assert(strlen($normalized) === 3 && preg_match('/^[A-Z0-9]{3}$/', $normalized) === 1, $field,
            'ID prefix tam 3 karakter olmali (BUYUK harf/rakam).');
        return $normalized;
    }

    public static function category(string $value, string $field = 'category', int $max = 64): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');
        self::assert($normalized !== '' && strlen($normalized) <= $max && preg_match('/^[a-z][a-z0-9_]*$/', $normalized) === 1, $field,
            'Kategori formati: kucuk harf + rakam + altcizgi (ornek: faq, vize_evrak).');
        return $normalized;
    }

    public static function permissionCode(string $value, string $field = 'code', int $max = 120): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? '';
        $normalized = preg_replace('/[^a-z0-9_.]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '._');
        self::assert($normalized !== '' && strlen($normalized) <= $max && preg_match('/^[a-z][a-z0-9_.]*$/', $normalized) === 1, $field,
            'Permission code formati: kucuk harf + rakam + nokta/altcizgi (ornek: config.view).');
        return $normalized;
    }

    public static function upperId(string $value, string $field = 'id', int $max = 64): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9-]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');
        self::assert($normalized !== '' && strlen($normalized) <= $max && preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $normalized) === 1, $field,
            'ID formati: BUYUK harf/rakam ve tire (ornek: REF-26-02-A1B2).');
        return $normalized;
    }

    private static function assert(bool $condition, string $field, string $message): void
    {
        if ($condition) {
            return;
        }

        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
