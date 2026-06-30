<?php

namespace App\DTOs;

readonly class SystemVariable
{
    public function __construct(
        public string $category,
        public string $key,
        public string $label,
        public string $description,
    ) {}
}
