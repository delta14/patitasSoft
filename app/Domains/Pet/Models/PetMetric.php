<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class PetMetric extends Model
{
    use HasUuid7, BelongsToTenant;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'pet_id',
        'measured_by',
        'consultation_id',
        'weight_kg',
        'temperature_c',
        'heart_rate_bpm',
        'respiratory_rate_rpm',
        'systolic_bp',
        'diastolic_bp',
        'measured_at',
    ];

    protected $casts = [
        'weight_kg' => 'float',
        'temperature_c' => 'float',
        'heart_rate_bpm' => 'integer',
        'respiratory_rate_rpm' => 'integer',
        'systolic_bp' => 'integer',
        'diastolic_bp' => 'integer',
        'measured_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Obtiene la mascota asociada a esta métrica.
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    /**
     * Obtiene el veterinario que tomó la medición.
     */
    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'measured_by');
    }

    /**
     * Obtiene la consulta clínica asociada si existe.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }
}
