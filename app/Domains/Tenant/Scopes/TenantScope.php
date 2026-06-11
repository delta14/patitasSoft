<?php
declare(strict_types=1);

namespace App\Domains\Tenant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Aplica el scope global de filtrado por tenant.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = session('tenant_id');

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', '=', $tenantId);
        }
    }
}
