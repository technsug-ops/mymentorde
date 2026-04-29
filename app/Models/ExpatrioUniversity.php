<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Expatrio Study Buddy üniversite kataloğu (~500 Almanya üni).
 * Tek seferlik sync ile DB'ye yazılır, formdaki "hedef üniversite" dropdown'u
 * için kaynak.
 *
 * Multi-tenant scope YOK — Almanya üniversite listesi tüm company'lere ortak.
 */
class ExpatrioUniversity extends Model
{
    protected $table = 'expatrio_universities';

    /** Expatrio UUID — int olmadığı için autoincrement değil */
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'image_path', 'program_count', 'synced_at'];

    protected $casts = [
        'program_count' => 'integer',
        'synced_at'     => 'datetime',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(ExpatrioProgram::class, 'university_id');
    }
}
