<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Auth\JwtService;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class AuthController extends Controller
{
    private JwtService $jwtService;
    private AuditService $auditService;

    public function __construct(JwtService $jwtService, AuditService $auditService)
    {
        $this->jwtService = $jwtService;
        $this->auditService = $auditService;
    }

    /**
     * Inicia sesión de un usuario y retorna los tokens JWT.
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): Response
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return response()->json([
                'error' => 'Debe especificar el contexto de la veterinaria (subdominio o cabecera X-Tenant-ID).'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $request->input('email');
        $throttleKey = 'login:' . $email . '|' . $request->ip();

        // 1. Rate Limiting: Máximo 5 intentos por minuto
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'error' => "Demasiados intentos de inicio de sesión. Por favor, intente en {$seconds} segundos."
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // 2. Intentar buscar el usuario (filtrado por el TenantScope automático)
        $user = User::where('email', $email)->first();

        // 3. Validar credenciales
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            // Auditar intento fallido
            $this->auditService->log(
                $tenantId,
                $user ? $user->id : null,
                'failed_login',
                'users',
                $user ? $user->id : 'guest',
                null,
                ['email' => $email]
            );

            return response()->json([
                'error' => 'Credenciales inválidas.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 4. Validar si el usuario está activo
        if (!$user->is_active) {
            return response()->json([
                'error' => 'La cuenta de usuario está desactivada.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 5. Autenticación exitosa: Limpiar limitador e iniciar sesión
        RateLimiter::clear($throttleKey);

        // Generar Access Token y Refresh Token
        $tokens = $this->jwtService->generateTokenPair($user);

        // Auditar login exitoso
        $this->auditService->log(
            $tenantId,
            $user->id,
            'login',
            'users',
            $user->id
        );

        // Retornar Access Token y configurar Refresh Token en una Cookie HttpOnly
        $cookie = cookie(
            'refresh_token',
            $tokens['refresh_token'],
            $this->getRefreshTtlMinutes(),
            '/',
            null,
            true, // secure
            true, // httpOnly
            false,
            'Strict'
        );

        return response()->json([
            'access_token' => $tokens['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['expires_in'],
        ])->withCookie($cookie);
    }

    /**
     * Cierra la sesión activa revocando el Access Token.
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): Response
    {
        $authorizationHeader = $request->header('Authorization');
        if ($authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = substr($authorizationHeader, 7);
            
            // Revocar token agregándolo a la blacklist de Redis
            $this->jwtService->revokeToken($token);

            $user = auth()->user();
            if ($user) {
                // Auditar logout
                $this->auditService->log(
                    session('tenant_id') ?? $user->tenant_id,
                    $user->id,
                    'logout',
                    'users',
                    $user->id
                );
            }
        }

        // Limpiar cookie de refresh token
        $cookie = cookie()->forget('refresh_token');

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ])->withCookie($cookie);
    }

    /**
     * Renueva el Access Token utilizando el Refresh Token.
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): Response
    {
        // 1. Intentar obtener el refresh token desde la cookie o el request
        $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json([
                'error' => 'Refresh token ausente.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // 2. Decodificar y validar refresh token
            $claims = $this->jwtService->decodeRefreshToken($refreshToken);
            $userId = $claims->sub ?? null;

            if (!$userId) {
                return response()->json([
                    'error' => 'Token de refresco inválido.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Forzar el contexto del tenant obtenido del token para mantener consistencia
            session(['tenant_id' => $claims->tenant_id]);
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$claims->tenant_id]);

            // 3. Buscar el usuario
            $user = User::find($userId);
            if (!$user || !$user->is_active) {
                return response()->json([
                    'error' => 'Usuario inactivo o no encontrado.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // 4. Generar nuevo par de tokens
            $tokens = $this->jwtService->generateTokenPair($user);

            // Actualizar la cookie de refresh token
            $cookie = cookie(
                'refresh_token',
                $tokens['refresh_token'],
                $this->getRefreshTtlMinutes(),
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );

            return response()->json([
                'access_token' => $tokens['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => $tokens['expires_in'],
            ])->withCookie($cookie);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Token de refresco inválido o expirado.'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Solicita recuperación de contraseña generando un token temporal.
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request): Response
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            return response()->json([
                'error' => 'Debe especificar el contexto de la veterinaria.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // Si el usuario existe, generamos el token de recuperación
        if ($user) {
            $token = Str::random(64);
            $createdAt = now();

            // Insertar o actualizar el token en la tabla password_reset_tokens
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => $createdAt
                ]
            );

            // Auditar solicitud de password reset
            $this->auditService->log(
                $tenantId,
                $user->id,
                'forgot_password',
                'users',
                $user->id
            );

            // En un entorno real se enviaría el correo. En desarrollo lo escribimos en los logs del sistema.
            logger()->info("Restablecimiento de contraseña solicitado para {$email}. Token de recuperación: {$token}");
        }

        // Retornamos respuesta genérica siempre por seguridad (mitigar User Enumeration)
        return response()->json([
            'message' => 'Si el correo electrónico existe en nuestro sistema, recibirá un enlace de restablecimiento.'
        ]);
    }

    /**
     * Restablece la contraseña de un usuario usando el token.
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(ResetPasswordRequest $request): Response
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            return response()->json([
                'error' => 'Debe especificar el contexto de la veterinaria.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $request->input('email');
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($request->input('token'), $record->token)) {
            return response()->json([
                'error' => 'El token de recuperación es inválido o no pertenece a este correo.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar expiración del token (60 minutos por defecto)
        $tokenLifetimeMinutes = 60;
        if (now()->diffInMinutes(\Carbon\Carbon::parse($record->created_at)) > $tokenLifetimeMinutes) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'error' => 'El token de recuperación ha expirado.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Actualizar contraseña del usuario
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($request->input('password'));
            $user->save();

            // Borrar el token de recuperación consumido
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Auditar cambio de contraseña
            $this->auditService->log(
                $tenantId,
                $user->id,
                'password_change',
                'users',
                $user->id
            );

            return response()->json([
                'message' => 'Contraseña restablecida correctamente.'
            ]);
        }

        return response()->json([
            'error' => 'El usuario no pudo ser localizado.'
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Obtiene el perfil del usuario autenticado actual y sus habilidades RBAC.
     * GET /api/v1/auth/me
     */
    public function me(): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'No autorizado.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Cargar roles y permisos de forma eficiente
        $user->loadMissing('roles.permissions');

        // Consolidar matriz de permisos asignados
        $permissions = [];
        if ($user->role === 'Super Admin' || $user->roles->contains('name', 'Super Admin')) {
            $permissions = ['*'];
        } else {
            foreach ($user->roles as $role) {
                foreach ($role->permissions as $permission) {
                    $permissions[] = $permission->name;
                }
            }
            $permissions = array_values(array_unique($permissions));
        }

        return response()->json([
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'tenant_id' => $user->tenant_id,
            'permissions' => $permissions
        ]);
    }

    private function getRefreshTtlMinutes(): int
    {
        return (int) (env('JWT_REFRESH_TTL', 10080));
    }
}
