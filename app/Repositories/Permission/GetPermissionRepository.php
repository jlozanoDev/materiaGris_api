<?php

namespace App\Repositories\Permission;

use App\Models\Permission;

class GetPermissionRepository
{
    public function buscarTodos()
    {
        return Permission::with('category.parent')->get();
    }

    public function buscarPorId(int $id): ?Permission
    {
        return Permission::find($id);
    }
}
