<?php

namespace App\Repositories\User;

use App\Models\User;

class GetUserRepository
{
    public function buscarPorEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function buscarPorId(int $id): ?User
    {
        return User::with(['roles.permissions', 'userPermissions'])->find($id);
    }

    public function buscarTodos()
    {
        return User::with(['roles', 'userPermissions'])->get();
    }
}
