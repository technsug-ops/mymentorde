<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAssetSavedSearch extends Model
{
    protected $table = 'digital_asset_saved_searches';

    protected $fillable = [
        'user_id',
        'name',
        'query_params',
    ];

    protected $casts = [
        'query_params' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
