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
        // 1. Crear tabla de medicamentos (Extensión 1:1 de productos)
        Schema::create('medicines', function (Blueprint $table) {
            $table->uuid('id')->primary(); // PK y FK al mismo tiempo
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('generic_name', 150); // Principio activo
            $table->string('concentration', 50); // Concentración (ej: 500mg)
            $table->string('dosage_form', 100); // Presentación (comprimido, jarabe, inyectable)

            $table->foreign('id')->references('id')->on('products')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // 2. Crear tabla de lotes de medicamentos (Extensión 1:1 de product_batches)
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); // PK y FK
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('registration_number', 100)->nullable(); // Registro sanitario (gubernamental)

            $table->foreign('id')->references('id')->on('product_batches')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
        Schema::dropIfExists('medicines');
    }
};
