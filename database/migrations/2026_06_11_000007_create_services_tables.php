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
        // 1. Crear tabla de categorías de servicios (Multi-Tenant RLS)
        Schema::create('service_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->timestampTz('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'name']);
        });

        // 2. Crear tabla de servicios (Multi-Tenant RLS)
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('category_id');
            $table->string('name', 150);
            $table->decimal('price', 12, 2)->default(0.00);
            $table->timestampTz('created_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('restrict');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};
