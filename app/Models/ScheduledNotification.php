<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    protected $fillable = [
        'name', 'channel', 'category', 'subject', 'body_template',
        'target_role', 'target_email', 'source_type', 'company_id',
        'schedule_type', 'send_at', 'recurrence_time', 'recurrence_day',
        'recurrence_until', 'is_active', 'last_sent_at', 'sent_count',
        'created_by_email',
    ];

    protected $casts = [
        'send_at'           => 'datetime',
        'last_sent_at'      => 'datetime',
        'recurrence_until'  => 'datetime',
        'is_active'         => 'boolean',
        'sent_count'        => 'integer',
        'recurrence_day'    => 'integer',
    ];

    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = now();

        if ($this->recurrence_until && $now->gt($this->recurrence_until)) {
            return false;
        }

        return match ($this->schedule_type) {
            'once'    => $this->send_at && $now->gte($this->send_at) && $this->sent_count === 0,
            'daily'   => $this->isRecurrenceTimeDue($now),
            'weekly'  => $now->dayOfWeekIso === (int) $this->recurrence_day && $this->isRecurrenceTimeDue($now),
            'monthly' => $now->day === (int) $this->recurrence_day && $this->isRecurrenceTimeDue($now),
            default   => false,
        };
    }

    private function isRecurrenceTimeDue(\Carbon\Carbon $now): bool
    {
        if (!$this->recurrence_time) {
            return false;
        }
        [$h, $m] = explode(':', (string) $this->recurrence_time);
        $target = $now->copy()->setTime((int) $h, (int) $m, 0);
        // Son 5 dakika içinde gönderilebilir
        return $now->gte($target) && $now->lt($target->copy()->addMinutes(5))
            && (!$this->last_sent_at || !$this->last_sent_at->isToday());
    }

    public function renderBody(array $vars = []): string
    {
        $body = (string) $this->body_template;
        foreach ($vars as $key => $val) {
            $body = str_replace('{' . $key . '}', (string) $val, $body);
        }
        return $body;
    }
}
