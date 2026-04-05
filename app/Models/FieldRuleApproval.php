<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldRuleApproval extends Model
{
    protected $fillable = [
        'rule_id','student_id','guest_id','triggered_field','triggered_value','severity','status',
        'approved_by','approved_at','rejection_reason',
    ];

    protected $casts = [
        'triggered_value' => 'array',
        'approved_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(FieldRule::class, 'rule_id');
    }
}
