<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestAchievement extends Model
{
    public $timestamps = false;

    protected $fillable = ['guest_application_id', 'achievement_code', 'earned_at'];

    protected $casts = ['earned_at' => 'datetime'];
}
