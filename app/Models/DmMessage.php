<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmMessage extends Model
{
    protected $table = 'dm_messages';

    protected $fillable = [
        'thread_id',
        'sender_user_id',
        'sender_role',
        'message',
        'is_quick_request',
        'attachment_original_name',
        'attachment_storage_path',
        'attachment_mime',
        'attachment_size_kb',
        'is_read_by_advisor',
        'is_read_by_participant',
    ];

    protected $casts = [
        'thread_id' => 'integer',
        'sender_user_id' => 'integer',
        'is_quick_request' => 'boolean',
        'is_read_by_advisor' => 'boolean',
        'is_read_by_participant' => 'boolean',
        'attachment_size_kb' => 'integer',
    ];

    public function thread()
    {
        return $this->belongsTo(DmThread::class, 'thread_id');
    }
}

