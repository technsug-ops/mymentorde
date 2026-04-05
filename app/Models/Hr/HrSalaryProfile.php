<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrSalaryProfile extends Model
{
    protected $table = 'hr_salary_profiles';

    protected $fillable = [
        'company_id', 'user_id', 'gross_salary', 'currency',
        'payment_day', 'bank_name', 'iban', 'valid_from',
        'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'gross_salary' => 'float',
        'valid_from'   => 'date',
        'is_active'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
