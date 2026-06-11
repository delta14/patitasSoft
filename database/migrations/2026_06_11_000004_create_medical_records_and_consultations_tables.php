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
        // 1. Crear tabla de expedientes (Multi-Tenant RLS, Inmutable)
        Schema::create('medical_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('pet_id')->unique();
            $table->text('critical_notes')->nullable();
            $table->timestampsTz();

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // 2. Crear tabla de consultas (Multi-Tenant RLS, Inmutable)
        Schema::create('consultations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('medical_record_id');
            $table->uuid('branch_id');
            $table->uuid('veterinarian_id');
            $table->decimal('weight_kg', 6, 3);
            $table->decimal('temperature_c', 4, 2);
            $table->integer('heart_rate_bpm');
            $table->integer('respiratory_rate_rpm');
            $table->text('symptoms');
            $table->text('medical_notes')->nullable();
            $table->timestampsTz();

            $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('veterinarian_id')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'medical_record_id']); // Búsqueda frecuente
        });

        // 3. Crear tabla de diagnósticos (Catálogo Híbrido)
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null = Global
            $table->string('code', 20)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 4. Crear tabla intermedia de diagnósticos de consulta
        Schema::create('consultation_diagnoses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('consultation_id');
            $table->uuid('diagnosis_id');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('diagnosis_id')->references('id')->on('diagnoses')->onDelete('restrict');
        });

        // 5. Crear tabla de tratamientos (Catálogo Híbrido)
        Schema::create('treatments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null = Global
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 6. Crear tabla intermedia de tratamientos de consulta
        Schema::create('consultation_treatments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('consultation_id');
            $table->uuid('treatment_id');
            $table->text('dosage_notes');
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('treatment_id')->references('id')->on('treatments')->onDelete('restrict');
        });

        // 7. Crear tabla de recetas
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('consultation_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });

        // 8. Crear tabla de detalles de recetas
        Schema::create('prescription_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prescription_id');
            $table->string('medicine_name', 150);
            $table->string('dosage', 255);
            $table->string('frequency', 100);
            $table->integer('duration_days');

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('cascade');
        });

        // 9. Crear tabla de archivos adjuntos clínicos (Rayos X, PDFs, etc.)
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('consultation_id');
            $table->string('file_name', 150);
            $table->string('file_url', 255);
            $table->string('mime_type', 50);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });

        // 10. Crear tabla de métricas/signos vitales históricos (Multi-Tenant RLS)
        Schema::create('pet_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('pet_id');
            $table->uuid('measured_by')->nullable();
            $table->uuid('consultation_id')->nullable();
            
            $table->decimal('weight_kg', 6, 3)->nullable();
            $table->decimal('temperature_c', 4, 2)->nullable();
            $table->integer('heart_rate_bpm')->nullable();
            $table->integer('respiratory_rate_rpm')->nullable();
            $table->integer('systolic_bp')->nullable();
            $table->integer('diastolic_bp')->nullable();
            
            $table->timestampTz('measured_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('cascade');
            $table->foreign('measured_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            
            $table->index(['tenant_id', 'pet_id', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_metrics');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('prescription_details');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('consultation_treatments');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('consultation_diagnoses');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('medical_records');
    }
};
