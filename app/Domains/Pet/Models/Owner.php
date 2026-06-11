<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class Owner extends Model
{
    use HasUuid7, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'dni_rfc', // Guardado encriptado en el controlador o servicio
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtiene los contactos del dueño de mascota.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(OwnerContact::class, 'owner_id');
    }

    /**
     * Obtiene las mascotas propiedad de este dueño.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'owner_id');
    }
}
