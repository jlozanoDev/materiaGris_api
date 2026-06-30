<?php

namespace App\DTOs;

readonly class Segment
{
    public function __construct(
        public string $speaker,
        public string $text,
        public float $start,
        public float $end,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            speaker: $data['speaker'] ?? '',
            text: $data['text'] ?? '',
            start: (float) ($data['start'] ?? 0.0),
            end: (float) ($data['end'] ?? 0.0),
        );
    }

    public function toArray(): array
    {
        return [
            'speaker' => $this->speaker,
            'text' => $this->text,
            'start' => $this->start,
            'end' => $this->end,
        ];
    }
}
