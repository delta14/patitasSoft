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
        // 1. Crear tabla de facturas fiscales (Multi-Tenant RLS, Inmutable)
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('sale_id')->unique();
            $table->string('tax_identifier', 50); // RFC, DNI, etc.
            $table->string('invoice_number', 50); // Serie y folio
            $table->string('fiscal_uuid', 100)->unique(); // ID fiscal de hacienda
            $table->text('xml_payload'); // XML timbrado
            $table->string('status', 30)->default('issued'); // issued, cancelled
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
            $table->index('tenant_id');
        });

        // 2. Crear tabla de detalles de facturas
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('invoice_id');
            $table->string('description', 255);
            $table->integer('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('invoices');
    }
};
