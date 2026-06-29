<?php

namespace Tests\Unit\Services;

use App\DTOs\TranscribeResult;
use App\Exceptions\AiResponseException;
use App\Services\SpeechToTextService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpeechToTextServiceTest extends TestCase
{
    private function createService(): SpeechToTextService
    {
        return new SpeechToTextService(config('stt'));
    }

    // ─── buildSystemPrompt ─────────────────────────────────────

    #[Test]
    public function build_system_prompt_includes_medical_instructions(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt(true, null);

        $this->assertStringContainsString('transcripción médica', $prompt);
        $this->assertStringContainsString('Speaker 1', $prompt);
        $this->assertStringContainsString('Speaker 2', $prompt);
        $this->assertStringContainsString('JSON', $prompt);
        $this->assertStringContainsString('duration_seconds', $prompt);
    }

    #[Test]
    public function build_system_prompt_includes_diarization_when_enabled(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt(true, null);

        // Should contain speaker identification instructions
        $this->assertStringContainsString('Speaker 1', $prompt);
        $this->assertStringContainsString('Speaker 2', $prompt);
        // Should NOT contain the no-diarization instruction
        $this->assertStringNotContainsString('No intentes identificar hablantes', $prompt);
    }

    #[Test]
    public function build_system_prompt_includes_single_speaker_when_disabled(): void
    {
        $service = $this->createService();
        $prompt = $service->buildSystemPrompt(false, null);

        // Should contain instruction to use single speaker
        $this->assertStringContainsString('No intentes identificar hablantes', $prompt);
        $this->assertStringContainsString('Speaker 1', $prompt);
    }

    // ─── buildMessages ──────────────────────────────────────────

    #[Test]
    public function build_messages_contains_input_audio_part(): void
    {
        $service = $this->createService();
        $messages = $service->buildMessages('aGVsbG8=', 'wav', true, null);

        $this->assertCount(2, $messages);
        $this->assertEquals('system', $messages[0]['role']);
        $this->assertEquals('user', $messages[1]['role']);

        $contentParts = $messages[1]['content'];
        $this->assertCount(2, $contentParts);

        // Find the input_audio part
        $audioPart = null;
        foreach ($contentParts as $part) {
            if (($part['type'] ?? '') === 'input_audio') {
                $audioPart = $part;
                break;
            }
        }

        $this->assertNotNull($audioPart, 'Missing input_audio content part');
        $this->assertEquals('aGVsbG8=', $audioPart['input_audio']['data']);
        $this->assertEquals('wav', $audioPart['input_audio']['format']);
    }

    #[Test]
    public function build_messages_contains_text_instruction(): void
    {
        $service = $this->createService();
        $messages = $service->buildMessages('aGVsbG8=', 'wav', true, 'es');

        $contentParts = $messages[1]['content'];

        // Find the text part
        $textPart = null;
        foreach ($contentParts as $part) {
            if (($part['type'] ?? '') === 'text') {
                $textPart = $part;
                break;
            }
        }

        $this->assertNotNull($textPart, 'Missing text content part');
        $this->assertStringContainsString('Transcribe', $textPart['text']);
        $this->assertStringContainsString('es', $textPart['text']);
    }

    // ─── parseTranscriptionResponse ────────────────────────────

    #[Test]
    public function parse_valid_json_returns_transcribe_result(): void
    {
        $service = $this->createService();
        $json = json_encode([
            'transcript' => 'El paciente presenta dolor de cabeza desde hace tres días.',
            'segments' => [
                ['speaker' => 'Speaker 1', 'text' => 'El paciente presenta dolor', 'start' => 0.0, 'end' => 3.5],
                ['speaker' => 'Speaker 2', 'text' => '¿Desde cuándo?', 'start' => 4.0, 'end' => 5.2],
            ],
            'language' => 'es',
            'duration_seconds' => 5.2,
        ]);

        $result = $service->parseTranscriptionResponse($json);

        $this->assertInstanceOf(TranscribeResult::class, $result);
        $this->assertEquals('El paciente presenta dolor de cabeza desde hace tres días.', $result->transcript);
        $this->assertCount(2, $result->segments);
        $this->assertEquals('es', $result->language);
        $this->assertEquals(5.2, $result->durationSeconds);
    }

    #[Test]
    public function parse_invalid_json_throws_ai_response_exception(): void
    {
        $service = $this->createService();

        $this->expectException(AiResponseException::class);

        $service->parseTranscriptionResponse('{not valid json}');
    }

    #[Test]
    public function parse_missing_transcript_throws_ai_response_exception(): void
    {
        $service = $this->createService();

        $this->expectException(AiResponseException::class);

        $service->parseTranscriptionResponse(json_encode([
            'language' => 'es',
            'duration_seconds' => 3.0,
        ]));
    }

    #[Test]
    public function parse_empty_response_throws_ai_response_exception(): void
    {
        $service = $this->createService();

        $this->expectException(AiResponseException::class);

        $service->parseTranscriptionResponse('');
    }
}
