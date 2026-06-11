<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Crear tabla de sucursales (Multi-Tenant RLS)
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

        // 2. Crear tabla intermedia branch_users
        Schema::create('branch_users', function (Blueprint $table) {
            $table->uuid('branch_id');
            $table->uuid('user_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary(['branch_id', 'user_id']);
        });

        // 3. Crear tabla de dueños de mascota (Multi-Tenant RLS)
        Schema::create('owners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('dni_rfc', 255)->nullable(); // Guardado encriptado
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'last_name', 'first_name']); // Búsqueda frecuente
        });

        // 4. Crear tabla de contactos de dueños
        Schema::create('owner_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->string('type', 30); // telefono, email, etc.
            $table->string('value', 150);
            $table->boolean('is_primary')->default(false);

            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_contacts');
        Schema::dropIfExists('owners');
        Schema::dropIfExists('branch_users');
        Schema::dropIfExists('branches');
    }
};
