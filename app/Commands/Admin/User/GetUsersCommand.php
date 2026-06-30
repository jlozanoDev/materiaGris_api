<?php

namespace App\Commands\Admin\User;

use App\DTOs\UserDetail;
use App\Models\User;
use App\Repositories\User\GetUserRepository;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use App\Exceptions\PermissionDeniedException;

class GetUsersCommand
{
    private GetUserRepository $leer;
    private PermissionService $permissionService;

    public function __construct(GetUserRepository $leer, PermissionService $permissionService)
    {
        $this->leer = $leer;
        $this->permissionService = $permissionService;
    }

    /**
     * @return Collection<int, UserDetail>
     */
    public function execute(): Collection
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new PermissionDeniedException('Unauthorized');
        }

        $this->permissionService->ensure($actor, 'admin.user.view');

        $users = $this->leer->buscarTodos();

        return $users->map(fn ($user) => UserDetail::fromUser($user));
    }
}
