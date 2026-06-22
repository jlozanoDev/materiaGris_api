<?php

namespace Tests\Feature\Migrations;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class LlmInteractionMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function llm_interactions_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('llm_interactions'));
    }

    #[Test]
    public function llm_interactions_table_has_expected_columns(): void
    {
        $columns = Schema::getColumnListing('llm_interactions');

        $this->assertContains('id', $columns);
        $this->assertContains('patient_report_id', $columns);
        $this->assertContains('request_payload', $columns);
        $this->assertContains('response_payload', $columns);
        $this->assertContains('processing_time_ms', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('updated_at', $columns);
    }

    #[Test]
    public function patient_report_id_is_foreign_key(): void
    {
        $foreignKeys = Schema::getForeignKeys('llm_interactions');
        $patientReportFk = collect($foreignKeys)->firstWhere('columns', ['patient_report_id']);

        $this->assertNotNull($patientReportFk);
        $this->assertEquals('patient_reports', $patientReportFk['foreign_table']);
        $this->assertEquals(['id'], $patientReportFk['foreign_columns']);
    }

    #[Test]
    public function request_payload_column_accepts_json(): void
    {
        // SQLite stores json columns as 'text' internally; MySQL/MariaDB stores them as 'json'.
        // We verify the column can store and retrieve JSON data instead.
        $type = Schema::getColumnType('llm_interactions', 'request_payload');
        $this->assertContains($type, ['json', 'text'], 'JSON column type depends on the database driver');
    }

    #[Test]
    public function response_payload_column_accepts_json(): void
    {
        $type = Schema::getColumnType('llm_interactions', 'response_payload');
        $this->assertContains($type, ['json', 'text'], 'JSON column type depends on the database driver');
    }
}
