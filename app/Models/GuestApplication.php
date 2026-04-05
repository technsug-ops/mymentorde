<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class GuestApplication extends Model
{
    use BelongsToCompany, SoftDeletes;

    /**
     * fill() ile yazılabilecek alanlar.
     *
     * GÜVENLİK SINIRI — İki katman:
     *   1) WorkflowController: guest rotalarında fill() kullanılır, validate() daraltır.
     *   2) Bu liste: validate() listesi yanlışlıkla genişlese bile ikinci duvar olarak durur.
     *
     * Yeni alan eklerken sor: "Guest bunu POST ile değiştirirse ne olur?"
     * Yanıt "hesap/erişim/sözleşme durumu değişir" ise → bu listeye EKLEME,
     * iç kodu forceFill() veya açık property assignment ile yaz.
     */
    protected $fillable = [
        // ── Oluşturma zamanı (sistem tarafından bir kez set edilir, guest değiştiremez)
        'company_id', 'guest_user_id', 'tracking_token', 'email',
        'assigned_senior_email', 'assigned_at', 'assigned_by',
        'lead_source', 'dealer_code', 'campaign_code', 'tracking_link_code',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'click_id', 'landing_url', 'referrer_url', 'branch',

        // ── Kimlik & Tercihler (guest form aksiyonlarıyla güncelleyebilir)
        'first_name', 'last_name', 'phone', 'gender',
        'application_country', 'communication_language', 'application_type',
        'target_term', 'target_city', 'language_level', 'language_skills',
        'kvkk_consent', 'priority', 'risk_level', 'lead_status',
        'notes', 'status_message', 'docs_ready',

        // ── Kayıt Formu
        'registration_form_draft', 'registration_form_draft_saved_at',
        'registration_form_submitted_at',

        // ── Paket & Ek Hizmetler
        'selected_package_code', 'selected_package_title', 'selected_package_price',
        'selected_extra_services', 'package_selected_at',

        // ── Sözleşme talep akışı (guest başlatır, sistem ilerletir)
        'contract_status', 'contract_requested_at',
        'reopen_reason', 'reopen_requested_at',

        // ── Portal Tercihleri & Profil
        'preferred_locale', 'notifications_enabled',
        'notify_email', 'notify_whatsapp', 'notify_inapp',
        'profile_photo_path',
        'lead_score', 'lead_score_tier', 'last_senior_action_at',
        'qualification_status', 'lost_reason', 'lost_note', 'follow_up_date',

        // ─────────────────────────────────────────────────────────────────────
        // KASITLI OLARAK DIŞARIDA BIRAKILANLAR — yalnızca forceFill() ile yazılır:
        //
        //   converted_to_student   ← KRİTİK: student rolü kazanımı
        //   converted_student_id   ← KRİTİK: student kaydına bağlantı
        //   converted_at           ← KRİTİK: dönüşüm zaman damgası
        //
        //   is_archived, archived_at, archived_by, archive_reason  ← manager-only
        //
        //   contract_signed_at, contract_approved_at               ← onay zaman damgaları
        //   contract_signed_file_path                              ← sunucu yolu
        //   contract_template_id, contract_template_code           ← manager-atandı
        //   contract_snapshot_text, contract_annex_*               ← sistem üretimi
        //   contract_generated_at                                  ← sistem zaman damgası
        //   contract_cancel_*, contract_cancelled_at/by            ← manager-only iptal
        //   reopen_decided_by, reopen_decided_at                   ← manager kararı
        // ─────────────────────────────────────────────────────────────────────
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    /** Atanmış senior kullanıcısı (email FK üzerinden) */
    public function senior(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_senior_email', 'email');
    }

    /** Portal hesabı (guest_user_id FK) */
    public function guestUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_user_id');
    }

    /** Dönüştürüldüğü öğrenci kaydı */
    public function studentAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentAssignment::class, 'student_id', 'converted_student_id');
    }

    protected $casts = [
        'kvkk_consent' => 'boolean',
        'docs_ready' => 'boolean',
        'converted_to_student' => 'boolean',
        'is_archived' => 'boolean',
        'guest_user_id' => 'integer',
        'archived_at' => 'datetime',
        'assigned_at' => 'datetime',
        'language_skills' => 'array',
        'registration_form_draft' => 'array',
        'registration_form_draft_saved_at' => 'datetime',
        'registration_form_submitted_at' => 'datetime',
        'package_selected_at' => 'datetime',
        'selected_extra_services' => 'array',
        'contract_requested_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'contract_approved_at' => 'datetime',
        'contract_generated_at' => 'datetime',
        'contract_cancelled_at' => 'datetime',
        'reopen_requested_at' => 'datetime',
        'reopen_decided_at' => 'datetime',
        'notifications_enabled' => 'boolean',
        'notify_email' => 'boolean',
        'notify_whatsapp' => 'boolean',
        'notify_inapp' => 'boolean',
        'lead_score' => 'integer',
        'converted_at' => 'datetime',
        'last_senior_action_at' => 'datetime',
        'follow_up_date' => 'date',
    ];
}
