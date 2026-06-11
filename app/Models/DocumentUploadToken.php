<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentUploadToken extends Model
{
    use BelongsToCompany;

    /**
     * Polymorphic target type sabitleri.
     * Yeni modül eklerken buraya bir sabit ekle, controller'da resolveTarget()
     * için handler kaydet.
     */
    public const TARGET_GUEST    = 'guest_application'; // Aday öğrenci (GuestApplication)
    public const TARGET_STUDENT  = 'student';           // Öğrenci (StudentAssignment.student_id)
    public const TARGET_USER     = 'user';              // Çalışan/personel (User.id) — HR onboarding
    public const TARGET_DEALER   = 'dealer';            // Bayi (Dealer.code)
    public const TARGET_CONTRACT = 'contract';          // İş sözleşmesi (BusinessContract.id) — imzalı PDF geri yükleme
    public const TARGET_TICKET   = 'ticket';            // Destek talebi (GuestTicket.id) — ek belge

    public const TARGET_TYPES = [
        self::TARGET_GUEST    => 'Aday Öğrenci',
        self::TARGET_STUDENT  => 'Öğrenci',
        self::TARGET_USER     => 'Çalışan',
        self::TARGET_DEALER   => 'Bayi',
        self::TARGET_CONTRACT => 'Sözleşme',
        self::TARGET_TICKET   => 'Destek Talebi',
    ];

    protected $fillable = [
        'company_id',
        'token',
        // Polymorphic (yeni canonical alanlar)
        'target_type',
        'target_id',
        'target_display_name',
        // Legacy (geriye uyum — dual-write ile senkron tutulur)
        'guest_application_id',
        'target_student_id',
        // Belge bilgileri
        'category_code',
        'category_name',
        'document_name_de',
        'custom_message',
        // Lifecycle
        'created_by_user_id',
        'recipient_email',
        'recipient_phone',
        'notification_channels',
        'reminder_first_sent_at',
        'reminder_final_sent_at',
        'whatsapp_first_sent_at',
        'whatsapp_final_sent_at',
        'max_uses',
        'used_count',
        'expires_at',
        'last_used_at',
        'last_used_ip',
        'last_used_user_agent',
        'document_id',
    ];

    protected $casts = [
        'expires_at'             => 'datetime',
        'last_used_at'           => 'datetime',
        'reminder_first_sent_at' => 'datetime',
        'reminder_final_sent_at' => 'datetime',
        'whatsapp_first_sent_at' => 'datetime',
        'whatsapp_final_sent_at' => 'datetime',
        'notification_channels'  => 'array',
        'max_uses'               => 'integer',
        'used_count'             => 'integer',
    ];

    /**
     * Default notification channels for tokens olusturuldugunda channel
     * belirtilmedi. Geriye uyum + safe fallback.
     */
    public function getNotificationChannelsAttribute($value): array
    {
        if ($value === null) {
            return ['email'];
        }
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        if (!is_array($decoded) || empty($decoded)) {
            return ['email'];
        }
        return array_values(array_intersect($decoded, ['email', 'whatsapp']));
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->notification_channels, true);
    }

    /**
     * Save öncesi: legacy alanlar set edilmişse polymorphic alanları senkronla
     * (ve tersi). Eski kod yeni kolonları doldurmasa bile polymorphic resolution
     * çalışır; yeni kod legacy alanları doldurmasa bile mevcut sorgular çalışır.
     */
    protected static function booted(): void
    {
        static::saving(function (self $row): void {
            // Legacy → polymorphic
            if (empty($row->target_type)) {
                if (!empty($row->guest_application_id)) {
                    $row->target_type = self::TARGET_GUEST;
                    $row->target_id   = (string) $row->guest_application_id;
                } elseif (!empty($row->target_student_id)) {
                    $row->target_type = self::TARGET_STUDENT;
                    $row->target_id   = (string) $row->target_student_id;
                }
            }

            // Polymorphic → legacy (geriye uyum)
            if ($row->target_type === self::TARGET_GUEST && empty($row->guest_application_id)) {
                $row->guest_application_id = is_numeric($row->target_id) ? (int) $row->target_id : null;
            }
            if ($row->target_type === self::TARGET_STUDENT && empty($row->target_student_id)) {
                $row->target_student_id = (string) $row->target_id;
            }
        });
    }

    public static function generateToken(): string
    {
        return Str::random(32);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->used_count >= $this->max_uses;
    }

    public function isUsable(): bool
    {
        return !$this->isExpired() && !$this->isExhausted();
    }

    public function markUsed(?string $ip = null, ?string $ua = null, ?int $documentId = null): void
    {
        $this->forceFill([
            'used_count'           => $this->used_count + 1,
            'last_used_at'         => CarbonImmutable::now(),
            'last_used_ip'         => $ip,
            'last_used_user_agent' => $ua ? mb_substr($ua, 0, 255) : null,
            'document_id'          => $documentId ?? $this->document_id,
        ])->save();
    }

    public function isFor(string $type): bool
    {
        return (string) $this->target_type === $type;
    }

    public function isForGuest(): bool    { return $this->isFor(self::TARGET_GUEST); }
    public function isForStudent(): bool  { return $this->isFor(self::TARGET_STUDENT); }
    public function isForUser(): bool     { return $this->isFor(self::TARGET_USER); }
    public function isForDealer(): bool   { return $this->isFor(self::TARGET_DEALER); }
    public function isForContract(): bool { return $this->isFor(self::TARGET_CONTRACT); }
    public function isForTicket(): bool   { return $this->isFor(self::TARGET_TICKET); }

    /**
     * Document.student_id alanına yazılacak owner string'i.
     * Her target_type için uygun pattern.
     */
    public function resolveDocumentOwnerId(): string
    {
        return match ($this->target_type) {
            self::TARGET_GUEST    => 'GST-' . str_pad((string) $this->target_id, 8, '0', STR_PAD_LEFT),
            self::TARGET_STUDENT  => (string) $this->target_id,
            self::TARGET_USER     => 'USR-' . str_pad((string) $this->target_id, 8, '0', STR_PAD_LEFT),
            self::TARGET_DEALER   => 'DLR-' . str_pad((string) $this->target_id, 8, '0', STR_PAD_LEFT),
            self::TARGET_CONTRACT => 'CTR-' . str_pad((string) $this->target_id, 8, '0', STR_PAD_LEFT),
            self::TARGET_TICKET   => 'TKT-' . str_pad((string) $this->target_id, 8, '0', STR_PAD_LEFT),
            default               => '',
        };
    }

    /**
     * Yüklenen dosyanın kaydedileceği storage dizini.
     */
    public function resolveStorageDir(): string
    {
        return match ($this->target_type) {
            self::TARGET_GUEST    => "guest-documents/{$this->target_id}",
            self::TARGET_STUDENT  => "student-documents/{$this->target_id}",
            self::TARGET_USER     => "user-documents/{$this->target_id}",
            self::TARGET_DEALER   => "dealer-documents/{$this->target_id}",
            self::TARGET_CONTRACT => "contract-files/{$this->target_id}/signed",
            self::TARGET_TICKET   => "ticket-attachments/{$this->target_id}",
            default               => "uploads/" . ($this->target_type ?: 'misc') . "/{$this->target_id}",
        };
    }

    public function guestApplication()
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }
}
