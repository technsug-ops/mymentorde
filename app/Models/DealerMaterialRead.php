<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerMaterialRead extends Model
{
    protected $fillable = ['dealer_user_id', 'article_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
