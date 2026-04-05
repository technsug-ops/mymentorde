<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * GDPR Madde 17 — Unutulma Hakkı
 *
 * Kişisel verileri (PII) fiziksel olarak silmek yerine anonimleştirir.
 * Bu sayede finansal kayıtlar ve iş süreç verileri korunurken
 * kişiyle ilişkilendirme mümkün olmaz.
 */
class AnonymizationService
{
    /**
     * Bir kullanıcının tüm PII alanlarını anonimleştirir.
     * Kullanıcı soft-delete edilir (ID ve foreign key'ler korunur).
     */
    public function anonymizeUser(User $user): void
    {
        $uid = $user->id;

        $user->forceFill([
            'name'           => "Anonimleştirilmiş Kullanıcı #{$uid}",
            'email'          => "anon-user-{$uid}@deleted.local",
            'password'       => Hash::make(Str::random(40)),
            'remember_token' => null,
        ])->saveQuietly();

        $user->delete(); // SoftDelete

        // Bu kullanıcının guest başvurularını da anonimleştir
        GuestApplication::withTrashed()
            ->where('guest_user_id', $uid)
            ->each(fn (GuestApplication $app) => $this->anonymizeGuestApplication($app));
    }

    /**
     * Bir guest başvurusundaki tüm PII alanlarını anonimleştirir.
     * Finansal veriler ve süreç durumları korunur.
     */
    public function anonymizeGuestApplication(GuestApplication $app): void
    {
        $aid = $app->id;

        // Profil fotoğrafını fiziksel olarak sil
        if (!empty($app->profile_photo_path)) {
            $this->deleteStorageFile($app->profile_photo_path);
        }

        $app->forceFill([
            'first_name'                    => 'Silinmiş',
            'last_name'                     => 'Başvuru',
            'email'                         => "anon-app-{$aid}@deleted.local",
            'phone'                         => null,
            'gender'                        => 'not_specified',
            'profile_photo_path'            => null,
            'registration_form_draft'       => null,
            'contract_snapshot_text'        => null,
            'contract_annex_kvkk_text'      => null,
            'contract_annex_commitment_text' => null,
            'notes'                         => null,
            'status_message'                => 'Bu kayıt GDPR kapsamında anonimleştirilmiştir.',
            'landing_url'                   => null,
            'referrer_url'                  => null,
        ])->saveQuietly();

        // Başvuruya ait belgeleri sil
        Document::where('guest_application_id', $app->id)
            ->each(function (Document $doc): void {
                $this->deleteStorageFile($doc->file_path ?? '');
                $doc->delete();
            });

        $app->delete(); // SoftDelete
    }

    /**
     * Bir bayi kaydının PII alanlarını anonimleştirir.
     */
    public function anonymizeDealer(Dealer $dealer): void
    {
        $did = $dealer->id;

        $dealer->forceFill([
            'name' => "Anonimleştirilmiş Bayi #{$did}",
        ])->saveQuietly();

        $dealer->delete(); // SoftDelete
    }

    /**
     * Bir öğrenci atama kaydının PII alanlarını anonimleştirir.
     */
    public function anonymizeStudentAssignment(StudentAssignment $assignment): void
    {
        $assignment->forceFill([
            'senior_email' => null,
            'archived_by'  => null,
        ])->saveQuietly();

        $assignment->delete(); // SoftDelete
    }

    /**
     * Storage'dan fiziksel dosyayı güvenle siler.
     */
    private function deleteStorageFile(string $path): void
    {
        $clean = trim($path);
        if ($clean === '') {
            return;
        }
        try {
            if (Storage::disk('public')->exists($clean)) {
                Storage::disk('public')->delete($clean);
            }
        } catch (\Throwable) {
            // Silme başarısız olursa loglama dışında işlemi engelleyen hata fırlatılmaz
        }
    }
}
