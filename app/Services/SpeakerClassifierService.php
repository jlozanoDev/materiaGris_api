<?php

namespace App\Services;

use App\DTOs\Segment;

class SpeakerClassifierService
{
    // Heuristic patterns for Spanish medical conversations
    private const PATIENT_PATTERNS = [
        'me duele', 'tengo', 'siento', 'me recetaron', 'me dijo', 'me dijeron',
        'estoy tomando', 'tomo', 'me operaron', 'padezco', 'sufro',
        'no puedo', 'me cuesta', 'me molesta',
    ];

    private const DOCTOR_PATTERNS = [
        'diagnóstico', 'tratamiento', 'prescribo', 'le receto', 'le voy a recetar',
        'explíqueme', 'cuénteme', 'desde cuándo', 'hace cuánto',
        'antecedentes', 'alergias', 'cirugías previas',
        'vamos a hacer', 'le voy a pedir', 'análisis', 'estudios',
        'presión', 'frecuencia', 'peso', 'talla',
    ];

    private const QUESTION_PATTERNS = ['¿', '?', 'cómo', 'cuándo', 'dónde', 'qué', 'cuál', 'cuánto', 'por qué'];

    public function __construct(
        private readonly array $llmConfig,
    ) {}

    /**
     * Classify segments from "Speaker N" to "Médico" / "Paciente".
     *
     * @param array<int, array{speaker: string, text: string, start: float, end: float}> $segments
     * @return array<int, array{speaker: string, text: string, start: float, end: float}>
     */
    public function classify(array $segments): array
    {
        $segments = array_map(fn ($s) => Segment::fromArray($s), $segments);

        $speakerTexts = $this->groupBySpeaker($segments);

        if (count($speakerTexts) <= 1) {
            $segments = $this->classifySingleSpeaker($segments, $speakerTexts);
        } else {
            $classification = $this->classifyByHeuristics($speakerTexts);

            if ($classification === null) {
                $classification = $this->classifyByLlm($segments);
            }

            $segments = $this->applyClassification($segments, $classification);
        }

        return array_map(fn (Segment $s) => $s->toArray(), $segments);
    }

    /**
     * @param array<string, string[]> $speakerTexts
     * @return array<string, string>|null  speaker → role, or null if inconclusive
     */
    private function classifyByHeuristics(array $speakerTexts): ?array
    {
        $scores = [];
        foreach ($speakerTexts as $speaker => $texts) {
            $fullText = mb_strtolower(implode(' ', $texts));
            $scores[$speaker] = $this->scoreSpeaker($fullText);
        }

        $speakers = array_keys($scores);
        $diff = abs($scores[$speakers[0]] - $scores[$speakers[1]]);

        if ($diff < 2) {
            return null;
        }

        $result = [];
        $maxSpeaker = array_search(max($scores), $scores, true);
        foreach ($scores as $speaker => $score) {
            $result[$speaker] = $speaker === $maxSpeaker ? 'Médico' : 'Paciente';
        }

        return $result;
    }

    private function scoreSpeaker(string $text): int
    {
        $score = 0;

        foreach (self::DOCTOR_PATTERNS as $pattern) {
            $score += substr_count($text, $pattern);
        }

        foreach (self::PATIENT_PATTERNS as $pattern) {
            $score -= substr_count($text, $pattern);
        }

        foreach (self::QUESTION_PATTERNS as $pattern) {
            $score += substr_count($text, $pattern);
        }

        return $score;
    }

    /**
     * @param Segment[] $segments
     * @return array<string, string[]>
     */
    private function groupBySpeaker(array $segments): array
    {
        $grouped = [];
        foreach ($segments as $segment) {
            $grouped[$segment->speaker][] = $segment->text;
        }

        return $grouped;
    }

    /**
     * @param Segment[] $segments
     * @param array<string, string[]> $speakerTexts
     * @return Segment[]
     */
    private function classifySingleSpeaker(array $segments, array $speakerTexts): array
    {
        $speaker = array_key_first($speakerTexts);
        $fullText = mb_strtolower(implode(' ', $speakerTexts[$speaker]));
        $score = $this->scoreSpeaker($fullText);

        $role = $score > 0 ? 'Médico' : 'Paciente';

        return array_map(function (Segment $segment) use ($role) {
            return new Segment(
                speaker: $role,
                text: $segment->text,
                start: $segment->start,
                end: $segment->end,
            );
        }, $segments);
    }

    /**
     * @param Segment[] $segments
     * @return array<string, string>
     */
    private function classifyByLlm(array $segments): array
    {
        $transcript = '';
        foreach ($segments as $seg) {
            $transcript .= $seg->speaker . ': ' . $seg->text . "\n";
        }

        $payload = [
            'model' => $this->llmConfig['model'] ?? 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente que analiza conversaciones médicas.'],
                ['role' => 'user', 'content' => "En esta consulta médica, identifica quién es el médico y quién el paciente basándote en quién hace preguntas y quién describe síntomas.\n\nConversación:\n{$transcript}\n\nResponde SOLO con JSON: {\"Speaker 1\": \"Médico|Paciente\", \"Speaker 2\": \"Médico|Paciente\"}"],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1,
        ];

        $response = \Illuminate\Support\Facades\Http::withToken($this->llmConfig['api_key'] ?? '')
            ->timeout(15)
            ->post(rtrim($this->llmConfig['base_url'] ?? 'https://api.openai.com/v1', '/') . '/chat/completions', $payload);

        $body = $response->body();
        $data = json_decode($body, true);
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $classification = json_decode($content, true);

        if (!is_array($classification) || empty($classification)) {
            $speakers = [];
            foreach ($segments as $segment) {
                $speakers[$segment->speaker] = true;
            }
            $speakers = array_keys($speakers);
            $classification = [];
            foreach ($speakers as $i => $speaker) {
                $classification[$speaker] = $i === 0 ? 'Médico' : 'Paciente';
            }
        }

        return $classification;
    }

    /**
     * @param Segment[] $segments
     * @param array<string, string> $classification
     * @return Segment[]
     */
    private function applyClassification(array $segments, array $classification): array
    {
        return array_map(function (Segment $segment) use ($classification) {
            if (isset($classification[$segment->speaker])) {
                return new Segment(
                    speaker: $classification[$segment->speaker],
                    text: $segment->text,
                    start: $segment->start,
                    end: $segment->end,
                );
            }
            return $segment;
        }, $segments);
    }
}
