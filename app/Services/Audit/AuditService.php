<?php
declare(strict_types=1);

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Registra una acción de auditoría en la base de datos.
     *
     * @param string $tenantId
     * @param string|null $userId
     * @param string $accion (ej: login, logout, failed_login, password_change, etc.)
     * @param string $nombreTabla
     * @param string $registroId
     * @param array|null $valoresAnteriores
     * @param array|null $valoresNuevos
     * @return void
     */
    public function log(
        string $tenantId,
        ?string $userId,
        string $accion,
        string $nombreTabla,
        string $registroId,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null
    ): void {
        try {
            DB::table('audit_logs')->insert([
                'id' => (string) Str::uuid7(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'accion' => substr($accion, 0, 20),
                'nombre_tabla' => substr($nombreTabla, 0, 100),
                'registro_id' => $registroId,
                'valores_anteriores' => $valoresAnteriores ? json_encode($valoresAnteriores) : null,
                'valores_nuevos' => $valoresNuevos ? json_encode($valoresNuevos) : null,
                'direccion_ip' => substr(Request::ip() ?? '127.0.0.1', 0, 45),
                'agente_usuario' => Request::userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Falla silenciosa o logging local para no bloquear la aplicación si la base de datos falla al auditar
            logger()->error('Fallo al escribir log de auditoría: ' . $e->getMessage());
        }
    }
}
