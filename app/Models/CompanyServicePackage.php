<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Firmanın kendi hizmet paketi.
 *
 * Tanımlanmamışsa üst firmanınki, o da yoksa config'teki fabrika kataloğu
 * kullanılır (bkz. App\Support\ServiceCatalog).
 */
class CompanyServicePackage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'code', 'title', 'price', 'price_amount', 'currency',
        'includes', 'features', 'included_categories', 'included_extras',
        'max_universities', 'includes_visa', 'includes_housing',
        'support_level', 'validity_months', 'is_active', 'sort_order', 'updated_by',
    ];

    protected $casts = [
        'features'            => 'array',
        'included_categories' => 'array',
        'included_extras'     => 'array',
        'price_amount'        => 'float',
        'includes_visa'       => 'boolean',
        'includes_housing'    => 'boolean',
        'is_active'           => 'boolean',
        'sort_order'          => 'integer',
    ];

    /**
     * Config'teki dizi şekline çevir.
     *
     * 35 okuma noktası bu diziyi bekliyor; DB'ye geçiş onların hiçbirinin
     * veri şeklini bilmesini gerektirmesin diye burada normalize ediliyor.
     *
     * @return array<string,mixed>
     */
    public function toCatalogArray(): array
    {
        return [
            'code'                => (string) $this->code,
            'title'               => (string) $this->title,
            'price'               => (string) ($this->price ?: $this->formattedPrice()),
            'price_amount'        => (float) $this->price_amount,
            'currency'            => (string) ($this->currency ?: 'EUR'),
            'includes'            => (string) ($this->includes ?? ''),
            'is_active'           => (bool) $this->is_active,
            'sort_order'          => (int) $this->sort_order,
            'features'            => is_array($this->features) ? $this->features : [],
            'included_categories' => is_array($this->included_categories) ? $this->included_categories : [],
            'included_extras'     => is_array($this->included_extras) ? $this->included_extras : [],
            'max_universities'    => $this->max_universities !== null ? (int) $this->max_universities : null,
            'includes_visa'       => (bool) $this->includes_visa,
            'includes_housing'    => (bool) $this->includes_housing,
            'support_level'       => (string) ($this->support_level ?? ''),
            'validity_months'     => $this->validity_months !== null ? (int) $this->validity_months : null,
        ];
    }

    /** Gösterim fiyatı girilmemişse sayıdan üret — ekranda boş kalmasın. */
    private function formattedPrice(): string
    {
        return number_format((float) $this->price_amount, 0, ',', '.') . ' ' . ($this->currency ?: 'EUR');
    }
}
