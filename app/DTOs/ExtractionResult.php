<?php

namespace App\DTOs;

use JsonSerializable;

readonly class ExtractionResult implements JsonSerializable
{
    public function __construct(
        public array $extractedData,
        public array $confidenceScores,
        public array $warnings,
        public int $processingTimeMs = 0,
    ) {}

    /**
     * @param array{
     *     extracted_data?: array,
     *     confidence_scores?: array,
     *     warnings?: array,
     *     processing_time_ms?: int,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            extractedData: $data['extracted_data'] ?? [],
            confidenceScores: $data['confidence_scores'] ?? [],
            warnings: $data['warnings'] ?? [],
            processingTimeMs: (int) ($data['processing_time_ms'] ?? 0),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'extracted_data' => $this->extractedData,
            'confidence_scores' => $this->confidenceScores,
            'warnings' => $this->warnings,
            'processing_time_ms' => $this->processingTimeMs,
        ];
    }
}
