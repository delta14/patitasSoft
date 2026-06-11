<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Planes Comerciales SaaS
        DB::table('plans')->insertOrIgnore([
            ['id' => 1, 'name' => 'Plan Trial', 'max_branches' => 1, 'max_users' => 2, 'price' => 0.00, 'features' => json_encode(['medical_records' => true, 'agenda' => true])],
            ['id' => 2, 'name' => 'Plan Standard', 'max_branches' => 3, 'max_users' => 5, 'price' => 49.00, 'features' => json_encode(['medical_records' => true, 'agenda' => true, 'inventory' => true, 'pos' => true])],
            ['id' => 3, 'name' => 'Plan Enterprise', 'max_branches' => 10, 'max_users' => 20, 'price' => 99.00, 'features' => json_encode(['medical_records' => true, 'agenda' => true, 'inventory' => true, 'pos' => true, 'billing' => true])],
        ]);

        // 2. Seed Especies de Mascotas
        DB::table('pet_species')->insertOrIgnore([
            ['id' => 1, 'name' => 'Canino'],
            ['id' => 2, 'name' => 'Felino'],
            ['id' => 3, 'name' => 'Ave'],
            ['id' => 4, 'name' => 'Reptil'],
            ['id' => 5, 'name' => 'Roedor'],
        ]);

        // 3. Seed Razas de Mascotas
        DB::table('pet_breeds')->insertOrIgnore([
            // Caninos
            ['id' => 1, 'species_id' => 1, 'name' => 'Pastor Alemán'],
            ['id' => 2, 'species_id' => 1, 'name' => 'Golden Retriever'],
            ['id' => 3, 'species_id' => 1, 'name' => 'Chihuahua'],
            ['id' => 4, 'species_id' => 1, 'name' => 'Pug'],
            ['id' => 5, 'species_id' => 1, 'name' => 'Bulldog Francés'],
            // Felinos
            ['id' => 6, 'species_id' => 2, 'name' => 'Persa'],
            ['id' => 7, 'species_id' => 2, 'name' => 'Maine Coon'],
            ['id' => 8, 'species_id' => 2, 'name' => 'Siamés'],
            ['id' => 9, 'species_id' => 2, 'name' => 'Mestizo'],
        ]);

        // 4. Seed Colores de Mascotas
        DB::table('pet_colors')->insertOrIgnore([
            ['id' => 1, 'name' => 'Negro'],
            ['id' => 2, 'name' => 'Blanco'],
            ['id' => 3, 'name' => 'Marrón'],
            ['id' => 4, 'name' => 'Gris'],
            ['id' => 5, 'name' => 'Dorado'],
            ['id' => 6, 'name' => 'Atigrado'],
        ]);

        // 5. Seed Estados de Citas
        DB::table('appointment_statuses')->insertOrIgnore([
            ['id' => 1, 'name' => 'Pendiente'],
            ['id' => 2, 'name' => 'Confirmada'],
            ['id' => 3, 'name' => 'Cancelada'],
            ['id' => 4, 'name' => 'Completada'],
            ['id' => 5, 'name' => 'Ausente'],
        ]);

        // 6. Seed Métodos de Pago
        DB::table('payment_methods')->insertOrIgnore([
            ['id' => 1, 'name' => 'Efectivo'],
            ['id' => 2, 'name' => 'Tarjeta de Débito/Crédito'],
            ['id' => 3, 'name' => 'Transferencia Bancaria'],
        ]);

        // 7. Seed Permisos Base de la Plataforma
        DB::table('permissions')->insertOrIgnore([
            ['id' => 1, 'name' => 'view_tenants', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'manage_tenants', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'manage_users', 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'view_medical_records', 'guard_name' => 'web'],
            ['id' => 5, 'name' => 'create_medical_records', 'guard_name' => 'web'],
            ['id' => 6, 'name' => 'edit_medical_records', 'guard_name' => 'web'],
            ['id' => 7, 'name' => 'manage_appointments', 'guard_name' => 'web'],
            ['id' => 8, 'name' => 'manage_inventory', 'guard_name' => 'web'],
            ['id' => 9, 'name' => 'process_sales', 'guard_name' => 'web'],
            ['id' => 10, 'name' => 'issue_invoices', 'guard_name' => 'web'],
        ]);
    }
}
