<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_consecutive_incidents_generate_sequential_atomic_folios(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $category = Category::where('slug', 'cctv')->firstOrFail();
        $priority = Priority::where('slug', 'media')->firstOrFail();

        $year = date('Y');

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($apocUser)->post(route('incidents.store'), [
                'title' => "Incidencia de prueba secuencial {$i}",
                'description' => "Verificación de folios atómicos continuos {$i}",
                'category_id' => $category->id,
                'priority_id' => $priority->id,
                'location' => "Terminal - Puerta {$i}",
            ]);
        }

        $incidents = Incident::orderBy('id', 'asc')->get();
        $this->assertCount(3, $incidents);

        $seq1 = (int) substr($incidents[0]->folio, -4);
        $seq2 = (int) substr($incidents[1]->folio, -4);
        $seq3 = (int) substr($incidents[2]->folio, -4);

        $this->assertEquals($seq1 + 1, $seq2);
        $this->assertEquals($seq2 + 1, $seq3);
        $this->assertMatchesRegularExpression('/^INC-\d{4}-\d{4}$/', $incidents[0]->folio);
    }

    public function test_cannot_close_incident_if_not_in_resolved_status(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $newIncident = Incident::factory()->nueva()->create();
        $assignedIncident = Incident::factory()->asignada()->create();
        $inAttentionIncident = Incident::factory()->enAtencion()->create();

        // Intento de cerrar ticket NUEVO -> 403 Forbidden
        $this->actingAs($apocUser)->post(route('incidents.close', $newIncident->folio), [
            'closing_notes' => 'Cierre inválido',
        ])->assertStatus(403);

        // Intento de cerrar ticket ASIGNADO -> 403 Forbidden
        $this->actingAs($apocUser)->post(route('incidents.close', $assignedIncident->folio), [
            'closing_notes' => 'Cierre inválido',
        ])->assertStatus(403);

        // Intento de cerrar ticket EN ATENCION -> 403 Forbidden
        $this->actingAs($apocUser)->post(route('incidents.close', $inAttentionIncident->folio), [
            'closing_notes' => 'Cierre inválido',
        ])->assertStatus(403);
    }

    public function test_cannot_reject_solution_if_not_in_resolved_status(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $newIncident = Incident::factory()->nueva()->create();
        $inAttentionIncident = Incident::factory()->enAtencion()->create();

        // Rechazo sobre ticket NUEVO -> 403 Forbidden
        $this->actingAs($apocUser)->post(route('incidents.reject-resolution', $newIncident->folio), [
            'rejection_reason' => 'Rechazo inválido',
        ])->assertStatus(403);

        // Rechazo sobre ticket EN ATENCIÓN -> 403 Forbidden
        $this->actingAs($apocUser)->post(route('incidents.reject-resolution', $inAttentionIncident->folio), [
            'rejection_reason' => 'Rechazo inválido',
        ])->assertStatus(403);
    }

    public function test_resolution_requires_detailed_solution_description(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        $response = $this->actingAs($gtsUser)->post(route('incidents.resolve', $incident->folio), [
            'solution_description' => '', // Vacío
        ]);

        $response->assertSessionHasErrors('solution_description');
        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
    }

    public function test_rejection_requires_mandatory_justification_reason(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->actingAs($apocUser)->post(route('incidents.reject-resolution', $incident->folio), [
            'rejection_reason' => '', // Vacío
        ]);

        $response->assertSessionHasErrors('rejection_reason');
        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);
    }

    public function test_assignment_requires_valid_gts_technician_id(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        // 1. Técnico inexistente (ID: 99999)
        $response1 = $this->actingAs($gtsUser)->post(route('incidents.assign', $incident->folio), [
            'assigned_to_user_id' => 99999,
        ]);
        $response1->assertSessionHasErrors('assigned_to_user_id');

        // 2. Campo ausente
        $response2 = $this->actingAs($gtsUser)->post(route('incidents.assign', $incident->folio), []);
        $response2->assertSessionHasErrors('assigned_to_user_id');
    }

    public function test_sql_injection_payloads_in_search_and_filters_are_sanitized(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        // Inyecciones SQL clásicas tipo ' OR 1=1 --
        $sqlPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE incidents; --",
            '1 UNION SELECT null, null, null--',
            "admin'--",
            "' AND 1=0 UNION ALL SELECT @@version--",
        ];

        foreach ($sqlPayloads as $payload) {
            // En búsqueda de incidencias
            $response = $this->actingAs($apocUser)->get(route('incidents.index', [
                'search' => $payload,
                'category_id' => $payload,
            ]));
            $response->assertStatus(200);

            // En bitácora de auditoría (exclusiva ADMIN)
            $auditResponse = $this->actingAs($adminUser)->get(route('audit.index', [
                'search' => $payload,
                'folio' => $payload,
            ]));
            $auditResponse->assertStatus(200);
        }

        // La base de datos debe permanecer íntegra
        $this->assertDatabaseCount('incidents', 0);
    }
}
