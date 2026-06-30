<?php

namespace App\DTOs;

use App\Models\User;
use App\Services\PermissionService;
use JsonSerializable;

readonly class UserDetail implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $active,
        public array $roles,
        public array $userPermissions,
        public array $effectivePermissions = [],
    ) {}

    public static function fromUser(User $user, ?PermissionService $permissionService = null): self
    {
        $roles = $user->roles->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'is_system' => (bool) $role->is_system,
        ])->toArray();

        $userPermissions = $user->userPermissions->map(fn ($permission) => [
            'permission_id' => $permission->id,
            'slug' => $permission->slug,
            'grant' => (int) $permission->pivot->grant,
            'origin' => $permission->pivot->origin,
            'origin_id' => $permission->pivot->origin_id,
        ])->toArray();

        $effectivePermissions = $permissionService
            ? $permissionService->getEffectivePermissions($user)
            : [];

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            active: (bool) $user->active,
            roles: $roles,
            userPermissions: $userPermissions,
            effectivePermissions: $effectivePermissions,
        );
    }

    public function jsonSerialize(): array
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'roles' => $this->roles,
            'user_permissions' => $this->userPermissions,
        ];

        if (! empty($this->effectivePermissions)) {
            $result['effective_permissions'] = $this->effectivePermissions;
        }

        return $result;
    }
}
