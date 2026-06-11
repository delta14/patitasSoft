<?php
declare(strict_types=1);

namespace App\Domains\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUuid7;

class Tenant extends Model
{
    use HasUuid7;

    protected $fillable = [
        'plan_id',
        'nombre',
        'subdominio',
        'dominio_personalizado',
        'status',
    ];

    /**
     * Obtiene el plan asociado al inquilino.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Obtiene las suscripciones del inquilino.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }

    /**
     * Obtiene los usuarios del inquilino.
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'tenant_id');
    }

    /**
     * Obtiene las sucursales del inquilino.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(\App\Domains\Clinic\Models\Branch::class, 'tenant_id');
    }
}
