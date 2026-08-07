<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;

/**
 * Otomatik danışman ataması.
 *
 * DANIŞMAN OPERASYON ŞİRKETİNDEN GELİR, adayın firmasından değil.
 *
 * Partner firma öğrenciyi devrediyor, süreci MentorDE yürütüyor — partnerin
 * kendi danışmanı yok. Ağaçta yukarı çıkıp danışmanı olan ilk şirket bulunur;
 * kendi danışmanı olan firma kendisini kullanır.
 *
 * Sorgular BİLEREK kapsam dışı: adayın firması operasyon şirketini göremez.
 */
class AdvisorAssignmentService
{
    private const ADVISOR_ROLES = [User::ROLE_SENIOR, User::ROLE_MENTOR];

    /**
     * @param  int  $companyId  Adayın/öğrencinin şirketi
     * @param  string  $applicationType  Uzmanlık eşleşmesi için (boş = hepsi)
     */
    public function pickFor(int $companyId, string $applicationType = ''): ?string
    {
        $operatingCompanyId = Company::operatingCompanyId($companyId);

        if ($operatingCompanyId === null) {
            return null;
        }

        $advisors = User::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $operatingCompanyId)
            ->whereIn('role', self::ADVISOR_ROLES)
            ->where('is_active', true)
            ->where('auto_assign_enabled', true)
            ->orderBy('id')
            ->get(['email', 'max_capacity', 'senior_type', 'advisor_specialties']);

        if ($advisors->isEmpty()) {
            return null;
        }

        $pool = $this->matchByType($advisors, $applicationType);
        $emails = $pool->pluck('email')->filter()->values();

        if ($emails->isEmpty()) {
            return null;
        }

        $loads = $this->currentLoads($emails->all());

        $eligible = $pool->filter(function (User $advisor) use ($loads): bool {
            $email = (string) ($advisor->email ?? '');

            if ($email === '') {
                return false;
            }

            if (!$advisor->max_capacity) {
                return true;
            }

            return (int) ($loads[$email] ?? 0) < (int) $advisor->max_capacity;
        })->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        // ÜST FİRMANIN SEÇTİĞİ danışman varsa o öncelikli.
        //
        // Otomatik dağıtım en az yüklü kişiyi seçiyor; yükler eşitken sıralama
        // hep aynı kişiyi öne çıkarıyor ve pratikte her yeni aday ona düşüyor.
        // Üst firma "bu partnerin işlerine şu danışman baksın" diyebilmeli.
        //
        // Seçilen kişi havuzda ve UYGUN olmalı: pasifse, otomatik atamaya
        // kapalıysa ya da kapasitesi dolduysa yok sayılır ve normal dağıtıma
        // düşülür — aksi halde seçim, kapasite kuralını sessizce delerdi.
        $preferred = $this->preferredAdvisor($companyId);

        if ($preferred !== null) {
            $match = $eligible->first(
                fn (User $a): bool => strtolower((string) $a->email) === $preferred
            );

            if ($match !== null) {
                return (string) $match->email;
            }
        }

        // En az yüklü olana ver.
        return (string) $eligible
            ->sortBy(fn (User $a): int => (int) ($loads[(string) $a->email] ?? 0))
            ->first()
            ->email;
    }

    /**
     * Firma için seçilmiş varsayılan danışmanın e-postası — yoksa null.
     *
     * ⚠ Kapsamsız: adayın firması kendi kaydını firma kapsamlı sorguyla
     * okuyamayabilir; bu servis zaten operasyon şirketi adına çalışıyor.
     */
    private function preferredAdvisor(int $companyId): ?string
    {
        if ($companyId <= 0) {
            return null;
        }

        $email = Company::query()
            ->withoutGlobalScope('company')
            ->whereKey($companyId)
            ->value('default_advisor_email');

        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    /**
     * Uzmanlığı eşleşenler; hiçbiri eşleşmezse havuzun tamamı.
     *
     * ── ÇOK ETİKET ───────────────────────────────────────────────────────
     * Bir danışman birden fazla alanda uzman olabilir (Bachelor + Master
     * gibi). Etiketlerden HERHANGİ BİRİ başvuru türüyle eşleşiyorsa aday
     * ona atanabilir.
     *
     * ETİKETSİZ = GENEL. Hiç etiketi olmayan danışman her başvuruya uygun
     * sayılır; aksi halde etiketleme başlar başlamaz etiketlenmemiş herkes
     * havuzdan sessizce düşerdi.
     *
     * `senior_type` geriye uyum için hâlâ okunuyor: etiket girilmemiş ama
     * eski alanı doldurulmuş danışmanlar çalışmaya devam etsin.
     *
     * @param  \Illuminate\Support\Collection<int,User>  $advisors
     * @return \Illuminate\Support\Collection<int,User>
     */
    private function matchByType($advisors, string $applicationType)
    {
        $type = strtolower(trim($applicationType));

        if ($type === '') {
            return $advisors;
        }

        $matched = $advisors->filter(function (User $advisor) use ($type): bool {
            $tags = $advisor->advisorSpecialties();

            if ($tags !== []) {
                return in_array($type, $tags, true);
            }

            // Etiket yok — eski tek değerli alana bak, o da boşsa genel.
            $legacy = strtolower(trim((string) ($advisor->senior_type ?? '')));

            return $legacy === '' || $legacy === $type;
        })->values();

        return $matched->isNotEmpty() ? $matched : $advisors;
    }

    /**
     * Danışmanın TOPLAM yükü — hangi firmanın öğrencisi olduğu fark etmez.
     *
     * Şirkete göre sayılsaydı partner bağlamında MentorDE danışmanının mevcut
     * yükü 0 görünür, kapasitesi dolu danışmana atama yapılırdı.
     *
     * @param  list<string>  $emails
     * @return array<string,int>
     */
    private function currentLoads(array $emails): array
    {
        $students = StudentAssignment::query()
            ->withoutGlobalScope('company')
            ->whereIn('senior_email', $emails)
            ->where('is_archived', false)
            ->selectRaw('senior_email, COUNT(*) as total')
            ->groupBy('senior_email')
            ->pluck('total', 'senior_email');

        $guests = GuestApplication::query()
            ->withoutGlobalScope('company')
            ->whereIn('assigned_senior_email', $emails)
            ->where('converted_to_student', false)
            ->where('is_archived', false)
            ->selectRaw('assigned_senior_email, COUNT(*) as total')
            ->groupBy('assigned_senior_email')
            ->pluck('total', 'assigned_senior_email');

        $out = [];

        foreach ($emails as $email) {
            $out[$email] = (int) ($students[$email] ?? 0) + (int) ($guests[$email] ?? 0);
        }

        return $out;
    }
}
