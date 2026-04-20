<?php

namespace Database\Seeders;

use App\Models\BusinessContract;
use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo veri: Manager Contracts Hub (/my-contracts) sayfasını dolu göstermek için.
 *
 * - business_contracts tablosuna staff + dealer tipleri için örnek kayıtlar
 * - guest_applications için contract_status=approved demo kayıtları
 *
 * Idempotent: önceki demo kayıtlar (contract_no LIKE 'DEMO-%') önce silinir.
 * FK checks off çünkü template_id gerçek bir template'e ait olmayabilir.
 *
 * Çalıştır: php artisan db:seed --class=ContractsHubDemoSeeder
 */
class ContractsHubDemoSeeder extends Seeder
{
    public function run(): void
    {
        $prefix = 'DEMO-';
        $pdfDir = 'contracts-demo';

        // Önce önceki demo kayıtları + PDF dosyaları temizle
        DB::table('business_contracts')->where('contract_no', 'like', $prefix . '%')->delete();
        $absPdfDir = storage_path('app/' . $pdfDir);
        if (is_dir($absPdfDir)) {
            array_map('unlink', glob($absPdfDir . '/*.pdf'));
        }
        if (!is_dir($absPdfDir)) {
            mkdir($absPdfDir, 0755, true);
        }

        $now = Carbon::now();

        // ─── 1) Staff sözleşmeleri — farklı rollerde 8 personel ──────────────
        $staffUsers = User::query()
            ->whereIn('role', ['manager', 'senior', 'mentor', 'marketing_admin', 'operations_admin', 'marketing_staff', 'operations_staff'])
            ->where('role', '!=', 'manager') // manager kendi iş sözleşmesini profil sayfasında görür, hub'da DEĞİL
            ->limit(8)
            ->get(['id', 'name', 'email', 'role']);

        $staffContracts = [];
        $i = 1;
        foreach ($staffUsers as $u) {
            $issuedDays = rand(30, 180);
            $issuedAt   = $now->copy()->subDays($issuedDays);
            $signedAt   = $issuedAt->copy()->addDays(rand(1, 7));
            $approvedAt = $signedAt->copy()->addDays(rand(0, 3));
            $status     = $i % 3 === 0 ? 'signed_uploaded' : 'approved';

            $title = match ($u->role) {
                'manager'          => 'Manager İş Sözleşmesi',
                'senior', 'mentor' => 'Eğitim Danışmanı İş Sözleşmesi',
                'marketing_admin'  => 'Marketing Admin Sözleşmesi',
                'operations_admin' => 'Operasyon Admin Sözleşmesi',
                'marketing_staff'  => 'Marketing Personeli Sözleşmesi',
                'operations_staff' => 'Operasyon Personeli Sözleşmesi',
                default            => 'Personel Sözleşmesi',
            };

            $contractNo = sprintf('%sSTF-%04d', $prefix, $i);
            $pdfPath    = $this->generateDemoPdf($pdfDir, $contractNo, $title, $u->name, $u->role, $u->email);

            $staffContracts[] = [
                'company_id'       => 1,
                'contract_type'    => 'staff',
                'dealer_id'        => null,
                'user_id'          => $u->id,
                'template_id'      => 1,
                'contract_no'      => $contractNo,
                'title'            => $title,
                'body_text'        => "Demo sözleşme metni — {$u->name} ({$u->role}).",
                'meta'             => json_encode(['demo' => true]),
                'status'           => $status,
                'issued_at'        => $issuedAt,
                'signed_at'        => $signedAt,
                'approved_at'      => $status === 'approved' ? $approvedAt : null,
                'signed_file_path' => $pdfPath,
                'issued_by'        => 1,
                'approved_by'      => $status === 'approved' ? 1 : null,
                'notes'            => null,
                'created_at'       => $issuedAt,
                'updated_at'       => $approvedAt,
            ];
            $i++;
        }

        // ─── 2) Dealer sözleşmeleri — mevcut tüm dealer'lar için ────────────
        $dealers = Dealer::query()->limit(6)->get(['id', 'name', 'code', 'dealer_type_code']);

        $dealerContracts = [];
        $j = 1;
        foreach ($dealers as $d) {
            $issuedAt   = $now->copy()->subDays(rand(20, 200));
            $signedAt   = $issuedAt->copy()->addDays(rand(1, 10));
            $approvedAt = $signedAt->copy()->addDays(rand(0, 5));

            $typeLabel = match ($d->dealer_type_code) {
                'lead_generation'    => 'Lead Generation Partner',
                'freelance_danisman' => 'Freelance Danışman',
                'operational'        => 'Operasyon Dealer',
                'referrer'           => 'Referans Partner',
                default              => 'Partner Sözleşmesi',
            };

            $contractNo = sprintf('%sDLR-%04d', $prefix, $j);
            $pdfPath    = $this->generateDemoPdf($pdfDir, $contractNo, $typeLabel . ' Sözleşmesi', $d->name, 'dealer', $d->code);

            $dealerContracts[] = [
                'company_id'       => 1,
                'contract_type'    => 'dealer',
                'dealer_id'        => $d->id,
                'user_id'          => null,
                'template_id'      => 1,
                'contract_no'      => $contractNo,
                'title'            => $typeLabel . ' Sözleşmesi',
                'body_text'        => "Demo dealer sözleşmesi — {$d->name} ({$d->code}).",
                'meta'             => json_encode(['demo' => true]),
                'status'           => 'approved',
                'issued_at'        => $issuedAt,
                'signed_at'        => $signedAt,
                'approved_at'      => $approvedAt,
                'signed_file_path' => $pdfPath,
                'issued_by'        => 1,
                'approved_by'      => 1,
                'notes'            => null,
                'created_at'       => $issuedAt,
                'updated_at'       => $approvedAt,
            ];
            $j++;
        }

        // ─── Insert business contracts (FK bypass — template_id=1 var olmayabilir) ──
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('business_contracts')->insert(array_merge($staffContracts, $dealerContracts));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ─── 3) Guest hizmet sözleşmeleri — bazı guest'leri approved yap ────
        // Mevcut guest_applications'tan örnek alıp contract_status ve tarihleri güncelle.
        $guests = GuestApplication::query()
            ->limit(12)
            ->get(['id', 'first_name', 'last_name', 'application_type', 'contract_status']);

        $appTypes = ['bachelor', 'master', 'phd', 'ausbildung', 'language', 'prep', 'visa'];
        $updated = 0;
        foreach ($guests as $idx => $g) {
            $approvedAt = $now->copy()->subDays(rand(5, 120));
            $appType    = $appTypes[$idx % count($appTypes)];

            GuestApplication::query()
                ->where('id', $g->id)
                ->update([
                    'contract_status'      => 'approved',
                    'contract_approved_at' => $approvedAt,
                    'contract_requested_at'=> $approvedAt->copy()->subDays(3),
                    'contract_signed_at'   => $approvedAt->copy()->subDays(1),
                    'application_type'     => $appType,
                ]);
            $updated++;
        }

        $this->command?->info('Contracts Hub demo verisi yüklendi:');
        $this->command?->info('  - ' . count($staffContracts) . ' staff sözleşme (PDF ile)');
        $this->command?->info('  - ' . count($dealerContracts) . ' dealer sözleşme (PDF ile)');
        $this->command?->info('  - ' . $updated . ' guest sözleşme approved yapıldı');
    }

    private function generateDemoPdf(string $dir, string $contractNo, string $title, string $ownerName, string $role, string $identifier): string
    {
        $date = now()->format('d.m.Y H:i');
        $html = <<<HTML
        <div style="font-family:DejaVu Sans,sans-serif;font-size:12px;color:#333;padding:30px;">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="font-size:10px;color:#888;">{$date}</div>
                <div style="font-size:10px;color:#888;">{$contractNo} — {$title}</div>
            </div>
            <h1 style="font-size:18px;text-align:center;margin-bottom:20px;">{$title}</h1>
            <p style="font-size:11px;color:#666;">Sözleşme No: <strong>{$contractNo}</strong> · MentorDE</p>
            <hr style="border:none;border-top:1px solid #ccc;margin:15px 0;">
            <p>Bu sözleşme <strong>{$ownerName}</strong> ({$role}) ile MentorDE arasında yapılmıştır.</p>
            <p>Taraflar karşılıklı mutabakat çerçevesinde aşağıdaki koşulları kabul eder:</p>
            <ol style="margin-top:10px;line-height:2;">
                <li>Hizmet kapsamı belirlenmiş çerçevededir.</li>
                <li>Ücretlendirme ayrı bir ekte belirtilmiştir.</li>
                <li>Gizlilik hükümleri 2 yıl süreyle geçerlidir.</li>
                <li>Taraflardan biri 30 gün önceden bildirerek sözleşmeyi feshedebilir.</li>
            </ol>
            <br><br>
            <div style="display:flex;justify-content:space-between;margin-top:30px;">
                <div style="border-top:1px solid #999;padding-top:5px;width:200px;text-align:center;">MentorDE Yönetim</div>
                <div style="border-top:1px solid #999;padding-top:5px;width:200px;text-align:center;">{$ownerName}</div>
            </div>
            <hr style="border:none;border-top:1px solid #ccc;margin:30px 0 10px;">
            <p style="font-size:9px;color:#999;text-align:center;">MentorDE — Gizli & Resmi Belge — {$contractNo}</p>
        </div>
        HTML;

        $filename = strtolower(str_replace([' ', '/'], '-', $contractNo)) . '.pdf';
        $path     = $dir . '/' . $filename;

        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        file_put_contents(storage_path('app/' . $path), $pdf->output());

        return $path;
    }
}
