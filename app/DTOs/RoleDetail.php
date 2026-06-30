<?php

namespace App\DTOs;

use JsonSerializable;

readonly class RoleDetail implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $description,
        public bool $isSystem,
        public array $permissions,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_system' => $this->isSystem,
            'permissions' => $this->permissions,
        ];
    }
}
