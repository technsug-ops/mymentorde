<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BookingWaitlistRequest extends Model
{
    use BelongsToCompany;

    public const TRACKS = ['bachelor', 'master', 'other'];
    public const STATUSES = ['new', 'contacted', 'converted', 'dismissed'];

    protected $fillable = [
        'company_id',
        'name', 'email', 'phone', 'track', 'message',
        'status', 'contacted_by', 'contacted_at', 'contact_notes',
        'utm_source', 'utm_medium', 'utm_campaign', 'referrer_url',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function trackLabel(): string
    {
        return match ($this->track) {
            'bachelor' => 'Bachelor (Lisans)',
            'master'   => 'Master (Yüksek Lisans)',
            default    => 'Diğer',
        };
    }
}
