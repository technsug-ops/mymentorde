<?php

namespace App\Observers;

use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;

/**
 * K3 — Model observer: update/delete olaylarını audit_trails'e yazar.
 * AppServiceProvider'da register edilmesi gerekir.
 */
class AuditObserver
{
    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if (empty($changes)) {
            return;
        }

        // Hassas alanları filtrele
        $skip = ['updated_at', 'remember_token', 'password'];
        $changes = array_diff_key($changes, array_flip($skip));
        if (empty($changes)) {
            return;
        }

        AuditTrail::create([
            'company_id'  => method_exists($model, 'getCompanyId') ? $model->getCompanyId() : null,
            'user_id'     => auth()->id(),
            'action'      => 'update',
            'entity_type' => $model->getTable(),
            'entity_id'   => (string) $model->getKey(),
            'old_values'  => array_intersect_key($model->getOriginal(), $changes),
            'new_values'  => $changes,
            'ip_address'  => request()->ip(),
            'request_url' => request()->fullUrl(),
        ]);
    }

    public function deleted(Model $model): void
    {
        AuditTrail::create([
            'company_id'  => null,
            'user_id'     => auth()->id(),
            'action'      => 'delete',
            'entity_type' => $model->getTable(),
            'entity_id'   => (string) $model->getKey(),
            'ip_address'  => request()->ip(),
            'request_url' => request()->fullUrl(),
        ]);
    }
}
