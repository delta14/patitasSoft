<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetSpecies extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Obtiene las razas asociadas a esta especie.
     */
    public function breeds(): HasMany
    {
        return $this->hasMany(PetBreed::class, 'species_id');
    }

    /**
     * Obtiene las mascotas de esta especie.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'species_id');
    }
}
