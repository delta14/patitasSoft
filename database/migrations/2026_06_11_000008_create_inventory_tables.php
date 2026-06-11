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
        // 1. Crear tabla de almacenes (Multi-Tenant RLS)
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('branch_id');
            $table->string('name', 100);
            $table->timestampTz('created_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index('tenant_id');
        });

        // 2. Crear tabla de proveedores (Multi-Tenant RLS)
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->softDeletesTz();

            $table->index('tenant_id');
        });

        // 3. Crear tabla de categorías de productos (Multi-Tenant RLS)
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'name']);
        });

        // 4. Crear tabla de productos (Multi-Tenant RLS)
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('category_id');
            $table->string('name', 150);
            $table->string('sku', 50);
            $table->string('barcode', 100)->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0.00);
            $table->decimal('sale_price', 12, 2)->default(0.00);
            $table->boolean('is_medicine')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->softDeletesTz();

            $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('restrict');
            $table->index('tenant_id');
            // SKU único por Tenant
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'barcode']);
        });

        // 5. Crear tabla de lotes de productos (Multi-Tenant RLS)
        Schema::create('product_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            $table->string('batch_number', 50);
            $table->integer('quantity')->default(0);
            $table->date('expiration_date')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'warehouse_id', 'product_id', 'batch_number'], 'uq_batches_keys');
        });

        // 6. Crear tabla de movimientos de inventario (Trazabilidad)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('batch_id');
            $table->uuid('user_id');
            $table->string('type', 30); // entrada_compra, salida_venta, merma, transferencia
            $table->integer('quantity'); // positivos/negativos
            $table->string('reason', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouses');
    }
};
