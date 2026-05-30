<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Hash;
use DateTimeImmutable;

use Illuminate\Support\Str;

/**
 * Repositorio para operaciones de escritura sobre User
 */
class SaveUserRepository
{
    /**
     * Crear usuario. Se crea con contraseña aleatoria.
     * @param array $data
     * @return User
     */
    public function crear(array $data): User
    {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }

        $password = Str::random(40);
        $user = new User();
        $user->name = $data['name'] ?? '';
        $user->email = $data['email'];
        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }

    /**
     * Actualizar usuario con datos provistos.
     * @param User $user
     * @param array $data
     * @return User
     */
    public function actualizar(User $user, array $data): User
    {
        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email']) && $data['email'] !== $user->email) $user->email = $data['email'];
        if (array_key_exists('active', $data)) $user->active = (bool) $data['active'];
        $user->save();
        return $user;
    }

    /**
     * Eliminar usuario (soft delete).
     * @param User $user
     * @return void
     */
    public function eliminar(User $user): void
    {
        $user->delete();
    }
}
