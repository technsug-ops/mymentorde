<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestRegistrationField;
use App\Services\GuestRegistrationFieldSchemaService;
use App\Support\ApplicationTypes;
use App\Support\GuestRegistrationFormCatalog;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Başvuru türüne göre alan görünürlüğü — TOPLU işaretleme.
 *
 * ── NEDEN AYRI EKRAN ────────────────────────────────────────────────────
 * Etiket alan düzenleyicisinde de var, ama oradan 114 alanı işaretlemek
 * her alan için aç–seç–kaydet demek. Bu ekran hepsini tek sayfada gösterip
 * TEK kaydetmeyle yazıyor; iş bir oturumda bitiyor.
 *
 * ── NE YAPMIYOR ─────────────────────────────────────────────────────────
 * Alan eklemiyor, silmiyor, sırasını değiştirmiyor. Yalnızca "hangi türde
 * görünsün" sorusunu cevaplıyor. Tek işi olan ekran, yanlışlıkla başka bir
 * şeyi bozamaz.
 */
class FormFieldTypeController extends Controller
{
    public function __construct(
        private readonly GuestRegistrationFieldSchemaService $schema
    ) {
    }

    private function companyId(): int
    {
        return (int) (TenantContext::writeId() ?? 0);
    }

    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        // Merkezî tanım firmanın kendi satırlarında; yoksa fabrika (0)
        // satırları gösterilir ki ekran boş açılmasın.
        $this->schema->ensureDefaults($companyId);

        $fields = $this->editableFields($companyId);

        return view('manager.form-field-types.index', [
            'sections'    => $fields->groupBy('section_key'),
            'level1Keys'  => $this->level1Keys(),
            'types'       => ApplicationTypes::LABELS,
            'fieldCount'  => $fields->count(),
            'taggedCount' => $fields->filter(fn ($f) => ! empty($f->applicable_types))->count(),
            'ownsRows'    => $fields->isNotEmpty() && (int) $fields->first()->company_id === $companyId,
            'companyId'   => $companyId,
        ]);
    }

    /**
     * Tek kaydetmede tüm etiketleri yaz.
     *
     * ⚠ Gönderilmeyen alan "hiçbir tür seçilmemiş" demek, "dokunma" değil:
     * ekran bütün alanları listeliyor ve kutusu boş olan alan tarayıcıda
     * hiç gönderilmez. Bu yüzden EKRANDAKİ alanların tamamı üzerinden
     * dönülüyor; aksi halde bir etiketi kaldırmak imkânsız olurdu.
     */
    public function update(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();

        $request->validate([
            'types'     => ['nullable', 'array'],
            'types.*'   => ['nullable', 'array'],
            'types.*.*' => ['string'],
        ]);

        $submitted = (array) $request->input('types', []);
        $fields    = $this->editableFields($companyId);

        // ⚠ Yalnızca EKRANDA olan alanlar güncelleniyor. İstek gövdesindeki
        // rastgele bir id ile başka firmanın alanı değiştirilemesin diye
        // sınır burada: kaynak liste sunucudan geliyor, istekten değil.
        $changed = 0;

        foreach ($fields as $field) {
            $next = ApplicationTypes::sanitizeList($submitted[$field->id] ?? []) ?: null;
            $current = ApplicationTypes::sanitizeList($field->applicable_types) ?: null;

            if ($next === $current) {
                continue;
            }

            $field->forceFill(['applicable_types' => $next])->save();
            $changed++;
        }

        return back()->with('status', $changed === 0
            ? 'Değişiklik yok.'
            : $changed . ' alanın tür ayarı güncellendi.');
    }

    /**
     * Düzenlenebilir alanlar: firmanın kendi satırları, yoksa fabrika.
     *
     * @return \Illuminate\Support\Collection<int,GuestRegistrationField>
     */
    private function editableFields(int $companyId)
    {
        $own = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->orderBy('section_order')->orderBy('sort_order')->orderBy('id')
            ->get();

        if ($own->isNotEmpty()) {
            return $own;
        }

        return GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', 0)
            ->orderBy('section_order')->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /**
     * Level 1 alan anahtarları — adayın İLK doldurduğu form.
     *
     * Tür seçimi `/apply`'da yapıldığı için ayrımın asıl karşılığını
     * gösteren yer burası; ekranda ayrıca işaretleniyor.
     *
     * @return list<string>
     */
    private function level1Keys(): array
    {
        return collect(GuestRegistrationFormCatalog::groupsByLevel(1))
            ->flatMap(fn (array $g) => $g['fields'] ?? [])
            ->pluck('key')
            ->map(fn ($k) => (string) $k)
            ->all();
    }
}
