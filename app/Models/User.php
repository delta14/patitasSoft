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
}
