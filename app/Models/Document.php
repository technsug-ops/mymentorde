<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'document_id',
        'student_id',
        'category_id',
        'process_tags',
        'original_file_name',
        'standard_file_name',
        'storage_path',
        'mime_type',
        'status',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'review_note',
        'extracted_data',
        'extracted_at',
        'extraction_confidence',
        'extraction_status',
        'extraction_error',
    ];

    protected $casts = [
        'process_tags' => 'array',
        'approved_at' => 'datetime',
        'extracted_data' => 'array',
        'extracted_at' => 'datetime',
        'extraction_confidence' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }
}
