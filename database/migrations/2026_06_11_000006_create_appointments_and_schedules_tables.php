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
        // 1. Crear tabla de estados de citas (Global)
        Schema::create('appointment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        // 2. Crear tabla de citas (Multi-Tenant RLS)
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('branch_id');
            $table->uuid('pet_id');
            $table->uuid('veterinarian_id');
            $table->foreignId('status_id')->constrained('appointment_statuses')->onDelete('restrict');
            $table->timestampTz('start_time');
            $table->timestampTz('end_time');
            $table->string('reason', 255);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('veterinarian_id')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
            // Índice de rango para búsquedas rápidas y evitar solapamiento de horarios
            $table->index(['tenant_id', 'start_time', 'end_time']);
        });

        // 3. Crear tabla de horarios de disponibilidad de veterinarios (Multi-Tenant RLS)
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('user_id'); // Veterinario
            $table->integer('day_of_week')->checkBetween([0, 6]); // 0 = Domingo
            $table->time('start_time');
            $table->time('end_time');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'user_id', 'day_of_week', 'start_time', 'end_time'], 'uq_schedules_slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('appointment_statuses');
    }
};
