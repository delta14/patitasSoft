<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetBreed extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'species_id',
        'name',
    ];

    /**
     * Obtiene la especie a la que pertenece esta raza.
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(PetSpecies::class, 'species_id');
    }

    /**
     * Obtiene las mascotas de esta raza.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'breed_id');
    }
}
