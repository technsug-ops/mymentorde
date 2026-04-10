<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalAssetFolder extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $table = 'digital_asset_folders';

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'slug',
        'path',
        'depth',
        'description',
        'color',
        'icon',
        'is_system',
        'allowed_roles',
        'created_by',
    ];

    protected $casts = [
        'depth'         => 'integer',
        'is_system'     => 'boolean',
        'allowed_roles' => 'array',
    ];

    /**
     * Bu klasör verilen role açık mı?
     * null/boş = herkes (DAM izni olan tüm roller).
     */
    public function isAccessibleByRole(?string $role): bool
    {
        if (empty($this->allowed_roles) || !is_array($this->allowed_roles)) {
            return true;
        }
        return in_array((string) $role, $this->allowed_roles, true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(DigitalAsset::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Klasör için kök → mevcut breadcrumb dizisi.
     *
     * @return array<int, array{id:int, name:string, slug:string}>
     */
    public function breadcrumb(): array
    {
        $crumbs = [];
        $node   = $this;
        while ($node) {
            array_unshift($crumbs, [
                'id'   => (int) $node->id,
                'name' => (string) $node->name,
                'slug' => (string) $node->slug,
            ]);
            $node = $node->parent;
        }
        return $crumbs;
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
