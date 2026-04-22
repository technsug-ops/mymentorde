<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AiLabsFeedback extends Model
{
    use BelongsToCompany;

    protected $table = 'ai_labs_feedback';

    protected $fillable = [
        'company_id',
        'conversation_type',
        'conversation_id',
        'user_id',
        'guest_application_id',
        'rating',
        'reason',
        'role',
    ];

    public const CONVERSATION_TYPES = ['guest', 'senior', 'staff'];
    public const RATINGS = ['good', 'bad'];
}
