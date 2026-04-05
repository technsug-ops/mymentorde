<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentRecord extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'application_id',
        'consent_type',
        'version',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'company_id'     => 'integer',
            'user_id'        => 'integer',
            'application_id' => 'integer',
            'accepted_at'    => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guestApplication()
    {
        return $this->belongsTo(GuestApplication::class, 'application_id');
    }
}
