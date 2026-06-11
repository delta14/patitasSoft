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
        // 1. Crear tabla de cajas físicas (Multi-Tenant RLS)
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('branch_id');
            $table->uuid('opened_by');
            $table->uuid('closed_by')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->timestampTz('opened_at')->useCurrent();
            $table->timestampTz('closed_at')->nullable();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('opened_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
        });

        // 2. Crear tabla de ventas (Multi-Tenant RLS, Inmutable)
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('branch_id');
            $table->uuid('cash_register_id');
            $table->uuid('owner_id')->nullable();
            $table->uuid('pet_id')->nullable();
            $table->decimal('total', 12, 2)->default(0.00);
            $table->decimal('tax', 12, 2)->default(0.00);
            $table->string('status', 30)->default('paid'); // paid, refunded, cancelled
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('cash_register_id')->references('id')->on('cash_registers')->onDelete('restrict');
            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('restrict');
            $table->foreign('pet_id')->references('id')->on('pets')->onDelete('restrict');
            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']); // Búsqueda frecuente de reportes
        });

        // 3. Crear tabla de detalles de venta (Multi-Tenant RLS, Inmutable)
        Schema::create('sale_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('sale_id');
            $table->uuid('product_id')->nullable();
            $table->uuid('service_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
            $table->index('tenant_id');
        });

        // Aplicar restricción CHECK para asegurar que sea producto o servicio pero no ambos
        DB::statement("
            ALTER TABLE sale_details 
            ADD CONSTRAINT ck_detail_item 
            CHECK ((product_id IS NOT NULL AND service_id IS NULL) OR (product_id IS NULL AND service_id IS NOT NULL));
        ");

        // 4. Crear tabla de métodos de pago (Global)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        // 5. Crear tabla de pagos
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('sale_id');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            $table->decimal('amount', 12, 2);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('sale_details');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cash_registers');
    }
};
