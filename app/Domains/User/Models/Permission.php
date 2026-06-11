<?php
declare(strict_types=1);

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relación con los roles que tienen este permiso asignado.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        );
    }
}
