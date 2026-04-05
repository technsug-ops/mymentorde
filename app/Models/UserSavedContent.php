<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Marketing\CmsContent;

class UserSavedContent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'cms_content_id'];

    protected $casts = ['created_at' => 'datetime'];

    public function content()
    {
        return $this->belongsTo(CmsContent::class, 'cms_content_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
