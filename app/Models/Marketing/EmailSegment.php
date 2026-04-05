<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class EmailSegment extends Model
{
    protected $fillable = [
        'name', 'description', 'type', 'rules', 'member_user_ids', 'estimated_size',
        'last_calculated_at', 'zoho_list_id', 'zoho_synced', 'is_active', 'created_by',
    ];

    protected $casts = [
        'rules' => 'array',
        'member_user_ids' => 'array',
        'is_active' => 'boolean',
        'zoho_synced' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeDynamic($q)
    {
        return $q->where('type', 'dynamic');
    }

    public function isDynamic(): bool
    {
        return $this->type === 'dynamic';
    }
}
