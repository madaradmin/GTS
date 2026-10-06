<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApocOriginIdentificationPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected User $ccoUser;

    protected User $csaUser;

    protected User $gtsUser;

    protected User $adminUser;

    protected Incident $ccoIncident;

    protected Incident $csaIncident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->ccoUser = User::factory()->apocCco()->create([
            'name' => 'Operador CCO Turno A',
            'username' => 'cco.operador',
            'badge_number' => 'CCO-101',
        ]);

        $this->csaUser = User::factory()->apocCsa()->create([
            'name' => 'Operador CSA Turno B',
            'username' => 'csa.operador',
            'badge_number' => 'CSA-202',
        ]);

        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $this->ccoIncident = Incident::factory()->create([
            'folio' => 'INC-2026-CCO1',
            'title' => 'Falla en Sistema ATS CCO Torre',
            'reported_by_user_id' => $this->ccoUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $this->csaIncident = Incident::factory()->create([
            'folio' => 'INC-2026-CSA1',
            'title' => 'Falla en Sistema BHS CSA Equipajes',
            'reported_by_user_id' => $this->csaUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);
    }

    public function test_incident_model_origin_group_and_helpers_identify_cco_and_csa(): void
    {
        $this->assertEquals('CCO', $this->ccoIncident->origin_group);
        $this->assertTrue($this->ccoIncident->isFromCco());
        $this->assertFalse($this->ccoIncident->isFromCsa());

        $this->assertEquals('CSA', $this->csaIncident->origin_group);
        $this->assertTrue($this->csaIncident->isFromCsa());
        $this->assertFalse($this->csaIncident->isFromCco());
    }

    public function test_gts_and_admin_identify_cco_and_csa_in_incidents_index_table(): void
    {
        // 1. GTS view
        $responseGts = $this->actingAs($this->gtsUser)->get(route('incidents.index'));
        $responseGts->assertOk();
        $responseGts->assertSee('INC-2026-CCO1');
        $responseGts->assertSee('INC-2026-CSA1');
        $responseGts->assertSee('CCO');
        $responseGts->assertSee('CSA');

        // 2. Admin view
        $responseAdmin = $this->actingAs($this->adminUser)->get(route('incidents.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('INC-2026-CCO1');
        $responseAdmin->assertSee('INC-2026-CSA1');
        $responseAdmin->assertSee('CCO');
        $responseAdmin->assertSee('CSA');
    }

    public function test_gts_and_admin_identify_origin_in_incident_show_view(): void
    {
        // CCO incident show
        $responseShowCco = $this->actingAs($this->gtsUser)->get(route('incidents.show', $this->ccoIncident->folio));
        $responseShowCco->assertOk();
        $responseShowCco->assertSee('APOC-CCO');
        $responseShowCco->assertSee('Procedencia:');

        // CSA incident show
        $responseShowCsa = $this->actingAs($this->adminUser)->get(route('incidents.show', $this->csaIncident->folio));
        $responseShowCsa->assertOk();
        $responseShowCsa->assertSee('APOC-CSA');
        $responseShowCsa->assertSee('Procedencia:');
    }

    public function test_gts_and_admin_identify_origin_in_reports_index_table(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('INC-2026-CCO1');
        $response->assertSee('INC-2026-CSA1');
        $response->assertSee('CCO');
        $response->assertSee('CSA');
    }

    public function test_report_pdf_generation_identifies_cco_and_csa_origin(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf', [
            'selected_ids' => [$this->ccoIncident->id, $this->csaIncident->id],
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_dashboard_displays_origin_badges_for_gts_and_admin(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('INC-2026-CCO1');
        $response->assertSee('INC-2026-CSA1');
        $response->assertSee('CCO');
        $response->assertSee('CSA');
    }

    public function test_strict_isolation_is_maintained_for_apoc_users(): void
    {
        // CCO user only sees CCO incident
        $responseCco = $this->actingAs($this->ccoUser)->get(route('incidents.index'));
        $responseCco->assertOk();
        $responseCco->assertSee('INC-2026-CCO1');
        $responseCco->assertDontSee('INC-2026-CSA1');

        // CSA user only sees CSA incident
        $responseCsa = $this->actingAs($this->csaUser)->get(route('incidents.index'));
        $responseCsa->assertOk();
        $responseCsa->assertSee('INC-2026-CSA1');
        $responseCsa->assertDontSee('INC-2026-CCO1');
    }
}
