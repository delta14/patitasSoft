<?php
declare(strict_types=1);

namespace App\Domains\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid7;

class Subscription extends Model
{
    use HasUuid7;

    public $timestamps = false; // Manejado manualmente o mediante created_at estático

    protected $fillable = [
        'tenant_id',
        'stripe_subscription_id',
        'status',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Obtiene el inquilino dueño de esta suscripción.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
