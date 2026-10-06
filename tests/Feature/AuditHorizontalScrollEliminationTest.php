<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditHorizontalScrollEliminationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Comprobar: La tabla de Auditoría ya no contiene contenedores con 'overflow-x-auto'.
     */
    public function test_audit_view_does_not_contain_overflow_x_auto_table_wrapper(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('audit.index'));
        $response->assertOk();

        // No debe contener el contenedor de scroll horizontal
        $response->assertDontSee('overflow-x-auto');
        $response->assertDontSee('overflow-x-scroll');
    }

    /**
     * 2. Comprobar: La tabla implementa table-fixed y distribuciones proporcionales por columna.
     */
    public function test_audit_table_uses_table_fixed_and_proportional_widths(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('audit.index'));
        $response->assertOk();

        // Debe contener table-fixed y ancho máximo delimitado
        $response->assertSee('table-fixed');
        $response->assertSee('w-full max-w-full text-left border-collapse table-fixed', false);

        // Debe definir anchos porcentuales estrictos en cabeceras
        $response->assertSee('w-[13%]', false);
        $response->assertSee('w-[12%]', false);
        $response->assertSee('w-[14%]', false);
        $response->assertSee('w-[20%]', false);
        $response->assertSee('w-[5%]', false);
        $response->assertSee('w-[10%]', false);
    }

    /**
     * 3. Comprobar: Textos largos en comentarios y operadores se truncan limpiamente con tooltip para preservar el layout sin scroll.
     */
    public function test_audit_table_truncates_long_texts_with_title_tooltips_without_overflow(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $longComment = 'Este es un comentario pericial extremadamente extenso registrado por la cuadrilla técnica durante una contingencia crítica en la plataforma internacional de pasajeros con switch redundante.';

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-99999',
            'title' => 'Incidencia Pericial con Comentario Extenso',
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $admin->id,
            'action' => 'NOTA_TECNICA',
            'field_name' => 'technical_note',
            'old_value' => 'Diagnóstico preliminar pendiente de validación física en rack',
            'new_value' => 'Reemplazo completado satisfactoriamente con módulo SFP Cisco original',
            'comment' => $longComment,
        ]);

        $response = $this->actingAs($admin)->get(route('audit.index'));
        $response->assertOk();

        // El texto original existe en el HTML como tooltip sin romper el diseño
        $response->assertSee('title="'.$longComment.'"', false);
        $response->assertSee('line-clamp-2');
        $response->assertSee('min-w-0');
        $response->assertSee('INC-2026-99999');
    }

    /**
     * 4. Comprobar: El contenedor padre utiliza clases responsivas sin desbordamiento.
     */
    public function test_audit_layout_containers_fit_within_standard_viewports(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('audit.index'));
        $response->assertOk();

        // Contenedor delimitado y padding balanceado
        $response->assertSee('max-w-7xl');
        $response->assertDontSee('w-max');
    }
}
