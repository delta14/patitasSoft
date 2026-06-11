<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Exception;

class JwtService
{
    private string $secret;
    private int $accessTtl;
    private int $refreshTtl;

    public function __construct()
    {
        $this->secret = config('app.jwt_secret') ?: (env('JWT_SECRET') ?: 'default_jwt_secret_should_change_in_production');
        $this->accessTtl = (int) (env('JWT_ACCESS_TTL', 15)); // 15 minutos por defecto
        $this->refreshTtl = (int) (env('JWT_REFRESH_TTL', 10080)); // 7 días por defecto
    }

    /**
     * Genera un par de tokens (Access y Refresh) para un usuario.
     *
     * @param User $user
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function generateTokenPair(User $user): array
    {
        $currentTime = time();
        
        // Payload del Access Token
        $accessPayload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'jti' => (string) Str::uuid(),
            'iat' => $currentTime,
            'nbf' => $currentTime,
            'exp' => $currentTime + ($this->accessTtl * 60),
            'tenant_id' => $user->tenant_id,
            'role' => $user->role,
            'nombre' => $user->nombre,
            'email' => $user->email,
        ];

        // Payload del Refresh Token (solo contiene sub, tenant_id y jti para revocación)
        $refreshPayload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'jti' => (string) Str::uuid(),
            'iat' => $currentTime,
            'exp' => $currentTime + ($this->refreshTtl * 60),
            'tenant_id' => $user->tenant_id,
        ];

        $accessToken = JWT::encode($accessPayload, $this->secret, 'HS256');
        $refreshToken = JWT::encode($refreshPayload, $this->secret, 'HS256');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTtl * 60,
        ];
    }

    /**
     * Decodifica y valida un token de acceso.
     *
     * @param string $token
     * @return object
     * @throws Exception
     */
    public function decodeAccessToken(string $token): object
    {
        if ($this->isTokenBlacklisted($token)) {
            throw new Exception('El token ha sido revocado.');
        }

        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Token inválido o expirado: ' . $e->getMessage());
        }
    }

    /**
     * Decodifica y valida un token de refresco.
     *
     * @param string $token
     * @return object
     * @throws Exception
     */
    public function decodeRefreshToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Token de refresco inválido o expirado.');
        }
    }

    /**
     * Revoca un token añadiendo su JTI a la blacklist en Redis.
     *
     * @param string $token
     * @return void
     */
    public function revokeToken(string $token): void
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $jti = $decoded->jti ?? null;
            $exp = $decoded->exp ?? null;

            if ($jti && $exp) {
                $ttl = $exp - time();
                if ($ttl > 0) {
                    // Guardar en Redis usando prefijo para evitar colisiones
                    Redis::setex('jwt_blacklist:' . $jti, $ttl, 'revoked');
                }
            }
        } catch (Exception $e) {
            // Si el token es ilegible o ya expiró, no es necesario revocarlo
        }
    }

    /**
     * Verifica si un token está en la blacklist de Redis.
     *
     * @param string $token
     * @return bool
     */
    public function isTokenBlacklisted(string $token): bool
    {
        try {
            // Intentar leer el payload sin validar expiración por si ya expiró pero queremos ver el jti
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]));
                if ($payload && isset($payload->jti)) {
                    return (bool) Redis::get('jwt_blacklist:' . $payload->jti);
                }
            }
        } catch (Exception $e) {
            // Fallar de forma segura (asumir que no es seguro si hay error)
        }
        return false;
    }
}
