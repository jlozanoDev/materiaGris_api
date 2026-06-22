<?php

namespace Tests\Feature\Models;

use App\Models\LlmInteraction;
use App\Models\PatientReport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LlmInteractionModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function factory_creates_llm_interaction(): void
    {
        $interaction = LlmInteraction::factory()->create();

        $this->assertDatabaseHas('llm_interactions', [
            'id' => $interaction->id,
        ]);

        $this->assertNotNull($interaction->patient_report_id);
        $this->assertIsArray($interaction->request_payload);
        $this->assertNull($interaction->response_payload);
        $this->assertNull($interaction->processing_time_ms);
    }

    #[Test]
    public function json_casts_work_for_payloads(): void
    {
        $requestPayload = ['model' => 'gpt-4o', 'messages' => [['role' => 'user', 'content' => 'Hello']]];
        $responsePayload = ['choices' => [['message' => ['content' => '{"key": "value"}']]]];

        $interaction = LlmInteraction::factory()->create([
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'processing_time_ms' => 1500,
        ]);

        $this->assertIsArray($interaction->request_payload);
        $this->assertEquals('gpt-4o', $interaction->request_payload['model']);
        $this->assertIsArray($interaction->response_payload);
        $this->assertEquals('{"key": "value"}', $interaction->response_payload['choices'][0]['message']['content']);
    }

    #[Test]
    public function belongs_to_patient_report(): void
    {
        $report = PatientReport::factory()->create();
        $interaction = LlmInteraction::factory()->create([
            'patient_report_id' => $report->id,
        ]);

        $this->assertInstanceOf(PatientReport::class, $interaction->patientReport);
        $this->assertEquals($report->id, $interaction->patientReport->id);
    }

    #[Test]
    public function fillable_attributes_are_mass_assignable(): void
    {
        $report = PatientReport::factory()->create();
        $requestPayload = ['model' => 'gpt-4o'];

        $interaction = LlmInteraction::create([
            'patient_report_id' => $report->id,
            'request_payload' => $requestPayload,
            'response_payload' => null,
            'processing_time_ms' => 500,
        ]);

        $this->assertEquals($report->id, $interaction->patient_report_id);
        $this->assertEquals($requestPayload, $interaction->request_payload);
        $this->assertNull($interaction->response_payload);
        $this->assertEquals(500, $interaction->processing_time_ms);
    }
}
