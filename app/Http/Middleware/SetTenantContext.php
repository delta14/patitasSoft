<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SetTenantContext
{
    /**
     * Maneja la petición entrante resolviendo el contexto del Tenant y configurando PostgreSQL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        // 1. Intentar resolver el tenant_id a partir del token JWT si está presente en la cabecera Authorization
        $authorizationHeader = $request->header('Authorization');
        if ($authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = substr($authorizationHeader, 7);
            try {
                $secret = config('app.jwt_secret') ?: env('JWT_SECRET');
                if ($secret) {
                    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
                    if (isset($decoded->tenant_id)) {
                        $tenantId = $decoded->tenant_id;
                    }
                }
            } catch (\Exception $e) {
                // Token inválido o expirado. Dejar que el guard o autenticación lo maneje después.
            }
        }

        // 2. Si no se resolvió por JWT, intentar por cabecera personalizada (útil para APIs de testing o móviles)
        if (!$tenantId) {
            $tenantId = $request->header('X-Tenant-ID');
        }

        // 3. Si no, resolver por el subdominio del Host de la petición
        if (!$tenantId) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $subdomain = $parts[0];
                
                // Buscar subdominio en caché para alto rendimiento
                $tenant = cache()->remember("tenant_subdomain:{$subdomain}", 3600, function () use ($subdomain) {
                    return Tenant::where('subdominio', $subdomain)->first();
                });

                if ($tenant) {
                    $tenantId = $tenant->id;
                }
            }
        }

        // 4. Si tenemos un tenantId, cargar y validar el Tenant
        if ($tenantId) {
            $tenant = cache()->remember("tenant_meta:{$tenantId}", 3600, function () use ($tenantId) {
                return Tenant::find($tenantId);
            });

            if (!$tenant) {
                abort(404, 'La clínica veterinaria especificada no existe.');
            }

            if ($tenant->status !== 'trial' && $tenant->status !== 'active') {
                abort(403, 'La suscripción de la clínica veterinaria se encuentra suspendida o inactiva.');
            }

            // Inyectar en sesión y en los atributos de la petición
            session(['tenant_id' => $tenant->id]);
            $request->attributes->set('tenant', $tenant);

            // Inyectar variables de contexto en la sesión de la conexión PostgreSQL (para RLS)
            DB::statement("SELECT set_config('app.current_tenant_id', ?, false)", [$tenant->id]);
        } else {
            // Para rutas globales no se requiere tenant_id, pero limpiamos el contexto
            DB::statement("SELECT set_config('app.current_tenant_id', '', false)");
        }

        // 5. Si el usuario está autenticado, inyectar también su ID para la bitácora de auditoría
        if (Auth::check()) {
            DB::statement("SELECT set_config('app.current_user_id', ?, false)", [(string) Auth::id()]);
        }

        return $next($request);
    }
}
