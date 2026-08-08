<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentBuilderTemplate extends Model
{
    use BelongsToCompany;

    protected $table = 'document_builder_templates';

    protected $fillable = [
        'company_id',
        'doc_type',
        'language',
        'name',
        'section_order',
        'section_templates',
        'variables',
        'is_active',
        'version',
        'created_by',
    ];

    protected $casts = [
        'section_order'     => 'array',
        'section_templates' => 'array',
        'variables'         => 'array',
        'is_active'         => 'boolean',
        'version'           => 'integer',
    ];

    public static array $docTypeLabels = [
        'cv'           => 'Lebenslauf (CV)',
        'motivation'   => 'Motivationsschreiben',
        'reference'    => 'Empfehlungsschreiben',
        'cover_letter' => 'Anschreiben',
        'sperrkonto'   => 'Sperrkonto-Antrag',
        'housing'      => 'Wohnheimsantrag',
    ];

    /** Fabrika belge şablonları (`company_id = 0`) her firmada görünür. */
    public static function tenantIncludesFactoryRows(): bool
    {
        return true;
    }

    /** Fabrika satırı: hiçbir firmaya ait değil, düzenlenemez/silinemez. */
    public function isFactoryRow(): bool
    {
        return (int) $this->company_id === 0;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
