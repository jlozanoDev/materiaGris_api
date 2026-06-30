<?php

namespace Tests\Unit\Admin\SystemVariable;

use App\Commands\Admin\SystemVariable\GetSystemVariablesCommand;
use Tests\TestCase;

class GetSystemVariablesCommandTest extends TestCase
{
    private GetSystemVariablesCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new GetSystemVariablesCommand();
    }

    public function test_execute_returns_array(): void
    {
        $result = $this->command->execute();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_execute_returns_all_categories(): void
    {
        $result = $this->command->execute();

        $categories = array_unique(array_column($result, 'category'));
        $expected = ['paciente', 'clinica', 'fecha', 'usuario', 'medico', 'informe'];

        foreach ($expected as $cat) {
            $this->assertContains($cat, $categories, "Category '$cat' should exist");
        }
    }

    public function test_each_variable_has_required_keys(): void
    {
        $result = $this->command->execute();

        foreach ($result as $variable) {
            $this->assertArrayHasKey('category', $variable);
            $this->assertArrayHasKey('key', $variable);
            $this->assertArrayHasKey('label', $variable);
            $this->assertArrayHasKey('description', $variable);
            $this->assertNotEmpty($variable['category']);
            $this->assertNotEmpty($variable['key']);
            $this->assertNotEmpty($variable['label']);
        }
    }

    public function test_grouped_returns_variables_grouped_by_category(): void
    {
        $grouped = $this->command->grouped();

        $this->assertIsArray($grouped);

        $expectedCategories = ['paciente', 'clinica', 'fecha', 'usuario', 'medico', 'informe'];
        foreach ($expectedCategories as $cat) {
            $this->assertArrayHasKey($cat, $grouped, "Category '$cat' should be a key in grouped");
            $this->assertIsArray($grouped[$cat]);
            $this->assertNotEmpty($grouped[$cat]);
        }
    }

    public function test_grouped_contains_same_total_items_as_execute(): void
    {
        $flat = $this->command->execute();
        $grouped = $this->command->grouped();

        $totalInGrouped = array_sum(array_map(fn($items) => count($items), $grouped));

        $this->assertCount($totalInGrouped, $flat);
    }

    public function test_no_empty_categories_in_grouped(): void
    {
        $grouped = $this->command->grouped();

        foreach ($grouped as $category => $items) {
            $this->assertNotEmpty($items, "Category '$category' should not be empty");
        }
    }

    public function test_keys_are_unique_per_category(): void
    {
        $flat = $this->command->execute();

        $byCategory = [];
        foreach ($flat as $item) {
            $byCategory[$item['category']][] = $item['key'];
        }

        foreach ($byCategory as $category => $keys) {
            $this->assertCount(
                count(array_unique($keys)),
                $keys,
                "Keys in category '$category' must be unique"
            );
        }
    }

    public function test_specific_paciente_variables_exist(): void
    {
        $grouped = $this->command->grouped();

        $pacienteKeys = array_column($grouped['paciente'], 'key');
        $this->assertContains('nombre', $pacienteKeys);
        $this->assertContains('apellido', $pacienteKeys);
        $this->assertContains('dni', $pacienteKeys);
        $this->assertContains('obra_social', $pacienteKeys);
    }

    public function test_specific_fecha_variables_exist(): void
    {
        $grouped = $this->command->grouped();

        $fechaKeys = array_column($grouped['fecha'], 'key');
        $this->assertContains('actual', $fechaKeys);
        $this->assertContains('formato_largo', $fechaKeys);
        $this->assertContains('hora', $fechaKeys);
        $this->assertContains('anio', $fechaKeys);
    }

    public function test_informe_has_pagination_variables(): void
    {
        $grouped = $this->command->grouped();

        $informeKeys = array_column($grouped['informe'], 'key');
        $this->assertContains('pagina_actual', $informeKeys);
        $this->assertContains('pagina_total', $informeKeys);
        $this->assertContains('pagina_actual_de_total', $informeKeys);
    }
}
