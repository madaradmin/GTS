<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use App\Models\Status;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiAreaReassignmentModalFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gtsUser;

    protected User $apocUser;

    protected Role $cctvRole;

    protected User $cctvUser;

    protected Role $redesRole;

    protected Role $fidsRole;

    protected Role $tetraRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $this->apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $this->cctvRole = Role::firstOrCreate(['slug' => 'cctv'], ['name' => 'Área de Circuito Cerrado CCTV']);
        $this->cctvUser = User::firstOrCreate(
            ['username' => 'cctv.tech'],
            [
                'name' => 'Especialista CCTV Morales',
                'email' => 'cctv.tech@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $this->cctvRole->id,
                'is_active' => true,
            ]
        );

        $this->redesRole = Role::firstOrCreate(['slug' => 'redes'], ['name' => 'Área de Redes e Infraestructura']);
        $this->fidsRole = Role::firstOrCreate(['slug' => 'fids'], ['name' => 'Área de Información de Vuelos FIDS']);
        $this->tetraRole = Role::firstOrCreate(['slug' => 'tetra'], ['name' => 'Área de Telecomunicaciones TETRA']);
    }

    /**
     * Error 1: Seleccionar todas las áreas autorizadas no desborda assigned_specialist (varchar 50).
     */
    public function test_seleccionar_todas_las_areas_autorizadas_no_falla_por_longitud(): void
    {
        $incident = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $allAreas = ['gts', 'cctv', 'redes', 'fids', 'tetra'];

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => $allAreas,
            'assigned_to_user_id' => null,
            'comment' => 'Asignación masiva a todas las áreas técnicas especializadas.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertNull($incident->assigned_to_user_id);

        // Se persiste de forma individual en incident_assigned_areas
        $this->assertCount(5, $incident->assignedAreas);
        foreach ($allAreas as $code) {
            $this->assertTrue($incident->isAssignedToArea($code));
        }

        // assigned_specialist no debe superar 50 caracteres
        $this->assertLessThanOrEqual(50, mb_strlen((string) $incident->assigned_specialist));
    }

    /**
     * Error 2: El rol que creó la incidencia queda excluido de áreas y especialistas.
     */
    public function test_rol_creador_de_incidencia_queda_excluido_de_areas_y_especialistas(): void
    {
        $incident = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $service = app(IncidentService::class);

        // En getAssignableAreas, el rol apoc no debe aparecer
        $assignableAreas = $service->getAssignableAreas($this->admin, $incident);
        $this->assertFalse($assignableAreas->pluck('code')->contains('apoc'));

        // En getAvailableTechnicians, usuarios con rol apoc no deben aparecer
        $technicians = $service->getAvailableTechnicians($this->admin, $incident);
        $this->assertFalse($technicians->pluck('id')->contains($this->apocUser->id));

        // Intento de asignar al rol apoc debe fallar en validación
        $responseArea = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['apoc'],
        ]);
        $responseArea->assertSessionHasErrors('areas.0');

        // Intento de asignar al usuario creador debe fallar en validación
        $responseUser = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => $this->apocUser->id,
        ]);
        $responseUser->assertSessionHasErrors('assigned_to_user_id');
    }

    /**
     * Error 3: La opción TICS no aparece y es rechazada estrictamente.
     */
    public function test_tics_queda_excluido_de_areas_y_es_rechazado_en_asignacion(): void
    {
        $incident = Incident::factory()->nueva()->create();
        $service = app(IncidentService::class);

        // No aparece en getAssignableAreas
        $assignableAreas = $service->getAssignableAreas($this->admin, $incident);
        $this->assertFalse($assignableAreas->pluck('code')->contains('tics'));

        // Intento de asignar TICS en areas es rechazado
        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['tics'],
        ]);
        $response->assertSessionHasErrors('areas.0');

        // Intento de asignar TICS como técnico es rechazado
        $responseTech = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'tics',
        ]);
        $responseTech->assertSessionHasErrors('areas.0');
    }

    /**
     * Error 6: El rol ADMINISTRADOR no aparece como área ni como especialista.
     */
    public function test_administrador_queda_excluido_de_areas_y_especialistas(): void
    {
        $incident = Incident::factory()->nueva()->create();
        $service = app(IncidentService::class);

        // No aparece en áreas
        $assignableAreas = $service->getAssignableAreas($this->admin, $incident);
        $this->assertFalse($assignableAreas->pluck('code')->contains('admin'));

        // No aparece en especialistas
        $technicians = $service->getAvailableTechnicians($this->admin, $incident);
        $this->assertFalse($technicians->pluck('id')->contains($this->admin->id));

        // Intento de asignar admin como área es rechazado
        $responseArea = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['admin'],
        ]);
        $responseArea->assertSessionHasErrors('areas.0');

        // Intento de asignar admin como técnico es rechazado
        $responseUser = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => $this->admin->id,
        ]);
        $responseUser->assertSessionHasErrors('assigned_to_user_id');
    }

    /**
     * Error 5: Compatibilidad y opcionalidad del especialista técnico.
     */
    public function test_compatibilidad_estricta_de_especialista_con_areas_seleccionadas(): void
    {
        $incident = Incident::factory()->nueva()->create();

        // 1. Técnico incompatible con las áreas elegidas es rechazado
        $responseIncompatible = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['cctv'],
            'assigned_to_user_id' => $this->gtsUser->id,
        ]);
        $responseIncompatible->assertSessionHasErrors('assigned_to_user_id');

        // 2. Técnico compatible con una de las áreas elegidas tiene éxito
        $responseCompatible = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts', 'cctv'],
            'assigned_to_user_id' => $this->cctvUser->id,
            'comment' => 'Asignado a especialista de CCTV con soporte de GTS.',
        ]);
        $responseCompatible->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals($this->cctvUser->id, $incident->assigned_to_user_id);
        $this->assertTrue($incident->isAssignedToArea('cctv'));
        $this->assertTrue($incident->isAssignedToArea('gts'));

        // 3. Técnico opcional (asignación solo a nivel de áreas)
        $responseSoloAreas = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['cctv'],
            'assigned_to_user_id' => null,
            'comment' => 'Derivado solo al área de CCTV sin especialista individual.',
        ]);
        $responseSoloAreas->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertNull($incident->assigned_to_user_id);
        $this->assertTrue($incident->isAssignedToArea('cctv'));
    }

    /**
     * Error 1/Backend: Deduplicación defensiva de áreas para prevenir claves duplicadas.
     */
    public function test_deduplicacion_defensiva_de_areas_no_genera_errores(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts', 'gts', 'cctv', 'cctv', 'GTS'],
            'assigned_to_user_id' => null,
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertCount(2, $incident->assignedAreas);
    }

    /**
     * Error 4: Soporte de respuesta JSON para evitar bloqueo indefinido en el botón.
     */
    public function test_respuesta_json_exitosa_para_peticiones_asincronas(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->postJson(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => $this->gtsUser->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'folio' => $incident->folio,
        ]);
    }

    /**
     * Error 4: Soporte de respuesta JSON con error 422 para peticiones inválidas.
     */
    public function test_respuesta_json_con_errores_422_para_peticiones_invalidas(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->postJson(route('incidents.assign', $incident), [
            'areas' => ['tics'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['areas.0']);
    }
}

