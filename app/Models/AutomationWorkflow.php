<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pazarlama otomasyon akışı.
 *
 * ⚠ Şablon DEĞİL, işleyen bir nesne: durumu, onayı ve kayıtlı adayları var.
 * Bu yüzden `company_id = 0` (fabrika) kalıbı buraya uymuyor — paylaşımlı
 * olsaydı bir firmanın "aktifleştir" tıklaması diğerlerini de etkilerdi.
 * Sahibi belirlenemeyen tohum akışları operasyonu yürüten ANA FİRMAYA
 * yazılıyor; bkz. 2026_08_08_120000 migration'ı.
 */
class AutomationWorkflow extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'status',
        'trigger_type', 'trigger_config', 'is_recurring',
        'enrollment_limit', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'trigger_config'   => 'array',
        'is_recurring'     => 'boolean',
        'approved_at'      => 'datetime',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(AutomationWorkflowNode::class, 'workflow_id')->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AutomationEnrollment::class, 'workflow_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
