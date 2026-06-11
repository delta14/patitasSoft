<?php
declare(strict_types=1);

namespace App\Domains\Tenant\Traits;

use Exception;

trait BelongsToTenant
{
    /**
     * Inicializa el trait vinculando el evento de creación.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            $tenantId = session('tenant_id');

            if (!$tenantId) {
                // Permitir creación sin tenant_id en consola o seeders si se especifica manualmente
                if ($model->tenant_id) {
                    return;
                }
                
                throw new Exception("No se ha establecido un contexto de Tenant activo para crear este registro (" . get_class($model) . ").");
            }

            $model->tenant_id = $tenantId;
        });
    }

    /**
     * Obtiene la relación con el Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Tenant::class, 'tenant_id');
    }
}
