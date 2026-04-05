<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBulletin extends Model
{
    protected $table = 'company_bulletins';

    protected $fillable = [
        'company_id', 'author_id', 'title', 'body',
        'category', 'is_pinned', 'published_at', 'expires_at',
        'target_roles', 'target_departments',
    ];

    protected $casts = [
        'is_pinned'          => 'boolean',
        'published_at'       => 'datetime',
        'expires_at'         => 'datetime',
        'target_roles'       => 'array',
        'target_departments' => 'array',
    ];

    public static array $categoryLabels = [
        'genel'      => 'Genel',
        'duyuru'     => 'Duyuru',
        'acil'       => 'Acil',
        'ik'         => 'İK',
        'kutlama'    => 'Kutlama 🎉',
        'motivasyon' => 'Motivasyon ✨',
    ];

    public static array $categoryColors = [
        'genel'      => '#1e40af',
        'duyuru'     => '#7c3aed',
        'acil'       => '#dc2626',
        'ik'         => '#16a34a',
        'kutlama'    => '#d97706',
        'motivasyon' => '#0891b2',
    ];

    // Desteklenen reaksiyon emojileri
    public const REACTIONS = ['🎉', '👍', '❤️', '🙌', '🏆'];


    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads()
    {
        return $this->hasMany(BulletinRead::class, 'bulletin_id');
    }

    public function reactions()
    {
        return $this->hasMany(BulletinReaction::class, 'bulletin_id');
    }

    public function scopeActive($query)
    {
        return $query
            ->where('published_at', '<=', now())
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Belirli bir kullanıcının rolü/departmanı için görülebilir bültenler.
     * target_roles / target_departments NULL ise herkese gösterilir.
     */
    public function scopeVisibleToUser($query, string $role, ?string $department = null)
    {
        $query->where(fn($q) => $q->whereNull('target_roles')
            ->orWhereJsonContains('target_roles', $role));

        $query->where(function ($q) use ($department): void {
            $q->whereNull('target_departments');
            if ($department) {
                $q->orWhereJsonContains('target_departments', $department);
            }
        });

        return $query;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
