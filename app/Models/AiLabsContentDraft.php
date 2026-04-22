<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AiLabsContentDraft extends Model
{
    use BelongsToCompany;

    protected $table = 'ai_labs_content_drafts';

    protected $fillable = [
        'company_id',
        'user_id',
        'target_user_id',
        'template_code',
        'title',
        'variables',
        'content',
        'metadata',
        'status',
        'tokens_input',
        'tokens_output',
        'provider',
        'model',
    ];

    protected $casts = [
        'variables'     => 'array',
        'metadata'      => 'array',
        'tokens_input'  => 'integer',
        'tokens_output' => 'integer',
    ];

    public const TEMPLATES = [
        'motivation_letter'    => '🎓 Motivation Letter',
        'sperrkonto'           => '🏦 Sperrkonto Başvuru',
        'visa_call'            => '📧 Vize Çağrı Mektubu',
        'uni_recommendation'   => '🏫 Üniversite Önerisi Raporu',
        'blog_post'            => '📰 Blog Yazısı (SEO)',
        'faq'                  => '❓ FAQ Oluşturucu',
        'custom'               => '✨ Özel Prompt',
    ];
}
