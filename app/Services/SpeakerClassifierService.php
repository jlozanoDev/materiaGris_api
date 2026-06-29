<?php

namespace App\Services;

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
        $speakerTexts = $this->groupBySpeaker($segments);

        // Single speaker — assign "Médico"
        if (count($speakerTexts) <= 1) {
            return $this->mapSingleSpeaker($segments);
        }

        // Try heuristics first
        $classification = $this->classifyByHeuristics($speakerTexts);

        // Fallback to LLM if heuristics inconclusive
        if ($classification === null) {
            $classification = $this->classifyByLlm($segments);
        }

        return $this->applyClassification($segments, $classification);
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

        // Determine role: the speaker with higher doctor score is Médico
        $speakers = array_keys($scores);
        $diff = abs($scores[$speakers[0]] - $scores[$speakers[1]]);

        // Need clear difference to be confident
        if ($diff < 2) {
            return null; // inconclusive
        }

        $result = [];
        foreach ($scores as $speaker => $score) {
            $result[$speaker] = $score > 0 ? 'Médico' : 'Paciente';
        }

        return $result;
    }

    /**
     * Score a speaker: positive = doctor, negative = patient.
     */
    private function scoreSpeaker(string $text): int
    {
        $score = 0;

        foreach (self::DOCTOR_PATTERNS as $pattern) {
            $score += substr_count($text, $pattern);
        }

        foreach (self::PATIENT_PATTERNS as $pattern) {
            $score -= substr_count($text, $pattern);
        }

        // Questions heavily indicate doctor
        foreach (self::QUESTION_PATTERNS as $pattern) {
            $score += substr_count($text, $pattern);
        }

        return $score;
    }

    /**
     * @param array<int, array{speaker: string, text: string, start: float, end: float}> $segments
     * @return array<string, string[]>
     */
    private function groupBySpeaker(array $segments): array
    {
        $grouped = [];
        foreach ($segments as $segment) {
            $speaker = $segment['speaker'];
            $grouped[$speaker][] = $segment['text'];
        }

        return $grouped;
    }

    /**
     * @param array<int, array{speaker: string, text: string, start: float, end: float}> $segments
     * @return array<int, array{speaker: string, text: string, start: float, end: float}>
     */
    private function mapSingleSpeaker(array $segments): array
    {
        return array_map(function ($segment) {
            $segment['speaker'] = 'Médico';
            return $segment;
        }, $segments);
    }

    /**
     * @param array<int, array{speaker: string, text: string, start: float, end: float}> $segments
     * @return array<string, string>
     */
    private function classifyByLlm(array $segments): array
    {
        // Build a simple transcript for context
        $transcript = '';
        foreach ($segments as $seg) {
            $transcript .= $seg['speaker'] . ': ' . $seg['text'] . "\n";
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
            // Last resort: assign Speaker 1 = Médico
            $speakers = array_unique(array_column($segments, 'speaker'));
            $classification = [];
            foreach ($speakers as $i => $speaker) {
                $classification[$speaker] = $i === 0 ? 'Médico' : 'Paciente';
            }
        }

        return $classification;
    }

    /**
     * @param array<int, array{speaker: string, text: string, start: float, end: float}> $segments
     * @param array<string, string> $classification
     * @return array<int, array{speaker: string, text: string, start: float, end: float}>
     */
    private function applyClassification(array $segments, array $classification): array
    {
        return array_map(function ($segment) use ($classification) {
            if (isset($classification[$segment['speaker']])) {
                $segment['speaker'] = $classification[$segment['speaker']];
            }
            return $segment;
        }, $segments);
    }
}
