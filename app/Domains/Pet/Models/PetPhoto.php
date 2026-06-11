<?php
declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid7;

class PetPhoto extends Model
{
    use HasUuid7;

    const UPDATED_AT = null;

    protected $fillable = [
        'pet_id',
        'photo_url',
        'is_profile',
    ];

    protected $casts = [
        'is_profile' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Obtiene la mascota a la que pertenece esta foto.
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }
}
