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
    public const REACTIONS = ['🎉', '👍', '❤️', '👏', '🏆'];


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
     * Duyuruyu kullanıcıya gösterir mi?
     *
     * Kural (OR mantığı):
     *  (a) target_roles ve target_departments ikisi de null → herkese açık
     *  (b) kullanıcının rolü target_roles içinde
     *  (c) kullanıcının departmanı target_departments içinde (varsa)
     *
     * Not: MentorDE'de `users.department` kolonu yok — birincil eşleşme rol
     * üzerinden yapılır. Department filtresi ileride kolon eklenirse aktif olur.
     */
    public function scopeVisibleToUser($query, string $role, ?string $department = null)
    {
        return $query->where(function ($outer) use ($role, $department): void {
            // (a) Hiç hedef belirtilmemiş → herkese gösterilir
            $outer->where(function ($q): void {
                $q->whereNull('target_roles')
                  ->whereNull('target_departments');
            });

            // (b) Rol eşleşmesi
            $outer->orWhereJsonContains('target_roles', $role);

            // (c) Departman eşleşmesi (varsa)
            if ($department) {
                $outer->orWhereJsonContains('target_departments', $department);
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
