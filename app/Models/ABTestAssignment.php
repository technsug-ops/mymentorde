<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABTestAssignment extends Model
{
    protected $table = 'ab_test_assignments';

    public $timestamps = false;

    protected $fillable = [
        'ab_test_id', 'guest_application_id', 'variant_code',
        'converted', 'assigned_at', 'converted_at',
    ];

    protected $casts = [
        'converted'     => 'boolean',
        'assigned_at'   => 'datetime',
        'converted_at'  => 'datetime',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(ABTest::class, 'ab_test_id');
    }

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }
}
