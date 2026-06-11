<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuid7, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'nombre',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtiene las sucursales a las que pertenece el usuario.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domains\Clinic\Models\Branch::class,
            'branch_users',
            'user_id',
            'branch_id'
        );
    }

    /**
     * Relación con los roles del usuario (a través de la tabla model_has_roles).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domains\User\Models\Role::class,
            'model_has_roles',
            'model_id',
            'role_id'
        )->wherePivot('model_type', self::class);
    }

    /**
     * Comprueba si el usuario tiene un rol específico o uno de una lista de roles.
     */
    public function hasRole(string|array $roles): bool
    {
        // 1. Verificar el campo directo "role" en la tabla users
        $roleName = $this->role;
        if (is_array($roles)) {
            if (in_array($roleName, $roles, true)) {
                return true;
            }
        } elseif ($roleName === $roles) {
            return true;
        }

        // 2. Verificar en la relación de roles asignados dinámicamente
        if (is_array($roles)) {
            return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
        }

        return $this->roles->contains('name', $roles);
    }

    /**
     * Comprueba si el usuario tiene un permiso específico.
     */
    public function hasPermission(string $permissionName): bool
    {
        // El Super Admin siempre tiene todos los permisos (bypass)
        if ($this->role === 'Super Admin' || $this->roles->contains('name', 'Super Admin')) {
            return true;
        }

        // Cargar los permisos del usuario a través de sus roles y verificar
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }

        return false;
    }
}
