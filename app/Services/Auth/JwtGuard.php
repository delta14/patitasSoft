<?php
declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;

class JwtGuard implements Guard
{
    protected Request $request;
    protected JwtService $jwtService;
    protected ?Authenticatable $user = null;

    public function __construct(JwtService $jwtService, Request $request)
    {
        $this->jwtService = $jwtService;
        $this->request = $request;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();
        if (!$token) {
            return null;
        }

        try {
            $claims = $this->jwtService->decodeAccessToken($token);
            $userId = $claims->sub ?? null;

            if ($userId) {
                // Buscar el usuario. El scope global TenantScope filtrará por tenant.
                $user = User::find($userId);
                
                if ($user && $user->is_active) {
                    $this->user = $user;
                    return $this->user;
                }
            }
        } catch (Exception $e) {
            // Token inválido o revocado
        }

        return null;
    }

    public function id()
    {
        if ($this->user()) {
            return $this->user()->getAuthIdentifier();
        }
        return null;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    protected function getTokenFromRequest(): ?string
    {
        $header = $this->request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
