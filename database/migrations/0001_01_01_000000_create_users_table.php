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
        // 1. Crear tabla de planes (Global)
        Schema::create('plans', function (Blueprint $table) {
            $table->id(); // BIGINT Auto-increment
            $table->string('name', 100)->unique();
            $table->integer('max_branches')->default(1);
            $table->integer('max_users')->default(3);
            $table->decimal('price', 12, 2)->default(0.00);
            $table->jsonb('features')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        // 2. Crear tabla de inquilinos (Global)
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');
            $table->string('nombre', 150);
            $table->string('subdominio', 100)->unique();
            $table->string('dominio_personalizado', 150)->nullable()->unique();
            $table->string('status', 30)->default('trial');
            $table->timestampsTz();
        });

        // 3. Crear tabla de suscripciones (Global)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('stripe_subscription_id', 150)->unique();
            $table->string('status', 50);
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        // 4. Crear tabla de usuarios (Multi-Tenant RLS)
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('nombre', 150);
            $table->string('email', 150);
            $table->string('password', 255);
            $table->string('role', 50);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            // RLS Index
            $table->index('tenant_id');
            // Login Index
            $table->unique(['tenant_id', 'email']);
        });

        // 5. Tabla de tokens de restablecimiento (Default Laravel compatible)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 6. Tabla de sesiones de usuario (Default Laravel compatible)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plans');
    }
};
