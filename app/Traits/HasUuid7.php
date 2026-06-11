<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

trait HasUuid7
{
    use HasUuids;

    /**
     * Genera un nuevo ID único de tipo UUIDv7.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
}
