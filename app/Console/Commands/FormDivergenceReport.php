<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\GuestRegistrationField;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Hangi firmalar ortak form şablonundan AYRIŞMIŞ?
 *
 * ── NEDEN GEREKLİ ───────────────────────────────────────────────────────
 * Eskiden bir firma formu ilk kez açtığında katalogun tamamı ona
 * kopyalanıyordu. Kopyası olan firma merkezden yapılan form değişikliğini
 * ALMAZ — ve bu hiçbir yerde görünmez. Form güncellenir, bazı firmalar
 * eski formda kalır, kimse fark etmez.
 *
 * Kopyalama durduruldu (bkz. GuestRegistrationFieldSchemaService::
 * ensureDefaults) ama önceden oluşmuş kopyalar yerinde duruyor. Bu komut
 * onları listeler.
 *
 * Kullanım:  php artisan form:divergence
 */
class FormDivergenceReport extends Command
{
    protected $signature = 'form:divergence';

    protected $description = 'Ortak form sablonundan ayrismis firmalari listeler';

    public function handle(): int
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            $this->warn('guest_registration_fields tablosu yok.');

            return self::SUCCESS;
        }

        $shared = GuestRegistrationField::query()->where('company_id', 0)->count();

        $this->line("Ortak sablon (company_id=0): {$shared} alan");
        $this->newLine();

        $diverged = GuestRegistrationField::query()
            ->where('company_id', '>', 0)
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->orderBy('company_id')
            ->get();

        if ($diverged->isEmpty()) {
            $this->info('Ayrismis firma YOK — tum firmalar ortak sablonu kullaniyor.');

            return self::SUCCESS;
        }

        $this->warn('Asagidaki firmalarin KENDI form kopyasi var.');
        $this->warn('Merkezden yapilan form degisiklikleri bu firmalara ULASMAZ:');
        $this->newLine();

        $rows = $diverged->map(function ($row) {
            $company = Company::query()->withoutGlobalScope('company')->find($row->company_id);

            return [
                $row->company_id,
                $company->name ?? '(silinmis)',
                $row->total,
            ];
        })->all();

        $this->table(['Firma ID', 'Firma', 'Alan sayisi'], $rows);

        $this->newLine();
        $this->line('Bir firmanin ortak sablona DONMESI icin o firmanin satirlari silinmeli.');
        $this->line('Ozellestirmesi bilerek yapildiysa oldugu gibi birakin.');

        return self::SUCCESS;
    }
}
