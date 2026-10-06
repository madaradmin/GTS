<?php

namespace Tests\Feature;

use App\Http\Requests\AddNoteRequest;
use App\Http\Requests\AssignIncidentRequest;
use App\Http\Requests\RejectResolutionRequest;
use App\Http\Requests\ResolveIncidentRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IncidentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_incident_request_validates_required_fields(): void
    {
        $request = new StoreIncidentRequest;
        $validator = Validator::make([], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
        $this->assertArrayHasKey('location', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('priority_id', $validator->errors()->toArray());
    }

    public function test_store_incident_request_passes_with_valid_data(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $priority = Priority::factory()->create(['is_active' => true]);

        $data = [
            'title' => 'Falla en lector biométrico',
            'description' => 'El torniquete 02 de la puerta 14B no reconoce tarjetas.',
            'location' => 'Terminal 1 - Puerta 14B',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ];

        $request = new StoreIncidentRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_assign_incident_request_requires_active_gts_user(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create(['is_active' => true]);

        $request = new AssignIncidentRequest;

        // 1. Asignar a usuario APOC debe fallar
        $v1 = Validator::make(['assigned_to_user_id' => $apocUser->id], $request->rules(), $request->messages());
        $this->assertTrue($v1->fails());
        $this->assertArrayHasKey('assigned_to_user_id', $v1->errors()->toArray());

        // 2. Asignar a técnico GTS activo debe pasar
        $v2 = Validator::make(['assigned_to_user_id' => $gtsUser->id], $request->rules(), $request->messages());
        $this->assertFalse($v2->fails());
    }

    public function test_resolve_incident_request_requires_minimum_length_solution(): void
    {
        $request = new ResolveIncidentRequest;

        // Solución muy corta (< 15 caracteres) debe fallar
        $v1 = Validator::make(['solution_description' => 'listo'], $request->rules(), $request->messages());
        $this->assertTrue($v1->fails());
        $this->assertArrayHasKey('solution_description', $v1->errors()->toArray());

        // Solución técnica detallada debe pasar
        $v2 = Validator::make(['solution_description' => 'Se reemplazó la fuente de poder del switch.'], $request->rules(), $request->messages());
        $this->assertFalse($v2->fails());
    }

    public function test_add_note_request_validates_content(): void
    {
        $request = new AddNoteRequest;

        $v1 = Validator::make(['note' => 'ok'], $request->rules(), $request->messages());
        $this->assertTrue($v1->fails());

        $v2 = Validator::make(['note' => 'Revisión en sitio iniciada con multímetro.'], $request->rules(), $request->messages());
        $this->assertFalse($v2->fails());
    }

    public function test_reject_resolution_request_requires_explanation(): void
    {
        $request = new RejectResolutionRequest;

        $v1 = Validator::make(['rejection_reason' => 'sigue mal'], $request->rules(), $request->messages());
        $this->assertTrue($v1->fails());

        $v2 = Validator::make(['rejection_reason' => 'La cámara continúa sin emitir señal RTSP a consola.'], $request->rules(), $request->messages());
        $this->assertFalse($v2->fails());
    }
}
