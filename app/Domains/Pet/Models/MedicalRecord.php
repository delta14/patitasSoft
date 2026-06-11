<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class MedicalRecord extends Model
{
    use HasUuid7, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'pet_id',
        'critical_notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtiene la mascota asociada a este expediente.
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    /**
     * Obtiene las consultas médicas registradas en este expediente.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'medical_record_id');
    }
}
