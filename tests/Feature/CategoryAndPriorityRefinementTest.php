<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAndPriorityRefinementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_categories_in_database_use_cctv_and_perifericos(): void
    {
        // 1. CCTV existe y no tiene el nombre largo anterior
        $cctv = Category::where('slug', 'cctv')->firstOrFail();
        $this->assertEquals('CCTV', $cctv->name);
        $this->assertDatabaseMissing('categories', ['name' => 'CCTV y Videovigilancia']);

        // 2. Periféricos existe y sustituye a Redes
        $perifericos = Category::where('slug', 'networks')->firstOrFail();
        $this->assertEquals('Periféricos', $perifericos->name);
        $this->assertDatabaseMissing('categories', ['name' => 'Redes e Infraestructura IT']);
    }

    public function test_priorities_have_sober_institutional_colors(): void
    {
        $critica = Priority::where('slug', 'critica')->firstOrFail();
        $this->assertEquals('#D83B52', $critica->color);

        $alta = Priority::where('slug', 'alta')->firstOrFail();
        $this->assertEquals('#E06D24', $alta->color);

        $media = Priority::where('slug', 'media')->firstOrFail();
        $this->assertEquals('#E0A21A', $media->color);

        $baja = Priority::where('slug', 'baja')->firstOrFail();
        $this->assertEquals('#5B86B4', $baja->color);
    }

    public function test_badge_priority_renders_distinguishable_styles(): void
    {
        $critica = Priority::where('slug', 'critica')->firstOrFail();
        $alta = Priority::where('slug', 'alta')->firstOrFail();
        $media = Priority::where('slug', 'media')->firstOrFail();
        $baja = Priority::where('slug', 'baja')->firstOrFail();

        // 1. Crítica (Rojo con pulso)
        $viewCritica = $this->blade('<x-badge-priority :priority="$priority" />', ['priority' => $critica]);
        $viewCritica->assertSee('Crítica');
        $viewCritica->assertSee('#D83B52');
        $viewCritica->assertSee('animate-pulse');

        // 2. Alta (Naranja corporativo diferenciado de crítica)
        $viewAlta = $this->blade('<x-badge-priority :priority="$priority" />', ['priority' => $alta]);
        $viewAlta->assertSee('Alta');
        $viewAlta->assertSee('#E06D24');
        $viewAlta->assertDontSee('animate-pulse');

        // 3. Media (Verde institucional diferenciado de alta)
        $viewMedia = $this->blade('<x-badge-priority :priority="$priority" />', ['priority' => $media]);
        $viewMedia->assertSee('Media');
        $viewMedia->assertSee('#10B981');

        // 4. Baja (Azul grisáceo)
        $viewBaja = $this->blade('<x-badge-priority :priority="$priority" />', ['priority' => $baja]);
        $viewBaja->assertSee('Baja');
        $viewBaja->assertSee('#5B86B4');
    }

    public function test_dashboard_displays_cctv_and_perifericos_consistently(): void
    {
        $user = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('CCTV');
        $response->assertSee('Periféricos');
        $response->assertDontSee('CCTV y Videovigilancia');
        $response->assertDontSee('Redes e Infraestructura IT');
    }

    public function test_incident_creation_and_listing_uses_unified_category_names(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);

        $cctv = Category::where('slug', 'cctv')->firstOrFail();
        $perifericos = Category::where('slug', 'networks')->firstOrFail();
        $priorityAlta = Priority::where('slug', 'alta')->firstOrFail();

        $incident1 = $service->createIncident([
            'title' => 'Cámara domo sin señal',
            'description' => 'Falla de alimentación PoE en cámara.',
            'location' => 'Puerta 5',
            'category_id' => $cctv->id,
            'priority_id' => $priorityAlta->id,
        ], $user);

        $incident2 = $service->createIncident([
            'title' => 'Lector de código de barras bloqueado',
            'description' => 'Dispositivo USB no reconocido en mostrador 12.',
            'location' => 'Mostrador 12',
            'category_id' => $perifericos->id,
            'priority_id' => $priorityAlta->id,
        ], $user);

        $response = $this->actingAs($user)->get(route('incidents.index'));

        $response->assertOk();
        $response->assertSee('CCTV');
        $response->assertSee('Periféricos');
        $this->assertEquals('CCTV', $incident1->fresh()->category->name);
        $this->assertEquals('Periféricos', $incident2->fresh()->category->name);
    }
}
