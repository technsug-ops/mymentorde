<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StudentPayment extends Model
{
    protected $fillable = [
        'company_id',
        'student_id',
        'invoice_number',
        'description',
        'amount_eur',
        'currency',
        'due_date',
        'paid_at',
        'payment_method',
        'status',
        'notes',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'created_by',
        'contract_updated_at',
        'contract_change_log',
        'last_downloaded_at',
        'download_count',
    ];

    protected $casts = [
        'due_date'            => 'date',
        'paid_at'             => 'datetime',
        'contract_updated_at' => 'datetime',
        'last_downloaded_at'  => 'datetime',
        'amount_eur'          => 'decimal:2',
        'download_count'      => 'integer',
    ];

    // ── İndirme kaydı ──────────────────────────────────────────────────────

    public function recordDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    // ── Sözleşme değişikliğini kaydet ──────────────────────────────────────

    public function applyContractUpdate(array $changes, string $source = 'sözleşme'): void
    {
        $timestamp = now()->format('d.m.Y H:i');
        $lines = ["[{$timestamp}] {$source} güncellemesi:"];

        foreach ($changes as $field => $change) {
            $lines[] = "  • {$field}: {$change['from']} → {$change['to']}";
        }

        $newEntry = implode("\n", $lines);

        // Mevcut log varsa üstüne ekle (en yeni üstte)
        $existing = $this->contract_change_log ?? '';
        $log = $existing ? $newEntry . "\n\n" . $existing : $newEntry;

        $this->update([
            'contract_updated_at' => now(),
            'contract_change_log' => $log,
        ]);
    }

    // ── Değişiklik bildirimi okundu olarak işaretle ────────────────────────

    public function acknowledgeContractUpdate(): void
    {
        $this->update(['contract_updated_at' => null]);
    }

    // ── Bir sonraki fatura numarasını üret ──────────────────────────────────

    public static function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if ($last && preg_match('/INV-\d{4}-(\d+)/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return 'INV-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Otomatik durum güncellemesi (vadesi geçenleri işaretle) ────────────

    public static function markOverdue(): int
    {
        return static::where('status', 'pending')
            ->whereDate('due_date', '<', today())
            ->update(['status' => 'overdue']);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('status', 'overdue');
    }

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', 'paid');
    }

    // ── İlişkiler ───────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
