<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetColor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Obtiene las mascotas de este color.
     */
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'color_id');
    }
}
