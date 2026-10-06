<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Comprobar: Abrir GTS desde la raíz (/) lleva directamente a Login.
     */
    public function test_opening_root_redirects_directly_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * 2. Comprobar: La página de Login se muestra con éxito (status 200) tras la redirección.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Iniciar sesión');
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
    }

    /**
     * 3. Comprobar: Iniciar sesión correctamente entra al dashboard correspondiente.
     */
    public function test_user_can_authenticate_and_reach_dashboard(): void
    {
        $user = User::where('username', 'admin')->firstOrFail();

        $response = $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));

        $dashResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashResponse->assertOk();
    }

    /**
     * 4. Comprobar: Cerrar sesión invalida la sesión y redirige al inicio/login.
     */
    public function test_user_can_logout_and_is_redirected(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * 5. Comprobar: Intentar acceder a rutas protegidas sin autenticarse redirige a Login.
     */
    public function test_unauthenticated_access_to_protected_routes_redirects_to_login(): void
    {
        $protectedRoutes = [
            route('dashboard'),
            route('incidents.index'),
            route('reports.index'),
            route('indicators.index'),
            route('profile.edit'),
        ];

        foreach ($protectedRoutes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('login'));
        }
    }

    /**
     * 6. Comprobar: La vista welcome.blade.php ha sido eliminada por completo.
     */
    public function test_welcome_view_file_is_completely_eliminated(): void
    {
        $welcomePath = resource_path('views/welcome.blade.php');
        $this->assertFileDoesNotExist($welcomePath);
    }
}
