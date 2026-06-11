<?php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Tenant\Models\Tenant;
use App\Models\User;
use App\Domains\User\Models\Role;
use App\Domains\User\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $userVet;
    private User $userAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Crear Tenant
        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid7(),
            'plan_id' => 1,
            'nombre' => 'Clinica Prueba',
            'subdominio' => 'clinica-prueba',
            'status' => 'active',
        ]);

        // Establecer contexto para poder crear roles hijos de este tenant
        session(['tenant_id' => $this->tenant->id]);

        // Crear un rol y asignarle un permiso
        $roleVet = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Veterinario',
            'guard_name' => 'web',
        ]);
        
        $permissionViewRecords = Permission::where('name', 'view_medical_records')->first();
        $roleVet->permissions()->attach($permissionViewRecords->id);

        // Crear un usuario Veterinario
        $this->userVet = User::create([
            'id' => (string) Str::uuid7(),
            'tenant_id' => $this->tenant->id,
            'nombre' => 'Dra. Ana',
            'email' => 'ana@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Veterinario',
            'is_active' => true,
        ]);
        $this->userVet->roles()->attach($roleVet->id, ['model_type' => User::class]);

        // Crear un usuario Super Admin
        $this->userAdmin = User::create([
            'id' => (string) Str::uuid7(),
            'tenant_id' => $this->tenant->id,
            'nombre' => 'Super Administrador',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Super Admin',
            'is_active' => true,
        ]);
    }

    /**
     * Test a Veterinario user can view medical records but cannot manage tenants.
     */
    public function test_rbac_gates_authorizes_correctly(): void
    {
        // El usuario veterinario tiene asignado el rol Veterinario, el cual tiene el permiso view_medical_records
        $this->assertTrue($this->userVet->hasPermission('view_medical_records'));
        $this->assertFalse($this->userVet->hasPermission('manage_tenants'));

        // Probar a través de Gate
        $this->assertTrue(Gate::forUser($this->userVet)->allows('view_medical_records'));
        $this->assertFalse(Gate::forUser($this->userVet)->allows('manage_tenants'));
    }

    /**
     * Test a Super Admin user is always authorized for any ability.
     */
    public function test_super_admin_bypasses_all_rbac_checks(): void
    {
        // El Super Admin debe pasar cualquier validación de Gate directamente
        $this->assertTrue(Gate::forUser($this->userAdmin)->allows('view_medical_records'));
        $this->assertTrue(Gate::forUser($this->userAdmin)->allows('manage_tenants'));
        $this->assertTrue(Gate::forUser($this->userAdmin)->allows('random_nonexistent_permission'));
    }
}
