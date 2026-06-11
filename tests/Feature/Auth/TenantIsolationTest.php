<?php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Tenant\Models\Tenant;
use App\Models\User;
use App\Services\Auth\JwtService;
use Illuminate\Support\Str;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->jwtService = $this->app->make(JwtService::class);

        // Crear Tenant A y su usuario
        $this->tenantA = Tenant::create([
            'id' => (string) Str::uuid7(),
            'plan_id' => 1,
            'nombre' => 'Clinica A',
            'subdominio' => 'clinica-a',
            'status' => 'active',
        ]);
        $this->userA = User::create([
            'id' => (string) Str::uuid7(),
            'tenant_id' => $this->tenantA->id,
            'nombre' => 'User A',
            'email' => 'user-a@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Veterinario',
            'is_active' => true,
        ]);

        // Crear Tenant B y su usuario
        $this->tenantB = Tenant::create([
            'id' => (string) Str::uuid7(),
            'plan_id' => 1,
            'nombre' => 'Clinica B',
            'subdominio' => 'clinica-b',
            'status' => 'active',
        ]);
        $this->userB = User::create([
            'id' => (string) Str::uuid7(),
            'tenant_id' => $this->tenantB->id,
            'nombre' => 'User B',
            'email' => 'user-b@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'Veterinario',
            'is_active' => true,
        ]);
    }

    /**
     * Test a Tenant A user gets filtered lists containing only Tenant A resources.
     */
    public function test_tenant_isolation_applies_automatically(): void
    {
        // Generar tokens para el usuario A
        $tokens = $this->jwtService->generateTokenPair($this->userA);

        // Realizar una petición a GET /api/v1/auth/me usando token del Usuario A
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokens['access_token'])
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonFragment(['tenant_id' => $this->tenantA->id]);
        $response->assertJsonFragment(['email' => $this->userA->email]);
        $response->assertJsonMissing(['tenant_id' => $this->tenantB->id]);
    }

    /**
     * Test accessing User B profile directly fails or is not found when authenticated as User A.
     */
    public function test_cannot_access_other_tenant_user_via_isolation(): void
    {
        // Generar token del Usuario A
        $tokens = $this->jwtService->generateTokenPair($this->userA);

        // Simulamos el contexto del Tenant A establecido por el middleware
        session(['tenant_id' => $this->tenantA->id]);
        
        // El TenantScope debe evitar que busquemos al Usuario B (del Tenant B)
        $userBResolved = User::find($this->userB->id);
        
        $this->assertNull($userBResolved);
    }
}
