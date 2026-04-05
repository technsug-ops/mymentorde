<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CmsContentRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['cms_content_id', 'revision_number', 'edited_by', 'change_note', 'snapshot_data', 'created_at'];

    protected $casts = [
        'snapshot_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function content()
    {
        return $this->belongsTo(CmsContent::class, 'cms_content_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
