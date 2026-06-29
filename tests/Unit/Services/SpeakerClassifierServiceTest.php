<?php

namespace Tests\Unit\Services;

use App\Services\SpeakerClassifierService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SpeakerClassifierServiceTest extends TestCase
{
    private function createService(): SpeakerClassifierService
    {
        return new SpeakerClassifierService(config('llm'));
    }

    private function segment(string $speaker, string $text, float $start = 0.0, float $end = 3.0): array
    {
        return [
            'speaker' => $speaker,
            'text' => $text,
            'start' => $start,
            'end' => $end,
        ];
    }

    // ─── TEST 1: DOCTOR FIRST SPEAKER ──────────────────────────

    #[Test]
    public function test_classify_with_doctor_first_speaker(): void
    {
        $service = $this->createService();
        $segments = [
            $this->segment('Speaker 1', '¿Cómo se siente?', 0.0, 3.0),
            $this->segment('Speaker 2', 'Me duele la cabeza', 3.5, 6.0),
            $this->segment('Speaker 1', '¿Ha tenido fiebre?', 6.5, 9.0),
            $this->segment('Speaker 2', 'Tengo mucho dolor', 9.5, 12.0),
        ];

        $result = $service->classify($segments);

        $this->assertCount(4, $result);
        // Speaker 1 asks questions → Médico
        $this->assertEquals('Médico', $result[0]['speaker']);
        $this->assertEquals('Médico', $result[2]['speaker']);
        // Speaker 2 describes symptoms → Paciente
        $this->assertEquals('Paciente', $result[1]['speaker']);
        $this->assertEquals('Paciente', $result[3]['speaker']);
    }

    // ─── TEST 2: PATIENT FIRST SPEAKER ─────────────────────────

    #[Test]
    public function test_classify_with_patient_first_speaker(): void
    {
        $service = $this->createService();
        $segments = [
            $this->segment('Speaker 1', 'Me duele la cabeza', 0.0, 3.0),
            $this->segment('Speaker 2', '¿Cómo se siente?', 3.5, 6.0),
            $this->segment('Speaker 1', 'Tengo fiebre', 6.5, 9.0),
            $this->segment('Speaker 2', '¿Desde cuándo?', 9.5, 12.0),
        ];

        $result = $service->classify($segments);

        $this->assertCount(4, $result);
        // Speaker 1 describes symptoms → Paciente
        $this->assertEquals('Paciente', $result[0]['speaker']);
        $this->assertEquals('Paciente', $result[2]['speaker']);
        // Speaker 2 asks questions → Médico
        $this->assertEquals('Médico', $result[1]['speaker']);
        $this->assertEquals('Médico', $result[3]['speaker']);
    }

    // ─── TEST 3: SINGLE SPEAKER ────────────────────────────────

    #[Test]
    public function test_classify_single_speaker_returns_medico(): void
    {
        $service = $this->createService();
        $segments = [
            $this->segment('Speaker 1', 'Buenos días, ¿en qué puedo ayudarle?', 0.0, 3.0),
            $this->segment('Speaker 1', 'Vamos a revisar sus síntomas.', 3.5, 6.0),
        ];

        $result = $service->classify($segments);

        $this->assertCount(2, $result);
        $this->assertEquals('Médico', $result[0]['speaker']);
        $this->assertEquals('Médico', $result[1]['speaker']);
    }

    // ─── TEST 4: AMBIGUOUS → LLM FALLBACK ──────────────────────

    #[Test]
    public function test_classify_ambiguous_falls_back_to_llm(): void
    {
        $llmBaseUrl = rtrim(config('llm.base_url', 'https://api.openai.com/v1'), '/');
        $expectedUrl = $llmBaseUrl . '/chat/completions';

        // Fake the LLM call using the actual configured URL
        Http::fake([
            $expectedUrl => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"Speaker 1": "Médico", "Speaker 2": "Paciente"}',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = $this->createService();

        // Ambiguous texts: neither speaker uses doctor/patient/question patterns clearly
        $segments = [
            $this->segment('Speaker 1', 'El cielo es azul.', 0.0, 3.0),
            $this->segment('Speaker 2', 'Mañana será otro día.', 3.5, 6.0),
            $this->segment('Speaker 1', 'La computadora funciona bien.', 6.5, 9.0),
            $this->segment('Speaker 2', 'El café está caliente.', 9.5, 12.0),
        ];

        $result = $service->classify($segments);

        $this->assertCount(4, $result);
        // LLM fallback used: Speaker 1 = Médico, Speaker 2 = Paciente
        $this->assertEquals('Médico', $result[0]['speaker']);
        $this->assertEquals('Paciente', $result[1]['speaker']);
        $this->assertEquals('Médico', $result[2]['speaker']);
        $this->assertEquals('Paciente', $result[3]['speaker']);

        // Verify that the fake HTTP call was actually made (LLM fallback was triggered)
        Http::assertSent(function ($request) use ($expectedUrl) {
            return $request->url() === $expectedUrl
                && $request->method() === 'POST';
        });
    }

    // ─── TEST 5: CLEARLY DIFFERENT SPEAKERS ─────────────────────

    #[Test]
    public function test_classify_clearly_different_speakers(): void
    {
        $service = $this->createService();
        $segments = [
            $this->segment('Speaker 1', '¿Cómo se siente? ¿Desde cuándo tiene ese dolor?', 0.0, 4.0),
            $this->segment('Speaker 2', 'Me duele la cabeza y tengo fiebre.', 4.5, 8.0),
        ];

        $result = $service->classify($segments);

        $this->assertCount(2, $result);
        // Speaker 1: only questions → Médico
        $this->assertEquals('Médico', $result[0]['speaker']);
        // Speaker 2: only "me duele" and "tengo" → Paciente
        $this->assertEquals('Paciente', $result[1]['speaker']);
    }
}
