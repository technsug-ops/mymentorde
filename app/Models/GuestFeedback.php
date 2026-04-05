<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestFeedback extends Model
{
    public $timestamps = false;

    protected $table = 'guest_feedback';

    protected $fillable = [
        'guest_application_id',
        'company_id',
        'feedback_type',
        'process_step',
        'rating',
        'nps_score',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'rating'     => 'integer',
        'nps_score'  => 'integer',
    ];

    public const STEP_LABELS = [
        'application_prep'  => 'Başvuru Hazırlık',
        'uni_assist'        => 'Uni Assist',
        'visa_application'  => 'Vize Başvurusu',
        'language_course'   => 'Dil Kursu',
        'residence'         => 'İkamet',
        'official_services' => 'Resmi Hizmetler',
    ];
}
