<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationWorkflowNode extends Model
{
    protected $fillable = [
        'workflow_id', 'node_type', 'node_config',
        'position_x', 'position_y', 'sort_order', 'connections',
    ];

    protected $casts = [
        'node_config'  => 'array',
        'connections'  => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }
}
