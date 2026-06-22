<?php

namespace Tests\Feature\Models;

use App\Models\ReportTemplate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTemplateFactoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function factory_structure_has_ai_help_description_on_fields(): void
    {
        $template = ReportTemplate::factory()->create();

        $structure = $template->structure;
        $this->assertIsArray($structure);
        $this->assertArrayHasKey('sections', $structure);
        $this->assertNotEmpty($structure['sections']);

        foreach ($structure['sections'] as $section) {
            $this->assertArrayHasKey('rows', $section);
            foreach ($section['rows'] as $row) {
                $this->assertArrayHasKey('columns', $row);
                foreach ($row['columns'] as $column) {
                    $this->assertArrayHasKey('ai_help_description', $column, 'Each column must have ai_help_description');
                    $this->assertNotEmpty($column['ai_help_description']);
                }
            }
        }
    }

    #[Test]
    public function ai_help_description_is_different_per_column(): void
    {
        $template = ReportTemplate::factory()->create();

        $descriptions = [];
        foreach ($template->structure['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                foreach ($row['columns'] as $column) {
                    $descriptions[] = $column['ai_help_description'];
                }
            }
        }

        $unique = array_unique($descriptions);
        $this->assertGreaterThan(1, count($unique), 'Each column should have a unique ai_help_description');
    }
}
