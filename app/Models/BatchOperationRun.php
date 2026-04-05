<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchOperationRun extends Model
{
    protected $fillable = [
        'operation_type',
        'filters',
        'payload',
        'target_count',
        'processed_count',
        'failed_count',
        'status',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'payload' => 'array',
    ];
}

