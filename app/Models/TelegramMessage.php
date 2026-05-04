<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'source', 'sender_hash', 'sent_at', 'text', 'is_question', 'is_short',
        'text_len', 'year', 'month', 'dow', 'hour', 'topics', 'imported_batch',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'is_question' => 'boolean',
        'is_short'    => 'boolean',
        'topics'      => 'array',
    ];

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) $q->where('sent_at', '>=', $from . ' 00:00:00');
        if ($to)   $q->where('sent_at', '<=', $to . ' 23:59:59');
        return $q;
    }

    public function scopeBySource(Builder $q, ?string $source): Builder
    {
        return $source ? $q->where('source', $source) : $q;
    }

    public function scopeByTopic(Builder $q, ?string $topic): Builder
    {
        if (!$topic) return $q;
        // JSON_CONTAINS MySQL 5.7+
        return $q->whereJsonContains('topics', $topic);
    }

    public function scopeOnlyQuestions(Builder $q, bool $only = true): Builder
    {
        return $only ? $q->where('is_question', true) : $q;
    }

    public function scopeMinLength(Builder $q, int $min): Builder
    {
        return $q->where('text_len', '>=', $min);
    }
}
