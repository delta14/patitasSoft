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
        // 1. Crear tabla de especies (Global)
        Schema::create('pet_species', function (Blueprint $table) {
            $table->id(); // BIGINT
            $table->string('name', 100)->unique();
        });

        // 2. Crear tabla de razas (Global)
        Schema::create('pet_breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained('pet_species')->onDelete('cascade');
            $table->string('name', 100);
            
            $table->unique(['species_id', 'name']);
        });

        // 3. Crear tabla de colores (Global)
        Schema::create('pet_colors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        // 4. Crear tabla de mascotas (Multi-Tenant RLS)
        Schema::create('pets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('owner_id');
            $table->foreignId('species_id')->constrained('pet_species')->onDelete('restrict');
            $table->foreignId('breed_id')->constrained('pet_breeds')->onDelete('restrict');
            $table->foreignId('color_id')->constrained('pet_colors')->onDelete('restrict');
            $table->string('name', 100);
            $table->date('birth_date');
            $table->char('gender', 1)->checkIn(['M', 'F']);
            $table->string('microchip', 50)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'owner_id']); // Búsqueda frecuente
        });

        // 5. Crear tabla de fotos de mascotas
        Schema::create('pet_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pet_id');
            $table->string('photo_url', 255);
            $table->boolean('is_profile')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_photos');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('pet_colors');
        Schema::dropIfExists('pet_breeds');
        Schema::dropIfExists('pet_species');
    }
};
