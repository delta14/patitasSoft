<?php
declare(strict_types=1);

namespace App\Domains\Clinic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class Branch extends Model
{
    use HasUuid7, BelongsToTenant, SoftDeletes;

    public $timestamps = false; // Manejado por created_at estático en migración, o podemos activar timestamps si es necesario

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'phone',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtiene los usuarios que trabajan en esta sucursal.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'branch_users',
            'branch_id',
            'user_id'
        );
    }
}
