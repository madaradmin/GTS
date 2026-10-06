<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5InputMemoryEliminationAndFinalSystemVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $apocUser;

    protected User $gtsUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
    }

    public function test_login_page_preserves_username_autocomplete_and_secure_password_handling(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        $content = $response->getContent();

        // 1. Username en login preserva su capacidad de ser recordado
        $this->assertStringContainsString('autocomplete="username"', $content);
        $this->assertStringContainsString('name="username"', $content);

        // 2. Contraseña utiliza current-password nativo sin almacenamiento manual
        $this->assertStringContainsString('autocomplete="current-password"', $content);
        $this->assertStringContainsString('type="password"', $content);

        // 3. No existen scripts personalizados almacenando contraseñas en localStorage/cookies
        $this->assertStringNotContainsString('localStorage.setItem', $content);
        $this->assertStringNotContainsString('sessionStorage.setItem', $content);
    }

    public function test_sidebar_search_has_autocomplete_off_and_disabled_browser_heuristics(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // Formulario y campo de búsqueda rápida del sidebar con autocomplete="off"
        $this->assertStringContainsString('action="'.route('incidents.index').'" method="GET" class="relative" autocomplete="off"', $content);
        $this->assertStringContainsString('name="search"', $content);
        $this->assertStringContainsString('autocomplete="off"', $content);
        $this->assertStringContainsString('autocorrect="off"', $content);
        $this->assertStringContainsString('spellcheck="false"', $content);
    }

    public function test_incidents_index_search_and_filter_form_has_autocomplete_off(): void
    {
        // 1. Inspección de formulario e input
        $response = $this->actingAs($this->apocUser)->get(route('incidents.index'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('method="GET" action="'.route('incidents.index').'" class="bg-surface-subtle border border-border-subtle p-4 rounded-xl shadow-sm flex flex-col gap-4" autocomplete="off"', $content);
        $this->assertStringContainsString('name="search"', $content);
        $this->assertStringContainsString('placeholder="Buscar por folio, asunto o ubicación..."', $content);
        $this->assertStringContainsString('autocomplete="off"', $content);

        // 2. Comprobación de que la búsqueda funcional sigue operando con normalidad
        $category = Category::first();
        Incident::factory()->create([
            'title' => 'Falla Sensor Perimetral Alpha',
            'reported_by_user_id' => $this->apocUser->id,
            'category_id' => $category->id,
        ]);

        $searchResponse = $this->actingAs($this->apocUser)->get(route('incidents.index', ['search' => 'Sensor Perimetral Alpha']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Falla Sensor Perimetral Alpha');
    }

    public function test_incidents_create_form_and_inputs_have_autocomplete_off(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('incidents.create'));
        $response->assertOk();

        $content = $response->getContent();

        // Formulario con autocomplete="off"
        $this->assertStringContainsString('id="incidentForm" autocomplete="off"', $content);

        // Inputs title y location con autocomplete="off"
        $this->assertStringContainsString('id="title"', $content);
        $this->assertStringContainsString('id="location"', $content);

        // Creación funcional de incidencia
        $category = Category::first();
        $priority = Priority::first();

        $storeResponse = $this->actingAs($this->gtsUser)->post(route('incidents.store'), [
            'title' => 'Incidencia de Prueba Autocomplete Off',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Puerta 22 Sala B',
            'description' => 'Descripción detallada de la prueba de desactivación de historial en inputs del sistema.',
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('incidents', [
            'title' => 'Incidencia de Prueba Autocomplete Off',
            'location' => 'Puerta 22 Sala B',
        ]);
    }

    public function test_audit_index_search_and_date_inputs_have_autocomplete_off(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Formulario y campo de búsqueda con autocomplete="off"
        $this->assertStringContainsString('action="'.route('audit.index').'" class="flex flex-col gap-4" autocomplete="off"', $content);
        $this->assertStringContainsString('name="search"', $content);
        $this->assertStringContainsString('id="date_from"', $content);
        $this->assertStringContainsString('id="date_to"', $content);

        // Filtro funcional en auditoría
        $filterResponse = $this->actingAs($this->adminUser)->get(route('audit.index', ['search' => 'INC-']));
        $filterResponse->assertOk();
    }

    public function test_users_index_search_and_administrative_modals_have_autocomplete_off(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Barra de búsqueda con autocomplete="off"
        $this->assertStringContainsString('action="'.route('users.index').'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center" autocomplete="off"', $content);

        // Modal de creación con autocomplete="off"
        $this->assertStringContainsString('action="'.route('users.store').'" class="mt-4 flex flex-col gap-4" autocomplete="off"', $content);
        $this->assertStringContainsString('autocomplete="new-password"', $content);

        // Búsqueda funcional de usuarios
        $searchResponse = $this->actingAs($this->adminUser)->get(route('users.index', ['search' => $this->gtsUser->name]));
        $searchResponse->assertOk();
        $searchResponse->assertSee($this->gtsUser->name);
    }

    public function test_catalogs_index_search_and_modals_have_autocomplete_off(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('catalogs.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Barra de búsqueda con autocomplete="off"
        $this->assertStringContainsString('action="'.route('catalogs.index').'" class="flex flex-col md:flex-row items-center gap-3" autocomplete="off"', $content);

        // Modal de creación con autocomplete="off"
        $this->assertStringContainsString('action="'.route('catalogs.store').'" class="space-y-4" autocomplete="off"', $content);

        // Búsqueda funcional de catálogos
        $cat = Category::first();
        $searchResponse = $this->actingAs($this->adminUser)->get(route('catalogs.index', ['search' => $cat->name]));
        $searchResponse->assertOk();
        $searchResponse->assertSee($cat->name);
    }

    public function test_reports_index_search_dates_and_modals_have_autocomplete_off(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Formulario de filtros operativos con autocomplete="off"
        $this->assertStringContainsString('action="'.route('reports.index').'" class="flex flex-col gap-4" autocomplete="off"', $content);

        // Modal de correo con autocomplete="off"
        $this->assertStringContainsString('action="'.route('reports.email').'" class="p-5 flex flex-col gap-4" autocomplete="off"', $content);

        // Modal de WhatsApp con autocomplete="off"
        $this->assertStringContainsString('action="'.route('reports.whatsapp').'" class="p-5 flex flex-col gap-4" autocomplete="off"', $content);
    }

    public function test_indicators_index_date_filters_have_autocomplete_off(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('indicators.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Formulario con autocomplete="off"
        $this->assertStringContainsString('action="'.route('indicators.index').'" class="flex flex-col sm:flex-row items-center justify-between gap-4" autocomplete="off"', $content);
    }

    public function test_system_does_not_use_localstorage_or_sessionstorage_for_search_history(): void
    {
        $jsContent = file_get_contents(resource_path('js/app.js'));

        $this->assertStringNotContainsString('localStorage.setItem', $jsContent);
        $this->assertStringNotContainsString('sessionStorage.setItem', $jsContent);
        $this->assertStringNotContainsString('document.cookie =', $jsContent);
        $this->assertStringNotContainsString('recentSearches', $jsContent);
        $this->assertStringNotContainsString('searchHistory', $jsContent);
    }
}
