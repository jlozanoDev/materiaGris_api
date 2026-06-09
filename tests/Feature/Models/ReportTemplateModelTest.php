<?php

namespace Tests\Feature\Models;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ReportTemplate;

class ReportTemplateModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function factory_creates_template_with_expected_attributes(): void
    {
        $template = ReportTemplate::factory()->create([
            'name' => 'Informe Médico General',
            'description' => 'Plantilla para informes médicos generales',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('report_templates', [
            'id' => $template->id,
            'name' => 'Informe Médico General',
        ]);

        $this->assertNotNull($template->id);
        $this->assertEquals('Informe Médico General', $template->name);
        $this->assertTrue($template->is_active);
        $this->assertIsArray($template->structure);
    }

    #[Test]
    public function soft_delete_removes_from_active_queries_but_preserves_record(): void
    {
        $template = ReportTemplate::factory()->create(['is_active' => true]);

        $templateId = $template->id;
        $template->delete();

        $this->assertSoftDeleted('report_templates', ['id' => $templateId]);

        // Default query excludes soft-deleted
        $this->assertNull(ReportTemplate::find($templateId));

        // withTrashed still finds it
        $this->assertNotNull(ReportTemplate::withTrashed()->find($templateId));
    }

    #[Test]
    public function structure_is_casted_to_array(): void
    {
        $structure = [
            'sections' => [
                [
                    'title' => 'Datos del paciente',
                    'rows' => [
                        [
                            'columns' => [
                                ['type' => 'text', 'label' => 'Nombre', 'field' => 'patient_name'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $template = ReportTemplate::factory()->create([
            'structure' => $structure,
        ]);

        $this->assertIsArray($template->structure);
        $this->assertEquals('Datos del paciente', $template->structure['sections'][0]['title']);

        // Verify it's stored as JSON in DB but comes back as array
        $fromDb = ReportTemplate::find($template->id);
        $this->assertIsArray($fromDb->structure);
        $this->assertEquals($structure, $fromDb->structure);
    }

    #[Test]
    public function is_active_is_casted_to_boolean(): void
    {
        $active = ReportTemplate::factory()->create(['is_active' => true]);
        $inactive = ReportTemplate::factory()->create(['is_active' => false]);

        $this->assertTrue($active->is_active);
        $this->assertFalse($inactive->is_active);
    }
}
