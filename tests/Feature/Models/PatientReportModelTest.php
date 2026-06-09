<?php

namespace Tests\Feature\Models;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\PatientReport;
use App\Models\Patient;
use App\Models\User;
use App\Models\ReportTemplate;
use App\Enums\ReportStatus;
use Carbon\Carbon;

class PatientReportModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function factory_creates_report_with_draft_status(): void
    {
        $report = PatientReport::factory()->create();

        $this->assertDatabaseHas('patient_reports', [
            'id' => $report->id,
            'status' => 'draft',
        ]);

        $this->assertEquals(ReportStatus::Draft, $report->status);
        $this->assertIsArray($report->values);
        $this->assertIsArray($report->template_structure_snapshot);
    }

    #[Test]
    public function belongs_to_patient(): void
    {
        $patient = Patient::factory()->create();
        $report = PatientReport::factory()->create(['patient_id' => $patient->id]);

        $this->assertInstanceOf(Patient::class, $report->patient);
        $this->assertEquals($patient->id, $report->patient->id);
    }

    #[Test]
    public function belongs_to_user(): void
    {
        $user = User::factory()->create();
        $report = PatientReport::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $report->user);
        $this->assertEquals($user->id, $report->user->id);
    }

    #[Test]
    public function belongs_to_template_including_trashed(): void
    {
        $template = ReportTemplate::factory()->create();
        $report = PatientReport::factory()->create(['template_id' => $template->id]);

        $this->assertInstanceOf(ReportTemplate::class, $report->template);
        $this->assertEquals($template->id, $report->template->id);

        // Verify soft-deleted template still accessible via relationship
        $template->delete();
        $report->refresh();

        $this->assertNotNull($report->template);
        $this->assertEquals($template->id, $report->template->id);
    }

    #[Test]
    public function status_can_transition_from_draft_to_signed(): void
    {
        $report = PatientReport::factory()->create(['status' => ReportStatus::Draft]);

        $report->status = ReportStatus::Signed;
        $report->signed_at = Carbon::now();
        $report->save();

        $report->refresh();
        $this->assertEquals(ReportStatus::Signed, $report->status);
        $this->assertNotNull($report->signed_at);
    }

    #[Test]
    public function status_can_transition_from_signed_to_closed(): void
    {
        $report = PatientReport::factory()->create([
            'status' => ReportStatus::Signed,
            'signed_at' => Carbon::now(),
        ]);

        $report->status = ReportStatus::Closed;
        $report->closed_at = Carbon::now();
        $report->save();

        $report->refresh();
        $this->assertEquals(ReportStatus::Closed, $report->status);
        $this->assertNotNull($report->closed_at);
    }

    #[Test]
    public function json_casts_work_for_template_structure_snapshot_and_values(): void
    {
        $snapshot = ['sections' => [['title' => 'Test Section', 'rows' => []]]];
        $values = ['field_1' => 'value_1', 'field_2' => 42];

        $report = PatientReport::factory()->create([
            'template_structure_snapshot' => $snapshot,
            'values' => $values,
        ]);

        $this->assertIsArray($report->template_structure_snapshot);
        $this->assertEquals('Test Section', $report->template_structure_snapshot['sections'][0]['title']);
        $this->assertIsArray($report->values);
        $this->assertEquals('value_1', $report->values['field_1']);
        $this->assertEquals(42, $report->values['field_2']);
    }
}
