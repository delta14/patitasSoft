<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Crear tabla de roles (Multi-Tenant RLS)
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('guard_name', 50)->default('web');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'name']);
        });

        // 2. Crear tabla de permisos (Global)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); // BIGINT Auto-increment
            $table->string('name', 100)->unique();
            $table->string('guard_name', 50)->default('web');
            $table->timestampTz('created_at')->useCurrent();
        });

        // 3. Crear tabla intermedia model_has_roles
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->uuid('model_id');
            $table->string('model_type', 150);
            $table->uuid('role_id');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['model_id', 'model_type', 'role_id']);
        });

        // 4. Crear tabla intermedia role_permissions (user_roles / role_has_permissions)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->foreignId('permission_id');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // 5. Crear tabla de auditoría (Particionada por RANGE en created_at)
        DB::statement("
            CREATE TABLE audit_logs (
                id UUID NOT NULL,
                tenant_id UUID NOT NULL,
                user_id UUID NULL,
                accion VARCHAR(20) NOT NULL,
                nombre_tabla VARCHAR(100) NOT NULL,
                registro_id UUID NOT NULL,
                valores_anteriores JSONB NULL,
                valores_nuevos JSONB NULL,
                direccion_ip VARCHAR(45) NULL,
                agente_usuario TEXT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at);
        ");

        // Crear partición por defecto para mitigar desbordamiento
        DB::statement("CREATE TABLE audit_logs_default PARTITION OF audit_logs DEFAULT;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS audit_logs CASCADE;");
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
