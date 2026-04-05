<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CmsMedia extends Model
{
    protected $table = 'cms_media_library';

    protected $fillable = [
        'file_name', 'file_url', 'thumbnail_url', 'file_type', 'mime_type',
        'file_size_bytes', 'width', 'height', 'alt_text', 'tags',
        'used_in_content_ids', 'uploaded_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'used_in_content_ids' => 'array',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeImages($q)
    {
        return $q->where('file_type', 'image');
    }

    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    public function fileSizeFormatted(): string
    {
        return number_format($this->file_size_bytes / 1048576, 1).' MB';
    }
}
