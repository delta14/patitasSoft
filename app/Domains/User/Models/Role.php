<?php
declare(strict_types=1);

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class Role extends Model
{
    use HasUuid7, BelongsToTenant;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'name',
        'guard_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relación con los usuarios que tienen este rol.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'model_has_roles',
            'role_id',
            'model_id'
        )->wherePivot('model_type', \App\Models\User::class);
    }

    /**
     * Relación con los permisos asignados a este rol.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }
}
