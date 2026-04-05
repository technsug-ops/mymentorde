<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTemplateItem extends Model
{
    protected $fillable = [
        'template_id',
        'title',
        'description',
        'priority',
        'due_offset_days',
        'assign_to_role',
        'assign_to_source',
        'sort_order',
        'depends_on_order',
        'checklist_items',
        'estimated_hours',
    ];

    protected function casts(): array
    {
        return [
            'template_id'      => 'integer',
            'due_offset_days'  => 'integer',
            'sort_order'       => 'integer',
            'depends_on_order' => 'integer',
            'checklist_items'  => 'array',
            'estimated_hours'  => 'decimal:2',
        ];
    }

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'template_id');
    }
}
