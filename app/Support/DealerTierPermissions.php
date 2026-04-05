<?php

namespace App\Support;

use App\Models\Dealer;
use App\Models\DealerType;
use Illuminate\Support\Facades\Cache;

/**
 * Dealer'ın tier'ına göre portal izinlerini yöneten helper.
 *
 * Kullanım:
 *   $tp = DealerTierPermissions::for($dealer);
 *   $tp->tier()           // 1, 2 veya 3
 *   $tp->can('canViewStudentDetails')  // bool
 *   $tp->dashboardLevel() // 'basic' | 'standard' | 'advanced'
 *   $tp->toArray()        // tüm permissions array
 */
class DealerTierPermissions
{
    private array $perms;

    private static array $defaults = [
        'tier'                  => 1,
        'canViewStudentDetails' => false,
        'canViewDocuments'      => false,
        'canUploadDocuments'    => false,
        'canMessageStudent'     => false,
        'canViewProcessDetails' => false,
        'canViewFinancials'     => true,
        'canViewTerritoryStats' => false,
        'dashboardLevel'        => 'basic',
        'canAccessCalculator'   => true,
        'canAccessTraining'     => true,
        'canAccessSupport'      => false,
        'nonCompeteMonths'      => 0,
        'contractDurationMonths'=> 12,
        'minimumLeadsPerMonth'  => 0,
    ];

    private function __construct(array $perms)
    {
        $this->perms = array_merge(self::$defaults, $perms);
    }

    public static function for(?Dealer $dealer): self
    {
        if (!$dealer || !$dealer->dealer_type_code) {
            return new self([]);
        }

        $typeCode = $dealer->dealer_type_code;

        $perms = Cache::remember("dealer_type_perms_{$typeCode}", 600, function () use ($typeCode) {
            $type = DealerType::where('code', $typeCode)->first();
            if (!$type || !$type->permissions) {
                return [];
            }
            $decoded = is_array($type->permissions)
                ? $type->permissions
                : (json_decode($type->permissions, true) ?? []);
            return $decoded;
        });

        return new self($perms);
    }

    public function tier(): int
    {
        return (int) ($this->perms['tier'] ?? 1);
    }

    public function dashboardLevel(): string
    {
        return (string) ($this->perms['dashboardLevel'] ?? 'basic');
    }

    public function can(string $permission): bool
    {
        return (bool) ($this->perms[$permission] ?? false);
    }

    public function toArray(): array
    {
        return $this->perms;
    }

    // ── Kısayol metodlar ──────────────────────────────────────────────────────

    public function isBasic(): bool    { return $this->dashboardLevel() === 'basic'; }
    public function isStandard(): bool { return in_array($this->dashboardLevel(), ['standard', 'advanced']); }
    public function isAdvanced(): bool { return $this->dashboardLevel() === 'advanced'; }

    public function tierLabel(): string
    {
        return match ($this->tier()) {
            1 => 'Lead Generation',
            2 => 'Freelance Danışman',
            3 => 'B2B Partner',
            default => 'Dealer',
        };
    }

    public function tierColor(): string
    {
        return match ($this->tier()) {
            1 => '#0891b2',
            2 => '#7c3aed',
            3 => '#1e40af',
            default => '#64748b',
        };
    }
}
