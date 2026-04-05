<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * v3.0 §2.2 — Veri İzolasyon Servisi
 *
 * Staff rolleri (marketing_staff, sales_staff) yalnızca
 * kendileriyle ilişkili verileri görür.
 */
class DataScopeService
{
    private const STAFF_ROLES = [
        User::ROLE_MARKETING_STAFF,
        User::ROLE_SALES_STAFF,
    ];

    public static function isStaff(User $user): bool
    {
        return in_array((string) $user->role, self::STAFF_ROLES, true);
    }

    /**
     * Query'ye staff scope filtresi uygular.
     *
     * Staff  → WHERE created_by_user_id = $userId OR assigned_user_id = $userId
     * Admin  → filtre yok
     *
     * @param  Builder  $query
     * @param  User     $user
     * @param  string   $createdByCol   created_by sütun adı
     * @param  string   $assignedToCol  assigned_to sütun adı
     * @return Builder
     */
    public static function applyScope(
        Builder $query,
        User $user,
        string $createdByCol = 'created_by_user_id',
        string $assignedToCol = 'assigned_user_id'
    ): Builder {
        if (! self::isStaff($user)) {
            return $query;
        }

        $id = (int) $user->id;

        return $query->where(function (Builder $q) use ($id, $createdByCol, $assignedToCol): void {
            $q->where($createdByCol, $id)->orWhere($assignedToCol, $id);
        });
    }

    /**
     * Tek bir modelin kullanıcıya ait olup olmadığını kontrol eder.
     *
     * Staff  → created_by veya assigned_to eşleşmeli
     * Admin  → her zaman true
     */
    public static function canAccess(object $resource, User $user): bool
    {
        if (! self::isStaff($user)) {
            return true;
        }

        $id = (int) $user->id;
        $createdBy  = (int) ($resource->created_by_user_id ?? $resource->created_by ?? 0);
        $assignedTo = (int) ($resource->assigned_user_id  ?? $resource->assigned_to ?? 0);

        return $createdBy === $id || $assignedTo === $id;
    }

    /**
     * GuestApplication query'si için özel scope.
     *
     * sales_staff → yalnızca assignment alanı üzerinden atanmış lead'ler.
     * (GuestApplication'da assigned_to = user_id)
     */
    public static function applyGuestScope(Builder $query, User $user): Builder
    {
        if (! self::isStaff($user)) {
            return $query;
        }

        // sales_staff için assigned_to veya created_by filtresi
        $id = (int) $user->id;

        return $query->where(function (Builder $q) use ($id): void {
            $q->where('assigned_to', $id)
              ->orWhere('created_by_user_id', $id);
        });
    }
}
