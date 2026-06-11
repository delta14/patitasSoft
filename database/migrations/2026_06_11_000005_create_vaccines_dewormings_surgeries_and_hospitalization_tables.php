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
        // 1. Crear tabla de vacunas (Catálogo Híbrido)
        Schema::create('vaccines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null = Global
            $table->string('name', 150);
            $table->foreignId('target_species_id')->constrained('pet_species')->onDelete('cascade');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 2. Crear tabla de vacunas aplicadas a mascotas
        Schema::create('pet_vaccines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('pet_id');
            $table->uuid('vaccine_id');
            $table->uuid('administered_by');
            $table->string('batch_number', 50)->nullable();
            $table->date('administered_at');
            $table->date('next_due_at');

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('vaccine_id')->references('id')->on('vaccines')->onDelete('restrict');
            $table->foreign('administered_by')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'pet_id']);
        });

        // 3. Crear tabla de desparasitantes (Catálogo Híbrido)
        Schema::create('dewormings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null = Global
            $table->string('name', 150);
            $table->string('type', 30); // interno, externo

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 4. Crear tabla de desparasitaciones de mascotas
        Schema::create('pet_dewormings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('pet_id');
            $table->uuid('deworming_id');
            $table->uuid('administered_by');
            $table->decimal('weight_kg', 6, 3);
            $table->date('administered_at');
            $table->date('next_due_at');

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('deworming_id')->references('id')->on('dewormings')->onDelete('restrict');
            $table->foreign('administered_by')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'pet_id']);
        });

        // 5. Crear tabla de cirugías (Catálogo Híbrido)
        Schema::create('surgeries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null = Global
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 6. Crear tabla de cirugías de mascotas
        Schema::create('pet_surgeries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('pet_id');
            $table->uuid('surgery_id');
            $table->uuid('surgeon_id');
            $table->timestampTz('performed_at');
            $table->text('post_operative_notes')->nullable();

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('surgery_id')->references('id')->on('surgeries')->onDelete('restrict');
            $table->foreign('surgeon_id')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'pet_id']);
        });

        // 7. Crear tabla de hospitalizaciones
        Schema::create('hospitalizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('pet_id');
            $table->uuid('branch_id');
            $table->text('reason');
            $table->string('cage_number', 30)->nullable();
            $table->timestampTz('admitted_at');
            $table->timestampTz('discharged_at')->nullable();
            $table->text('discharge_notes')->nullable();

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospitalizations');
        Schema::dropIfExists('pet_surgeries');
        Schema::dropIfExists('surgeries');
        Schema::dropIfExists('pet_dewormings');
        Schema::dropIfExists('dewormings');
        Schema::dropIfExists('pet_vaccines');
        Schema::dropIfExists('vaccines');
    }
};
