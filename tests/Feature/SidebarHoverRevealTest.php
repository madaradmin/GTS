<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarHoverRevealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. El layout renderiza la zona sensible en el extremo izquierdo (0px - 14px) para despliegue por hover.
     */
    public function test_proximity_sensitive_zone_is_rendered_on_the_left_edge(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // Zona sensible invisible
        $this->assertStringContainsString('fixed top-16 bottom-0 left-0 w-3.5 z-30 pointer-events-auto bg-transparent', $content);
        $this->assertStringContainsString('@mouseenter="openSidebar()"', $content);
    }

    /**
     * 2. El sidebar utiliza posicionamiento fixed superpuesto con clases de transformación para apertura suave.
     */
    public function test_sidebar_uses_fixed_overlay_and_smooth_transition_classes(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // Posicionamiento fixed superpuesto y oculto estáticamente por defecto para prevenir FOUC / flash
        $this->assertStringContainsString('fixed inset-y-0 left-0 z-40 top-16 w-64', $content);
        $this->assertStringContainsString('-translate-x-full shadow-none', $content);
        $this->assertStringContainsString('transition-transform duration-200 ease-out', $content);

        // Dinámica reactiva AlpineJS para apertura/cierre puro por hover
        $this->assertStringContainsString("sidebarHover ? 'translate-x-0 shadow-2xl shadow-black/80' : '-translate-x-full shadow-none'", $content);
    }

    /**
     * 3. El contenedor raíz define las funciones openSidebar y closeSidebar con control de debounce anti-parpadeo.
     */
    public function test_root_layout_defines_hover_sidebar_controller(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->first();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('sidebarHover: false', $content);
        $this->assertStringContainsString('openSidebar()', $content);
        $this->assertStringContainsString('closeSidebar()', $content);
        $this->assertStringContainsString('hoverTimeout: null', $content);
    }

    /**
     * 4. El contenido principal (<main>) y layout mantienen overflow-x-hidden impidiendo scroll horizontal.
     */
    public function test_layout_and_main_prevent_horizontal_overflow(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('overflow-hidden', $content);
        $this->assertStringContainsString('overflow-x-hidden', $content);
        $this->assertStringContainsString('<main class="flex-1 overflow-y-auto overflow-x-hidden relative scrollbar-thin', $content);
    }

    /**
     * 5. El botón de menú hamburguesa ha sido eliminado completamente del topbar y del sistema.
     */
    public function test_topbar_does_not_contain_hamburger_menu_button(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringNotContainsString('@click="sidebarOpen = !sidebarOpen"', $content);
        $this->assertStringNotContainsString('title="Alternar Menú Lateral"', $content);
        $this->assertStringNotContainsString('sidebarOpen', $content);
    }

    /**
     * 6. La navegación interna del sidebar (Dashboard, Incidencias, Reportes, Indicadores, Catálogos) permanece accesible.
     */
    public function test_sidebar_links_remain_fully_functional(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();

        $response->assertSee(route('dashboard'));
        $response->assertSee(route('incidents.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('indicators.index'));
        $response->assertSee(route('catalogs.index'));
        $response->assertSee(route('audit.index'));
        $response->assertSee(route('profile.edit'));
    }
}
