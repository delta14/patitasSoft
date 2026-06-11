<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasUuid7;
use App\Domains\Tenant\Traits\BelongsToTenant;

class Pet extends Model
{
    use HasUuid7, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'species_id',
        'breed_id',
        'color_id',
        'name',
        'birth_date',
        'gender',
        'microchip',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtiene el dueño de la mascota.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    /**
     * Obtiene la especie de la mascota.
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(PetSpecies::class, 'species_id');
    }

    /**
     * Obtiene la raza de la mascota.
     */
    public function breed(): BelongsTo
    {
        return $this->belongsTo(PetBreed::class, 'breed_id');
    }

    /**
     * Obtiene el color de la mascota.
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(PetColor::class, 'color_id');
    }

    /**
     * Obtiene las fotos asociadas a la mascota.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PetPhoto::class, 'pet_id');
    }

    /**
     * Obtiene el expediente clínico único de la mascota.
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class, 'pet_id');
    }

    /**
     * Obtiene el historial de métricas y signos vitales de la mascota.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(PetMetric::class, 'pet_id');
    }
}
