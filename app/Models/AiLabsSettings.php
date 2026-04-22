<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiLabsSettings extends Model
{
    use HasFactory;

    protected $table = 'ai_labs_settings';

    protected $fillable = [
        'company_id',
        'default_mode',
        'primary_provider',
        'daily_limit_student',
        'daily_limit_guest',
        'content_generator_enabled',
        'admin_instructions',
        'instructions_updated_at',
        'monthly_doc_limit',
        'questions_this_month',
        'docs_this_month',
        'period_reset_date',
    ];

    protected $casts = [
        'content_generator_enabled' => 'boolean',
        'daily_limit_student' => 'integer',
        'daily_limit_guest' => 'integer',
        'monthly_doc_limit' => 'integer',
        'questions_this_month' => 'integer',
        'docs_this_month' => 'integer',
        'period_reset_date' => 'date',
        'instructions_updated_at' => 'datetime',
    ];

    public const MODES = ['strict', 'hybrid'];
    public const PROVIDERS = ['gemini', 'claude', 'openai'];

    public static function forCompany(int $companyId): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            [
                'default_mode' => 'hybrid',
                'primary_provider' => 'gemini',
                'daily_limit_student' => 50,
                'daily_limit_guest' => 20,
                'content_generator_enabled' => false,
                'monthly_doc_limit' => 10,
            ]
        );
    }
}
