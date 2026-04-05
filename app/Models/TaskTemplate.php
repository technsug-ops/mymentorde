<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'department',
        'category',
        'is_chain',
        'created_by_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id'          => 'integer',
            'created_by_user_id'  => 'integer',
            'is_chain'            => 'boolean',
            'is_active'           => 'boolean',
        ];
    }

    public const CATEGORIES = [
        'onboarding' => 'Onboarding',
        'contract'   => 'Sözleşme',
        'process'    => 'Süreç',
        'general'    => 'Genel',
    ];

    public function items()
    {
        return $this->hasMany(TaskTemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
