<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Mesaj / aksiyon şablonları — WhatsApp, email, çağrı scripti, not için.
 * Variables: {{first_name}}, {{last_name}}, {{senior_name}}, {{company_name}}, vb.
 */
class ActionTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'channel',
        'target_type',
        'subject',
        'body',
        'variables',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const CHANNELS = ['whatsapp', 'email', 'call_script', 'note'];
    public const TARGET_TYPES = ['guest', 'student', 'both'];

    /**
     * Template body'sinde variable'ları substitute eder.
     * Örnek: "Merhaba {{first_name}}" + ['first_name' => 'Ali'] → "Merhaba Ali"
     */
    public function render(array $vars): array
    {
        $body = $this->body;
        $subject = $this->subject;

        foreach ($vars as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
            if ($subject) {
                $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
            }
        }

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
    }

    /**
     * Guest veya student için variable'ları hazırlar.
     */
    public static function extractVariables($target, array $extra = []): array
    {
        if ($target instanceof GuestApplication) {
            return array_merge([
                'first_name'    => $target->first_name ?? '',
                'last_name'     => $target->last_name ?? '',
                'full_name'     => trim(($target->first_name ?? '') . ' ' . ($target->last_name ?? '')),
                'email'         => $target->email ?? '',
                'phone'         => $target->phone ?? '',
                'senior_name'   => $target->assigned_senior_email ?? '',
                'lead_score'    => $target->lead_score ?? 0,
                'company_name'  => config('brand.name', 'MentorDE'),
            ], $extra);
        }

        if ($target instanceof User) {
            return array_merge([
                'first_name'    => explode(' ', $target->name ?? '')[0] ?? '',
                'last_name'     => '',
                'full_name'     => $target->name ?? '',
                'email'         => $target->email ?? '',
                'phone'         => '',
                'senior_name'   => '',
                'company_name'  => config('brand.name', 'MentorDE'),
            ], $extra);
        }

        return $extra;
    }
}
