<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoBoClosureIdentificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_closure_identifies_user_role_tag_and_displays_in_bitacora_and_view(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->actingAs($adminUser)
            ->post("/incidencias/{$incident->folio}/cerrar", []);

        $response->assertRedirect("/incidencias/{$incident->folio}");
        $response->assertSessionHas('success', "Incidencia {$incident->folio} cerrada definitivamente con visto bueno de |Admin| {$adminUser->name}. Expediente archivado en modo inmutable.");

        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);

        // Bitácora de trazabilidad
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $adminUser->id,
            'comment' => "Visto bueno operacional otorgado por |Admin| {$adminUser->name}. Incidencia cerrada definitivamente.",
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CERRADA',
            'user_id' => $adminUser->id,
            'comment' => "Expediente archivado en modo inmutable con visto bueno de |Admin| {$adminUser->name}.",
        ]);

        // Verificación en la vista
        $viewResponse = $this->actingAs($adminUser)->get("/incidencias/{$incident->folio}");
        $viewResponse->assertOk();
        $viewResponse->assertSee("|Admin| {$adminUser->name}");
        $viewResponse->assertSee('Conformidad Operacional y Cierre Formal (Admin):');
        $viewResponse->assertSee("• Visto Bueno otorgado por |Admin| {$adminUser->name}");
        $viewResponse->assertDontSee('Conformidad Operacional y Cierre Formal (APOC):');
    }

    public function test_gts_closure_identifies_user_role_tag_and_displays_in_bitacora_and_view(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $voboPerm = \App\Models\Permission::where('slug', 'incidents.vobo_close')->first();
        if ($voboPerm) {
            $gtsUser->role->permissions()->syncWithoutDetaching([$voboPerm->id]);
        }
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/cerrar", []);

        $response->assertRedirect("/incidencias/{$incident->folio}");
        $response->assertSessionHas('success', "Incidencia {$incident->folio} cerrada definitivamente con visto bueno de {$gtsUser->getTaggedName()}. Expediente archivado en modo inmutable.");

        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);

        // Bitácora de trazabilidad
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $gtsUser->id,
            'comment' => "Visto bueno operacional otorgado por |GTS| {$gtsUser->name}. Incidencia cerrada definitivamente.",
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CERRADA',
            'user_id' => $gtsUser->id,
            'comment' => "Expediente archivado en modo inmutable con visto bueno de |GTS| {$gtsUser->name}.",
        ]);

        // Verificación en la vista
        $viewResponse = $this->actingAs($gtsUser)->get("/incidencias/{$incident->folio}");
        $viewResponse->assertOk();
        $viewResponse->assertSee("|GTS| {$gtsUser->name}");
        $viewResponse->assertSee('Conformidad Operacional y Cierre Formal (GTS):');
        $viewResponse->assertSee("• Visto Bueno otorgado por |GTS| {$gtsUser->name}");
        $viewResponse->assertDontSee('Conformidad Operacional y Cierre Formal (APOC):');
    }

    public function test_apoc_closure_with_custom_comment_identifies_user_in_bitacora_and_view(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();
        $customComment = 'Pruebas de pista superadas exitosamente.';

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/cerrar", [
                'comment' => $customComment,
            ]);

        $response->assertRedirect("/incidencias/{$incident->folio}");
        $response->assertSessionHas('success', "Incidencia {$incident->folio} cerrada definitivamente con visto bueno de {$apocUser->getTaggedName()}. Expediente archivado en modo inmutable.");

        // Verificación en la vista
        $viewResponse = $this->actingAs($apocUser)->get("/incidencias/{$incident->folio}");
        $viewResponse->assertOk();
        $viewResponse->assertSee($apocUser->getTaggedName());
        $viewResponse->assertSee("Conformidad Operacional y Cierre Formal ({$apocUser->getRoleTag()}):");
        $viewResponse->assertSee($customComment);
        $viewResponse->assertSee("• Visto Bueno otorgado por {$apocUser->getTaggedName()}");
    }
}
