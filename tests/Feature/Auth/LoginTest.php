<?php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed base database catalogs
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Crear un tenant de prueba
        $this->tenant = Tenant::create([
            'id' => (string) Str::uuid7(),
            'plan_id' => 1,
            'nombre' => 'Veterinaria de Prueba',
            'subdominio' => 'test-vet',
            'status' => 'active',
        ]);

        // Crear un usuario de prueba asociado al tenant
        $this->user = User::create([
            'id' => (string) Str::uuid7(),
            'tenant_id' => $this->tenant->id,
            'nombre' => 'Juan Vet',
            'email' => 'juan@example.com',
            'password' => Hash::make('SecretPassword123!'),
            'role' => 'Veterinario',
            'is_active' => true,
        ]);
        
        RateLimiter::clear('login:juan@example.com|127.0.0.1');
    }

    /**
     * Test login aborts if no tenant context is provided.
     */
    public function test_login_requires_tenant_context(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'SecretPassword123!',
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Test successful login with X-Tenant-ID header.
     */
    public function test_successful_login_with_tenant_header(): void
    {
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'juan@example.com',
                'password' => 'SecretPassword123!',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
        $response->assertCookie('refresh_token');
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'juan@example.com',
                'password' => 'WrongPassword',
            ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Credenciales inválidas.']);
    }

    /**
     * Test login throttling (Rate Limiting).
     */
    public function test_login_throttling(): void
    {
        // Enviar 5 peticiones fallidadas
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
                ->postJson('/api/v1/auth/login', [
                    'email' => 'juan@example.com',
                    'password' => 'WrongPassword',
                ]);
            $response->assertStatus(401);
        }

        // La sexta petición debe retornar 429 (Too Many Requests)
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'juan@example.com',
                'password' => 'WrongPassword',
            ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['error']);
    }
}
