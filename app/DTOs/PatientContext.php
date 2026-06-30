<?php

namespace App\DTOs;

readonly class PatientContext
{
    public function __construct(
        public ?int $edad,
        public ?string $sexo,
        public string $medicacion,
        public array $lastReports,
    ) {}
}
