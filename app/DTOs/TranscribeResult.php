<?php

namespace App\DTOs;

readonly class TranscribeResult
{
    public function __construct(
        public string $transcript,
        public array $segments,
        public string $language,
        public float $durationSeconds,
        public int $processingTimeMs = 0,
    ) {}

    /**
     * Create a new TranscribeResult from an array of data.
     *
     * @param array{
     *     transcript?: string,
     *     segments?: array<int, array{speaker: string, text: string, start: float, end: float}>,
     *     language?: string,
     *     duration_seconds?: float,
     *     processing_time_ms?: int,
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transcript: $data['transcript'] ?? '',
            segments: $data['segments'] ?? [],
            language: $data['language'] ?? 'es',
            durationSeconds: (float) ($data['duration_seconds'] ?? 0.0),
            processingTimeMs: (int) ($data['processing_time_ms'] ?? 0),
        );
    }
}
