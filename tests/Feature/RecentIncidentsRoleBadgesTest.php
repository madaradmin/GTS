<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecentIncidentsRoleBadgesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_recent_incidents_displays_role_badges_for_cco_csa_gts_and_admin(): void
    {
        $ccoUser = User::factory()->apocCco()->create([
            'username' => 'cco.op1',
            'badge_number' => 'CCO-101',
        ]);
        $csaUser = User::factory()->apocCsa()->create([
            'username' => 'csa.op2',
            'badge_number' => 'CSA-202',
        ]);
        $gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        // 1. Incidencia creada por CCO
        $incCco = Incident::factory()->create([
            'folio' => 'INC-2026-CCO1',
            'title' => 'Falla en Sistema ATS CCO',
            'reported_by_user_id' => $ccoUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // 2. Incidencia creada por CSA
        $incCsa = Incident::factory()->create([
            'folio' => 'INC-2026-CSA1',
            'title' => 'Falla en Sistema BHS CSA',
            'reported_by_user_id' => $csaUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // 3. Incidencia creada por GTS
        $incGts = Incident::factory()->create([
            'folio' => 'INC-2026-GTS1',
            'title' => 'Falla en Router Perimetral GTS',
            'reported_by_user_id' => $gtsUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // 4. Incidencia creada por Administrador
        $incAdmin = Incident::factory()->create([
            'folio' => 'INC-2026-ADM1',
            'title' => 'Mantenimiento Preventivo DataCenter Admin',
            'reported_by_user_id' => $adminUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // Verificación de los métodos del modelo
        $this->assertTrue($incCco->isFromCco());
        $this->assertFalse($incCco->isFromCsa());
        $this->assertFalse($incCco->isFromGts());
        $this->assertFalse($incCco->isFromAdmin());

        $this->assertTrue($incCsa->isFromCsa());
        $this->assertFalse($incCsa->isFromCco());
        $this->assertFalse($incCsa->isFromGts());
        $this->assertFalse($incCsa->isFromAdmin());

        $this->assertTrue($incGts->isFromGts());
        $this->assertFalse($incGts->isFromCco());
        $this->assertFalse($incGts->isFromCsa());
        $this->assertFalse($incGts->isFromAdmin());

        $this->assertTrue($incAdmin->isFromAdmin());
        $this->assertFalse($incAdmin->isFromCco());
        $this->assertFalse($incAdmin->isFromCsa());
        $this->assertFalse($incAdmin->isFromGts());

        // Verificación en el Dashboard para Administrador (visualiza todas las incidencias)
        $responseAdmin = $this->actingAs($adminUser)->get(route('dashboard'));
        $responseAdmin->assertOk();

        // Verificar que los 4 folios y sus respectivos badges aparecen en la vista
        $responseAdmin->assertSee('INC-2026-CCO1');
        $responseAdmin->assertSee('title="Procedencia: APOC-CCO">CCO</span>', false);

        $responseAdmin->assertSee('INC-2026-CSA1');
        $responseAdmin->assertSee('title="Procedencia: APOC-CSA">CSA</span>', false);

        $responseAdmin->assertSee('INC-2026-GTS1');
        $responseAdmin->assertSee('title="Procedencia: GTS">GTS</span>', false);

        $responseAdmin->assertSee('INC-2026-ADM1');
        $responseAdmin->assertSee('title="Procedencia: Administrador">ADMIN</span>', false);

        // Verificación en el Dashboard para GTS
        $responseGts = $this->actingAs($gtsUser)->get(route('dashboard'));
        $responseGts->assertOk();
        $responseGts->assertSee('INC-2026-CCO1');
        $responseGts->assertSee('title="Procedencia: APOC-CCO">CCO</span>', false);
        $responseGts->assertSee('INC-2026-CSA1');
        $responseGts->assertSee('title="Procedencia: APOC-CSA">CSA</span>', false);
        $responseGts->assertSee('INC-2026-GTS1');
        $responseGts->assertSee('title="Procedencia: GTS">GTS</span>', false);
        $responseGts->assertSee('INC-2026-ADM1');
        $responseGts->assertSee('title="Procedencia: Administrador">ADMIN</span>', false);
    }
}
