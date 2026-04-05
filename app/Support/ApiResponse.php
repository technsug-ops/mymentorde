<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standart API yanit yardimcisi.
 *
 * Hata kodu formati: ERR_{MODUL}_{HTTP_KODU}
 * Ornek: ERR_DEALER_404, ERR_ASSIGN_409, ERR_TEAM_422
 */
final class ApiResponse
{
    // ── Genel ──────────────────────────────────────────────────────
    public const ERR_NOT_FOUND          = 'ERR_NOTFOUND_404';
    public const ERR_FORBIDDEN          = 'ERR_AUTH_403';
    public const ERR_UNPROCESSABLE      = 'ERR_VALIDATION_422';
    public const ERR_CONFLICT           = 'ERR_CONFLICT_409';
    public const ERR_SERVER             = 'ERR_SERVER_500';

    // ── Dealer ─────────────────────────────────────────────────────
    public const ERR_DEALER_NOT_FOUND   = 'ERR_DEALER_404';
    public const ERR_DEALER_NO_CODES    = 'ERR_DEALER_422_NO_CODES';
    public const ERR_DEALER_CONFLICT    = 'ERR_DEALER_409';

    // ── Assignment ─────────────────────────────────────────────────
    public const ERR_ASSIGN_NOT_FOUND   = 'ERR_ASSIGN_404';
    public const ERR_ASSIGN_CONFLICT    = 'ERR_ASSIGN_409';
    public const ERR_ASSIGN_FORBIDDEN   = 'ERR_ASSIGN_403';

    // ── Team ───────────────────────────────────────────────────────
    public const ERR_TEAM_NOT_FOUND     = 'ERR_TEAM_404';
    public const ERR_TEAM_ROLE_INVALID  = 'ERR_TEAM_422_ROLE';

    // ── Guest / Application ────────────────────────────────────────
    public const ERR_GUEST_NOT_FOUND    = 'ERR_GUEST_404';
    public const ERR_GUEST_CONFLICT     = 'ERR_GUEST_409';
    public const ERR_GUEST_LOCKED       = 'ERR_GUEST_423_LOCKED';

    // ── Student ────────────────────────────────────────────────────
    public const ERR_STUDENT_NOT_FOUND  = 'ERR_STUDENT_404';
    public const ERR_STUDENT_CONFLICT   = 'ERR_STUDENT_409';

    // ── Task ───────────────────────────────────────────────────────
    public const ERR_TASK_NOT_FOUND     = 'ERR_TASK_404';
    public const ERR_TASK_FORBIDDEN     = 'ERR_TASK_403';

    // ── Document ───────────────────────────────────────────────────
    public const ERR_DOC_NOT_FOUND      = 'ERR_DOC_404';
    public const ERR_DOC_FORBIDDEN      = 'ERR_DOC_403';
    public const ERR_DOC_MIME           = 'ERR_DOC_422_MIME';

    // ── Vault ──────────────────────────────────────────────────────
    public const ERR_VAULT_NOT_FOUND    = 'ERR_VAULT_404';
    public const ERR_VAULT_FORBIDDEN    = 'ERR_VAULT_403';

    // ── Notification ───────────────────────────────────────────────
    public const ERR_NOTIF_NO_RECIPIENT = 'ERR_NOTIF_422_NO_RECIPIENT';

    // ─────────────────────────────────────────────────────────────────

    /**
     * Basarili JSON yaniti.
     *
     * @param  array<string,mixed> $data
     */
    public static function ok(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json(array_merge(['ok' => true], $data), $status);
    }

    /**
     * Hatali JSON yaniti.
     *
     * @param  array<string,mixed> $extra
     */
    public static function error(
        string $errCode,
        string $message,
        array $extra = [],
        int $status = Response::HTTP_UNPROCESSABLE_ENTITY
    ): JsonResponse {
        return response()->json(
            array_merge(['ok' => false, 'err' => $errCode, 'message' => $message], $extra),
            $status
        );
    }

    /**
     * 404 Not Found yaniti.
     */
    public static function notFound(string $errCode = self::ERR_NOT_FOUND, string $message = 'Kayit bulunamadi.'): JsonResponse
    {
        return self::error($errCode, $message, [], Response::HTTP_NOT_FOUND);
    }

    /**
     * 409 Conflict yaniti.
     */
    public static function conflict(string $errCode = self::ERR_CONFLICT, string $message = 'Cakisma: kayit zaten mevcut.'): JsonResponse
    {
        return self::error($errCode, $message, [], Response::HTTP_CONFLICT);
    }

    /**
     * 403 Forbidden yaniti.
     */
    public static function forbidden(string $errCode = self::ERR_FORBIDDEN, string $message = 'Bu isleme izniniz yok.'): JsonResponse
    {
        return self::error($errCode, $message, [], Response::HTTP_FORBIDDEN);
    }
}
