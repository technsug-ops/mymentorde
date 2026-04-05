<?php

namespace App\Models\Hr;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class HrCertification extends Model
{
    protected $table = 'hr_certifications';

    protected $fillable = [
        'company_id', 'user_id', 'cert_name', 'issuer',
        'issue_date', 'expiry_date', 'file_path', 'notes',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'expiry_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiry_date !== null
            && !$this->isExpired()
            && $this->expiry_date->lte(Carbon::now()->addDays(60));
    }

    public function statusBadge(): string
    {
        if ($this->isExpired()) return 'danger';
        if ($this->isExpiringSoon()) return 'warn';
        return 'ok';
    }

    public function statusLabel(): string
    {
        if ($this->isExpired()) return 'Süresi Doldu';
        if ($this->isExpiringSoon()) return 'Yakında Bitiyor';
        return 'Aktif';
    }
}
