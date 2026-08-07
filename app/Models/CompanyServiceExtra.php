<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Firmanın kendi ek hizmeti (paket dışı, tek tek satılan).
 *
 * Tanımlanmamışsa üst firmanınki, o da yoksa config'teki fabrika kataloğu
 * kullanılır (bkz. App\Support\ServiceCatalog).
 */
class CompanyServiceExtra extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'code', 'category', 'title', 'price', 'price_amount',
        'currency', 'description', 'is_active', 'sort_order', 'updated_by',
    ];

    protected $casts = [
        'price_amount' => 'float',
        'is_active'    => 'boolean',
        'sort_order'   => 'integer',
    ];

    /**
     * Config'teki dizi şekline çevir — okuma noktaları bunu bekliyor.
     *
     * @return array<string,mixed>
     */
    public function toCatalogArray(): array
    {
        return [
            'code'         => (string) $this->code,
            'category'     => (string) ($this->category ?? ''),
            'title'        => (string) $this->title,
            'price'        => (string) ($this->price ?: $this->formattedPrice()),
            'price_amount' => (float) $this->price_amount,
            'currency'     => (string) ($this->currency ?: 'EUR'),
            'description'  => (string) ($this->description ?? ''),
            'is_active'    => (bool) $this->is_active,
            'sort_order'   => (int) $this->sort_order,
        ];
    }

    private function formattedPrice(): string
    {
        return number_format((float) $this->price_amount, 0, ',', '.') . ' ' . ($this->currency ?: 'EUR');
    }
}
