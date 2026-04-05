<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class CmsCategory extends Model
{
    protected $fillable = ['code', 'name_tr', 'name_de', 'name_en', 'description_tr', 'parent_category_id', 'icon_url', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_category_id')->orderBy('sort_order');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_category_id');
    }

    public function name(string $l = 'tr')
    {
        return $this->{"name_{$l}"} ?? $this->name_tr;
    }
}
