<?php

namespace App\Commands\Admin;

use App\Repositories\Permission\GetPermissionRepository;
use App\Services\PermissionService;
use App\Exceptions\PermissionDeniedException;

class GetPermissionsCommand
{
    private GetPermissionRepository $leer;

    public function __construct(GetPermissionRepository $leer)
    {
        $this->leer = $leer;
    }

    public function execute()
    {
        $user = auth()->user();
        if (! $user) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $permissionService = app(PermissionService::class);
        $permissionService->ensure($user, 'admin.permission.view');

        $permissions = $this->leer->buscarTodos();

        return $permissions->map(function ($permission) {
            $categoryName = $permission->category ? $permission->category->full_name : 'Sin Categoría';
            $description = $permission->description;
            
            if ($permission->category) {
                // Prepend hierarchy to description as requested
                $description = $permission->category->full_name . ': ' . $description;
            }

            return [
                'id' => $permission->id,
                'slug' => $permission->slug,
                'name' => $permission->name,
                'category' => $categoryName,
                'description' => $description,
            ];
        });
    }
}
