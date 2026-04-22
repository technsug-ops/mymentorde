<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeSource extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'title',
        'type',
        'category',
        'file_path',
        'url',
        'content_markdown',
        'gemini_file_id',
        'gemini_file_uri',
        'gemini_uploaded_at',
        'content_hash',
        'target_audience',
        'visible_to_roles',
        'is_active',
        'citation_count',
        'last_used_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'citation_count' => 'integer',
        'gemini_uploaded_at' => 'datetime',
        'last_used_at' => 'datetime',
        'visible_to_roles' => 'array',
    ];

    public const TYPES = ['pdf', 'url', 'text'];
    public const AUDIENCES = ['student', 'guest', 'both'];

    /** AI Labs bilgi havuzunu görebilen roller. */
    public const ROLES = ['guest', 'student', 'senior', 'manager', 'admin_staff'];

    public const ROLE_LABELS = [
        'guest'        => 'Aday Öğrenci',
        'student'      => 'Öğrenci',
        'senior'       => 'Eğitim Danışmanı',
        'manager'      => 'Yönetici',
        'admin_staff'  => 'Admin Personel',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Geriye uyumluluk — target_audience enum bazlı kontrol. */
    public function isVisibleFor(string $audience): bool
    {
        return $this->target_audience === 'both' || $this->target_audience === $audience;
    }

    /** Rol-bazlı görünürlük kontrolü (visible_to_roles JSON). */
    public function isVisibleForRole(string $role): bool
    {
        $roles = $this->visible_to_roles ?: [];
        return in_array($role, $roles, true);
    }

    /**
     * Query scope: belirli role görünür kaynaklar.
     * JSON_CONTAINS ile MySQL 5.7+ çalışır.
     */
    public function scopeVisibleToRole($query, string $role)
    {
        return $query->whereJsonContains('visible_to_roles', $role);
    }
}
