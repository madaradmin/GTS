<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rutas temporales de prueba para verificar el middleware
        Route::get('/test-apoc-route', fn () => 'OK APOC')->middleware(['web', 'role:apoc']);
        Route::get('/test-gts-route', fn () => 'OK GTS')->middleware(['web', 'role:gts']);
        Route::get('/test-shared-route', fn () => 'OK SHARED')->middleware(['web', 'role:apoc,gts']);
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/test-apoc-route');

        $response->assertRedirect('/login');
    }

    public function test_inactive_users_are_logged_out_and_redirected(): void
    {
        $user = User::factory()->apoc()->inactive()->create();

        $response = $this->actingAs($user)->get('/test-apoc-route');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_apoc_user_can_access_apoc_route_but_not_gts_route(): void
    {
        $apocUser = User::factory()->apoc()->create();

        // Acceso permitido a ruta APOC
        $this->actingAs($apocUser)
            ->get('/test-apoc-route')
            ->assertStatus(200)
            ->assertSee('OK APOC');

        // Acceso denegado a ruta GTS
        $this->actingAs($apocUser)
            ->get('/test-gts-route')
            ->assertStatus(403);
    }

    public function test_gts_user_can_access_gts_route_but_not_apoc_route(): void
    {
        $gtsUser = User::factory()->gts()->create();

        // Acceso permitido a ruta GTS
        $this->actingAs($gtsUser)
            ->get('/test-gts-route')
            ->assertStatus(200)
            ->assertSee('OK GTS');

        // Acceso denegado a ruta APOC
        $this->actingAs($gtsUser)
            ->get('/test-apoc-route')
            ->assertStatus(403);
    }

    public function test_admin_has_universal_access_to_all_role_routes(): void
    {
        $adminUser = User::factory()->admin()->create();

        $this->actingAs($adminUser)
            ->get('/test-apoc-route')
            ->assertStatus(200);

        $this->actingAs($adminUser)
            ->get('/test-gts-route')
            ->assertStatus(200);

        $this->actingAs($adminUser)
            ->get('/test-shared-route')
            ->assertStatus(200);
    }
}
