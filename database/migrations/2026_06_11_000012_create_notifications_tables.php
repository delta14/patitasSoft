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
        // 1. Crear tabla de plantillas de notificaciones (Catálogo Híbrido)
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // Null = Global
            $table->string('trigger_event', 100); // vaccine_reminder, appointment_alert, etc.
            $table->jsonb('channels'); // Array de canales en formato jsonb: ['whatsapp', 'email']
            $table->text('message_body');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'trigger_event']);
        });

        // 2. Crear tabla de logs/historial de notificaciones despachadas
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('user_id')->nullable(); // Destinatario personal clínico (opcional)
            $table->uuid('owner_id')->nullable(); // Destinatario cliente dueño de mascota (opcional)
            $table->uuid('template_id');
            $table->string('channel', 30); // whatsapp, email, sms
            $table->string('recipient', 150); // Numero telefono o email
            $table->string('status', 30)->default('pending'); // pending, sent, failed
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('notification_templates')->onDelete('restrict');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
    }
};
