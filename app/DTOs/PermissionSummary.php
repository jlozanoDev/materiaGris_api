<?php

namespace App\DTOs;

use JsonSerializable;

readonly class PermissionSummary implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $category,
        public string $description,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
        ];
    }
}
