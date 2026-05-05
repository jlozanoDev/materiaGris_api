<?php

namespace App\Repositories\Role;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleRepository
{
    public function listarTodos(): Collection
    {
        return Role::withCount('users')->get();
    }

    public function buscarPorId(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function guardar(array $data, ?Role $role = null): Role
    {
        if (!$role) {
            $role = new Role();
        }

        $role->name = $data['name'];
        if (isset($data['slug'])) {
            $role->slug = $data['slug'];
        } elseif (!$role->exists) {
            $role->slug = Str::slug($data['name']);
        }
        
        $role->description = $data['description'] ?? null;
        $role->is_system = $data['is_system'] ?? $role->is_system ?? false;
        
        $role->save();

        if (isset($data['permissions'])) {
            $syncData = [];
            foreach ($data['permissions'] as $perm) {
                // $perm should be ['id' => x, 'grant' => 1/-1/0]
                if (isset($perm['grant']) && $perm['grant'] != 0) {
                    $syncData[$perm['id']] = ['grant' => $perm['grant']];
                }
            }
            $role->permissions()->sync($syncData);
        }

        return $role;
    }

    public function eliminar(Role $role): bool
    {
        if ($role->is_system) {
            throw new \Exception('No se pueden eliminar roles del sistema.');
        }
        return $role->delete();
    }
}
