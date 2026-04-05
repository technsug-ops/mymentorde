<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'attachment_type',
        'file_path', 'original_name', 'mime_type', 'file_size', 'url',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(MarketingTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function publicUrl(): string
    {
        if ($this->attachment_type === 'link') {
            return (string) ($this->url ?? '');
        }
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }
        return '';
    }

    public function toFrontend(): array
    {
        return [
            'id'   => $this->id,
            'type' => $this->attachment_type,
            'name' => $this->original_name ?? $this->url ?? '',
            'url'  => $this->publicUrl(),
            'size' => $this->file_size,
            'mime' => $this->mime_type,
        ];
    }
}
