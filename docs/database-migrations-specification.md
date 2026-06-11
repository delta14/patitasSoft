# Especificación de Migraciones y Base de Datos por Sprints (1 al 11)
## Patitas Soft - Guía Oficial de Ingeniería de Software (Laravel 12 & PostgreSQL)

---

### Control de Documento
* **Proyecto:** Patitas Soft
* **Versión:** 2.0.0
* **Fecha:** 11 de Junio de 2026
* **Autores (Consorcio de Arquitectos):**
  * Laravel 12 Enterprise Architect
  * PostgreSQL Database Architect
  * SaaS Multi-Tenant Architect
  * Security Architect
  * DevSecOps Engineer

---

## 1. Introducción y Arquitectura de Ejecución

Este documento sirve como la guía técnica oficial y contrato físico del esquema de base de datos de **Patitas Soft**. Define de manera exhaustiva el diseño, las relaciones, las consideraciones de aislamiento Multi-Tenant, las medidas de mitigación de OWASP, los riesgos técnicos y el código completo de las migraciones organizadas en 11 Sprints secuenciales.

### 1.1. Diagrama de Flujo y Orden de Ejecución Recomendado
La base de datos tiene fuertes dependencias relacionales. El orden secuencial de ejecución de las migraciones es el siguiente:

```text
[Sprint 1: Plataforma] ──► [Sprint 2: Sucursales, Dueños y Mascotas]
                                     │
                                     ▼
[Sprint 5: Agenda] ◄──────── [Sprint 6: Servicios]
      │
      ▼
[Sprint 3: Expediente Clínico] ──► [Sprint 4: Vacunación e Hospitalizaciones]
                                               │
                                               ▼
[Sprint 11: Notificaciones] ◄────── [Sprint 7: Inventario] ──► [Sprint 8: Medicamentos]
                                               │
                                               ▼
                                    [Sprint 9: Punto de Venta]
                                               │
                                               ▼
                                    [Sprint 10: Facturación]
```

---

## 2. Especificación Detallada de Sprints (1 al 11)

---

### SPRINT 1: Plataforma (SaaS Core)

#### 1. Orden de Ejecución
1. `0001_01_01_000000_create_users_table.php` (Core global e inquilinos)
2. `2026_06_11_000001_create_roles_permissions_and_audit_tables.php` (Autorización y Auditoría)

#### 2. Código Completo de Migración

##### Archivo: `0001_01_01_000000_create_users_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->integer('max_branches')->default(1);
            $table->integer('max_users')->default(3);
            $table->decimal('price', 12, 2)->default(0.00);
            $table->jsonb('features')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');
            $table->string('nombre', 150);
            $table->string('subdominio', 100)->unique();
            $table->string('dominio_personalizado', 150)->nullable()->unique();
            $table->string('status', 30)->default('trial');
            $table->timestampsTz();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('stripe_subscription_id', 150)->unique();
            $table->string('status', 50);
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('nombre', 150);
            $table->string('email', 150);
            $table->string('password', 255);
            $table->string('role', 50);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plans');
    }
};
```

##### Archivo: `2026_06_11_000001_create_roles_permissions_and_audit_tables.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('guard_name', 50)->default('web');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('guard_name', 50)->default('web');
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->uuid('model_id');
            $table->string('model_type', 150);
            $table->uuid('role_id');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['model_id', 'model_type', 'role_id']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->foreignId('permission_id');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

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

        DB::statement("CREATE TABLE audit_logs_default PARTITION OF audit_logs DEFAULT;");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS audit_logs CASCADE;");
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
```

#### 3. Explicación de Diseño, Relaciones e Índices
* **UUIDv7 en Llaves Primarias:** Asignados en todos los recursos del inquilino (tenants, subscriptions, users, roles) para evitar colisiones en shards futuros y mitigar la enumeración de recursos.
* **Índice Compuesto Único (`tenant_id`, `email`):** Permite que un mismo correo de usuario exista de forma legítima en diferentes clínicas veterinarias del SaaS sin colisiones.
* **Particionamiento por Rango de Tiempos:** La tabla `audit_logs` se particiona físicamente por meses basados en `created_at`. Se provee una partición `DEFAULT` para atrapar inserciones que no tengan definida una subtabla de rango mensual activa, evitando denegaciones de servicio.

#### 4. Consideraciones Multi-Tenant y OWASP
* **Aislamiento a nivel de DB:** Todas las tablas transaccionales tienen la columna `tenant_id`. Se inyecta la Row-Level Security en PostgreSQL RLS.
* **OWASP A01 (Broken Access Control):** Al no utilizar IDs incrementales de forma visible en las APIs de usuarios o inquilinos, se previene que un atacante intente iterar identificadores.

#### 5. Riesgos y Recomendaciones de Producción
* *Riesgo:* Crecimiento exponencial del log de auditoría.
* *Recomendación:* Programar una tarea Cron (despachada los domingos en la noche) para separar particiones antiguas de `audit_logs` y archivarlas en almacenamiento en frío S3 Glacier.

---

### SPRINT 2: Dueños y Mascotas

#### 1. Orden de Ejecución
1. `2026_06_11_000002_create_branches_and_owners_tables.php`
2. `2026_06_11_000003_create_pets_and_catalogs_tables.php`

#### 2. Código Completo de Migración

##### Archivo: `2026_06_11_000002_create_branches_and_owners_tables.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->text('address');
            $table->string('phone', 30);
            $table->timestampTz('created_at')->useCurrent();
            $table->softDeletesTz();

            $table->index('tenant_id');
        });

        Schema::create('branch_users', function (Blueprint $table) {
            $table->uuid('branch_id');
            $table->uuid('user_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary(['branch_id', 'user_id']);
        });

        Schema::create('owners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('dni_rfc', 255)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'last_name', 'first_name']);
        });

        Schema::create('owner_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->string('type', 30);
            $table->string('value', 150);
            $table->boolean('is_primary')->default(false);

            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_contacts');
        Schema::dropIfExists('owners');
        Schema::dropIfExists('branch_users');
        Schema::dropIfExists('branches');
    }
};
```

##### Archivo: `2026_06_11_000003_create_pets_and_catalogs_tables.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_species', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
        });

        Schema::create('pet_breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained('pet_species')->onDelete('cascade');
            $table->string('name', 100);
            
            $table->unique(['species_id', 'name']);
        });

        Schema::create('pet_colors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('owner_id');
            $table->foreignId('species_id')->constrained('pet_species')->onDelete('restrict');
            $table->foreignId('breed_id')->constrained('pet_breeds')->onDelete('restrict');
            $table->foreignId('color_id')->constrained('pet_colors')->onDelete('restrict');
            $table->string('name', 100);
            $table->date('birth_date');
            $table->char('gender', 1);
            $table->string('microchip', 50)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'owner_id']);
        });

        Schema::create('pet_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pet_id');
            $table->string('photo_url', 255);
            $table->boolean('is_profile')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_photos');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('pet_colors');
        Schema::dropIfExists('pet_breeds');
        Schema::dropIfExists('pet_species');
    }
};
```

#### 3. Explicación de Diseño, Relaciones e Índices
* **Índice de Búsqueda Compuesto (`tenant_id`, `last_name`, `first_name`):** Permite autocompletado en milisegundos cuando las recepcionistas buscan dueños por sus apellidos.
* **Índices Parciales con Soft Deletes:** Los índices de `owners` y `pets` se aplican solo a filas activas (`WHERE deleted_at IS NULL`), ahorrando valioso espacio de índices en disco.

#### 4. Consideraciones Multi-Tenant y OWASP
* **Aislamiento de Pacientes:** RLS garantiza que ninguna clínica pueda listar las mascotas de otra.
* **OWASP A02 (Cryptographic Failures):** La columna `dni_rfc` almacenará la cadena cifrada con AES-256-CBC de Laravel en reposo, protegiendo información fiscal sensible.

#### 5. Riesgos y Recomendaciones de Producción
* *Riesgo:* Bloqueos en transacciones si eliminamos un dueño que posee múltiples mascotas vinculadas históricamente.
* *Recomendación:* Se restringe la eliminación física de dueños mediante `onDelete('restrict')` en la foránea de la tabla `pets`, obligando a usar soft deletes.

---

### SPRINT 3: Expediente Clínico

#### 1. Orden de Ejecución
1. `2026_06_11_000004_create_medical_records_and_consultations_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0004_create_medical_records...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000004_create_medical_records_and_consultations_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Medical Record 1:1 con Pet:** Llave foránea `pet_id` marcada como `UNIQUE` para asegurar que cada mascota posea un único expediente.
* **Inmutabilidad Clínica:** Ninguna tabla de este sprint utiliza `softDeletes` para garantizar la inalterabilidad histórica de las notas médicas.
* **Índice de Búsqueda:** `idx_consultations_tenant_record` indexa `(tenant_id, medical_record_id)` acelerando la carga del historial médico del paciente en consulta.

#### 4. Consideraciones Multi-Tenant y OWASP
* **Seguridad de Archivos Clínicos:** La tabla `attachments` guarda URLs que apuntan a buckets no públicos de S3, los cuales requieren que Laravel genere enlaces firmados temporales (validez máxima de 5 minutos).

#### 5. Riesgos y Recomendaciones de Producción
* *Riesgo:* Rendimiento degradado al crecer la tabla `consultations` a millones de registros en el SaaS.
* *Recomendación:* Crear índices parciales sobre diagnósticos críticos o particionar las tablas por año de consulta.

---

### SPRINT 4: Vacunación y Servicios Médicos

#### 1. Orden de Ejecución
1. `2026_06_11_000005_create_vaccines_dewormings_surgeries_and_hospitalization_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0005_create_vaccines...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000005_create_vaccines_dewormings_surgeries_and_hospitalization_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Catálogos Híbridos:** Las tablas `vaccines`, `dewormings` y `surgeries` tienen una columna `tenant_id` que admite valores `NULL`. Si es `NULL`, la vacuna o cirugía es del catálogo global del SaaS; si tiene valor, es una personalización exclusiva de ese inquilino.
* **RLS en Híbridos:** La política de RLS para estas tablas se define como:
  `USING (tenant_id IS NULL OR tenant_id = current_setting('app.current_tenant_id')::uuid)`.

#### 4. Consideraciones Multi-Tenant y OWASP
* Evita que clínicas de la competencia vean las cirugías personalizadas de alta especialidad que un hospital veterinario configure para sí mismo.

#### 5. Riesgos y Recomendaciones de Producción
* *Recomendación:* Se inyectan restricciones `check` para las hospitalizaciones, forzando a que la fecha de ingreso (`admitted_at`) no pueda ser posterior a la fecha de alta (`discharged_at`).

---

### SPRINT 5: Agenda

#### 1. Orden de Ejecución
1. `2026_06_11_000006_create_appointments_and_schedules_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0006_create_appointments...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000006_create_appointments_and_schedules_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Prevención de Solapamiento:** Se indexa `(tenant_id, start_time, end_time)`. Esto permite que las consultas de validación de horarios disponibles se ejecuten de forma instantánea.
* **Integridad de Slots:** La tabla `schedules` añade una llave única compuesta `uq_schedules_slots` para impedir que un veterinario declare dos rangos de trabajo solapados el mismo día.

#### 4. Consideraciones Multi-Tenant y OWASP
* Los dueños de mascotas solo pueden agendar y ver citas asociadas a su propio `pet_id`, validado mediante políticas de Laravel.

#### 5. Riesgos y Recomendaciones de Producción
* *Riesgo:* Problema de concurrencia cuando dos clientes intentan reservar la misma cita médica de forma simultánea.
* *Recomendación:* Implementar un bloqueo optimista en Laravel usando Redis Locks con una duración máxima de 2 minutos mientras el usuario completa el flujo de reserva.

---

### SPRINT 6: Servicios

#### 1. Orden de Ejecución
1. `2026_06_11_000007_create_services_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0007_create_services...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000007_create_services_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* Separación modular limpia entre categorías y servicios.
* Mapeo de precios con precisión NUMERIC para prevenir pérdidas decimales por redondeo flotante.

---

### SPRINT 7: Inventario

#### 1. Orden de Ejecución
1. `2026_06_11_000008_create_inventory_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0008_create_inventory...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000008_create_inventory_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Índice de SKU Único:** La tabla `products` restringe que no existan SKUs idénticos dentro de la misma clínica a través de la restricción compuesta:
  `UNIQUE(tenant_id, sku) WHERE deleted_at IS NULL`.
* **Control Loteado:** La tabla `product_batches` vincula la cantidad de stock a un almacén y lote específico.
* **Movimientos Históricos:** La tabla `inventory_movements` es inmutable y almacena el saldo del stock (positivo/negativo) firmado por el usuario del sistema que autorizó el ajuste.

#### 4. Consideraciones Multi-Tenant y OWASP
* Los almacenes y stocks están segregados por sucursal (`branch_id`), la cual hereda el aislamiento de `tenant_id`.

#### 5. Riesgos y Recomendaciones de Producción
* *Recomendación:* Se implementa la restricción `CHECK (quantity >= 0)` en la tabla de lotes para impedir que fallos en el software resulten en cantidades de inventario negativas.

---

### SPRINT 8: Medicamentos

#### 1. Orden de Ejecución
1. `2026_06_11_000009_create_medicines_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0009_create_medicines...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000009_create_medicines_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Estructura Relacional 1:1:** Las tablas `medicines` y `medicine_batches` heredan el ID del producto y del lote de productos respectivamente como clave primaria y clave foránea simultáneamente, evitando redundancias en la tabla de productos general y optimizando el almacenamiento.

---

### SPRINT 9: Punto de Venta (POS)

#### 1. Orden de Ejecución
1. `2026_06_11_000010_create_point_of_sale_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0010_create_point_of_sale...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000010_create_point_of_sale_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **CHECK Constraint Exclusivo:** En la tabla `sale_details`, forzamos a que el item facturado sea un producto o un servicio, nunca ambos ni ninguno. Esto garantiza la integridad referencial en caja.
* **Índices Financieros:** `idx_sales_tenant_date` indexa `(tenant_id, created_at DESC)` acelerando la carga del corte de caja diario.

---

### SPRINT 10: Facturación

#### 1. Orden de Ejecución
1. `2026_06_11_000011_create_billing_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0011_create_billing...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000011_create_billing_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Inmutabilidad Absoluta:** Las tablas `invoices` y `invoice_details` no permiten ningún tipo de borrado ni alteración física.
* **Relación 1:1 Única con Ventas:** La columna `sale_id` tiene una restricción `UNIQUE` para asegurar que una venta del Punto de Venta solo pueda generar una única factura fiscal timbrada.

---

### SPRINT 11: Notificaciones

#### 1. Orden de Ejecución
1. `2026_06_11_000012_create_notifications_tables.php`

#### 2. Código Completo de Migración
*(Ver archivo completo en el directorio del workspace: [0012_create_notifications...](file:///c:/workspace/patitasSoft/database/migrations/2026_06_11_000012_create_notifications_tables.php))*

#### 3. Explicación de Diseño, Relaciones e Índices
* **Templates Híbridos:** Las plantillas de aviso médico (`notification_templates`) se gestionan mediante el formato híbrido (Null en `tenant_id` para globales) y llave única compuesta para evitar duplicados del mismo tipo de evento por inquilino.
