<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Hash;
use DateTimeImmutable;

use Illuminate\Support\Str;
use App\Services\PasswordResetService;

/**
 * Repositorio para operaciones de escritura sobre User
 */
class SaveUserRepository
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Crear usuario. Se crea con contraseña aleatoria y se solicita email de restablecimiento.
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

        // Solicitar email de reseteo para que el usuario establezca su contraseña
        try {
            $this->passwordResetService->solicitarReseteo($user->email);
        } catch (\Throwable $e) {
            // No interrumpir la creación si falla el envío; registrar y continuar
            \Illuminate\Support\Facades\Log::error('[SaveUserRepository] error sending reset email: ' . $e->getMessage());
        }

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
