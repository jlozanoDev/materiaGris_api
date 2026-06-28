<?php

namespace Tests\Unit\Services;

use App\Exceptions\AiResponseException;
use App\Services\LlmExtractorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LlmExtractorServiceTest extends TestCase
{
    private function createService(): LlmExtractorService
    {
        return new LlmExtractorService(config('llm'));
    }

    private function sampleTemplateStructure(): array
    {
        return [
            'sections' => [
                [
                    'title' => 'Sección principal',
                    'rows' => [
                        [
                            'columns' => [
                                [
                                    'type' => 'text',
                                    'label' => 'Motivo de consulta',
                                    'field' => 'motivo_consulta',
                                    'required' => true,
                                    'ai_help_description' => 'Describe el motivo de la consulta médica',
                                ],
                                [
                                    'type' => 'text',
                                    'label' => 'Diagnóstico',
                                    'field' => 'diagnostico',
                                    'required' => true,
                                    'ai_help_description' => 'Indica el diagnóstico principal del paciente',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Signos vitales',
                    'rows' => [
                        [
                            'columns' => [
                                [
                                    'type' => 'number',
                                    'label' => 'Presión arterial',
                                    'field' => 'presion_arterial',
                                    'required' => false,
                                    'ai_help_description' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ─── sanitizeTranscript ───────────────────────────────────

    #[Test]
    public function sanitize_transcript_removes_code_fences(): void
    {
        $service = $this->createService();
        $transcript = "El paciente mencionó:\n```\ncódigo irrelevante\n```\ny luego dijo algo más\n~~~\notro fence\n~~~\ny ``inline`` code";

        $result = $service->sanitizeTranscript($transcript);

        $this->assertStringNotContainsString('```', $result);
        $this->assertStringNotContainsString('~~~', $result);
        $this->assertStringNotContainsString('``', $result);
        $this->assertStringContainsString('El paciente mencionó', $result);
        $this->assertStringContainsString('código irrelevante', $result);
        $this->assertStringContainsString('y luego dijo algo más', $result);
    }

    #[Test]
    public function sanitize_transcript_removes_delimiters(): void
    {
        $service = $this->createService();
        $transcript = "Primera línea\n---\nSegunda línea\n***\nTercera línea\n===\nCuarta línea";

        $result = $service->sanitizeTranscript($transcript);

        $this->assertStringNotContainsString('---', $result);
        $this->assertStringNotContainsString('***', $result);
        $this->assertStringNotContainsString('===', $result);
        $this->assertStringContainsString('Primera línea', $result);
        $this->assertStringContainsString('Segunda línea', $result);
        $this->assertStringContainsString('Tercera línea', $result);
        $this->assertStringContainsString('Cuarta línea', $result);
    }

    #[Test]
    public function sanitize_transcript_removes_html_tags(): void
    {
        $service = $this->createService();
        $transcript = 'Paciente <script>alert("xss")</script> y <b>notas</b> en <i>HTML</i>';

        $result = $service->sanitizeTranscript($transcript);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringNotContainsString('</b>', $result);
        $this->assertStringNotContainsString('<i>', $result);
        $this->assertStringContainsString('Paciente', $result);
        $this->assertStringContainsString('alert', $result);
    }

    #[Test]
    public function sanitize_transcript_normalizes_whitespace(): void
    {
        $service = $this->createService();
        $transcript = "Texto   con   múltiples    espacios\n\n\n\nmúltiples\nsaltos";

        $result = $service->sanitizeTranscript($transcript);

        $this->assertStringNotContainsString('   ', $result);
        $this->assertStringNotContainsString("\n\n\n", $result);
    }

    #[Test]
    public function sanitize_transcript_trims_result(): void
    {
        $service = $this->createService();
        $transcript = "  \n  texto con espacios alrededor  \n  ";

        $result = $service->sanitizeTranscript($transcript);

        $this->assertEquals('texto con espacios alrededor', trim($result));
        $this->assertEquals($result, trim($result));
    }

    // ─── buildSystemPrompt ────────────────────────────────────

    #[Test]
    public function build_system_prompt_includes_all_template_fields(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt($this->sampleTemplateStructure());

        // All field keys present
        $this->assertStringContainsString('motivo_consulta', $prompt);
        $this->assertStringContainsString('diagnostico', $prompt);
        $this->assertStringContainsString('presion_arterial', $prompt);

        // All labels present
        $this->assertStringContainsString('Motivo de consulta', $prompt);
        $this->assertStringContainsString('Diagnóstico', $prompt);
        $this->assertStringContainsString('Presión arterial', $prompt);
    }

    #[Test]
    public function build_system_prompt_includes_ai_help_description(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt($this->sampleTemplateStructure());

        $this->assertStringContainsString('Describe el motivo de la consulta médica', $prompt);
        $this->assertStringContainsString('Indica el diagnóstico principal del paciente', $prompt);
    }

    #[Test]
    public function build_system_prompt_falls_back_to_label(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt($this->sampleTemplateStructure());

        // presion_arterial has ai_help_description=null, should render with its label
        $this->assertStringContainsString('presion_arterial', $prompt);
        $this->assertStringContainsString('Presión arterial', $prompt);
    }

    #[Test]
    public function build_system_prompt_has_role_definition(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt($this->sampleTemplateStructure());

        $this->assertStringContainsString('asistente experto en medicina', $prompt);
    }

    #[Test]
    public function build_system_prompt_has_json_rule(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt($this->sampleTemplateStructure());

        $this->assertStringContainsString('JSON', $prompt);
    }

    // ─── parseLlmResponse ─────────────────────────────────────

    #[Test]
    public function parse_llm_response_extracts_data_correctly(): void
    {
        $service = $this->createService();
        $responseBody = json_encode([
            'motivo_consulta' => 'Paciente presenta dolor de cabeza',
            'diagnostico' => 'Migraña',
            'presion_arterial' => null,
        ]);

        $result = $service->parseLlmResponse($responseBody, ['motivo_consulta', 'diagnostico', 'presion_arterial']);

        $this->assertArrayHasKey('extracted_data', $result);
        $this->assertArrayHasKey('confidence_scores', $result);
        $this->assertArrayHasKey('warnings', $result);

        $this->assertEquals('Paciente presenta dolor de cabeza', $result['extracted_data']['motivo_consulta']);
        $this->assertEquals('Migraña', $result['extracted_data']['diagnostico']);
        $this->assertNull($result['extracted_data']['presion_arterial']);
    }

    #[Test]
    public function parse_llm_response_throws_on_invalid_json(): void
    {
        $service = $this->createService();

        $this->expectException(AiResponseException::class);

        $service->parseLlmResponse('{ invalid json }', ['motivo_consulta']);
    }

    #[Test]
    public function parse_llm_response_handles_missing_confidence(): void
    {
        $service = $this->createService();
        $responseBody = json_encode([
            'motivo_consulta' => 'Dolor abdominal',
            'diagnostico' => 'Gastritis',
        ]);

        $result = $service->parseLlmResponse($responseBody, ['motivo_consulta', 'diagnostico']);

        // Without confidence_scores, should default to 1.0 for found, 0.0 for null
        $this->assertEquals(1.0, $result['confidence_scores']['motivo_consulta']);
        $this->assertEquals(1.0, $result['confidence_scores']['diagnostico']);
    }

    #[Test]
    public function parse_llm_response_generates_warnings_for_null_fields(): void
    {
        $service = $this->createService();
        $responseBody = json_encode([
            'motivo_consulta' => 'Control rutinario',
            'diagnostico' => null,
            'presion_arterial' => null,
        ]);

        $result = $service->parseLlmResponse($responseBody, ['motivo_consulta', 'diagnostico', 'presion_arterial']);

        $this->assertCount(2, $result['warnings']);
        $this->assertEquals(1.0, $result['confidence_scores']['motivo_consulta']);
        $this->assertEquals(0.0, $result['confidence_scores']['diagnostico']);
        $this->assertEquals(0.0, $result['confidence_scores']['presion_arterial']);
    }

    #[Test]
    public function parse_llm_response_discards_extra_keys(): void
    {
        $service = $this->createService();
        $responseBody = json_encode([
            'motivo_consulta' => 'Dolor',
            'diagnostico' => 'Gastritis',
            'campo_extra' => 'Esto debería ser descartado',
            'otro_extra' => 42,
        ]);

        $result = $service->parseLlmResponse($responseBody, ['motivo_consulta', 'diagnostico']);

        $this->assertCount(2, $result['extracted_data']);
        $this->assertArrayNotHasKey('campo_extra', $result['extracted_data']);
        $this->assertArrayNotHasKey('otro_extra', $result['extracted_data']);
    }

    #[Test]
    public function parse_llm_response_throws_on_empty_json(): void
    {
        $service = $this->createService();

        $this->expectException(AiResponseException::class);

        $service->parseLlmResponse('', ['motivo_consulta']);
    }

    // ─── buildRequestPayload ──────────────────────────────────

    #[Test]
    public function build_request_payload_has_correct_structure(): void
    {
        $service = $this->createService();
        $systemPrompt = 'You are a medical expert. Extract data.';
        $userMessage = 'DATOS DEL PACIENTE: ...';

        $payload = $service->buildRequestPayload($systemPrompt, $userMessage);

        $this->assertArrayHasKey('model', $payload);
        $this->assertArrayHasKey('messages', $payload);
        $this->assertCount(2, $payload['messages']);

        $this->assertEquals('system', $payload['messages'][0]['role']);
        $this->assertEquals($systemPrompt, $payload['messages'][0]['content']);
        $this->assertEquals('user', $payload['messages'][1]['role']);
        $this->assertEquals($userMessage, $payload['messages'][1]['content']);

        $this->assertArrayHasKey('response_format', $payload);
        $this->assertEquals(['type' => 'json_object'], $payload['response_format']);

        $this->assertArrayHasKey('temperature', $payload);
        $this->assertEquals(0.1, $payload['temperature']);
    }

    #[Test]
    public function build_request_payload_uses_configured_model(): void
    {
        $service = $this->createService();
        $payload = $service->buildRequestPayload('prompt', 'message');

        $this->assertEquals(config('llm.model'), $payload['model']);
    }
}
