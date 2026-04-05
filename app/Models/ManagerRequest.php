<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ManagerRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'requester_user_id',
        'target_manager_user_id',
        'request_type',
        'subject',
        'description',
        'status',
        'priority',
        'due_date',
        'requested_at',
        'responded_at',
        'resolved_at',
        'decision_note',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'requester_user_id' => 'integer',
            'target_manager_user_id' => 'integer',
            'due_date' => 'date',
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'target_manager_user_id');
    }
}

