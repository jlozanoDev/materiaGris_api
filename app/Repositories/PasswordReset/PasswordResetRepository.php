<?php

namespace App\Repositories\PasswordReset;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use DateTimeImmutable;

class PasswordResetRepository
{
    private string $table;
    private int $expireMinutes;

    public function __construct()
    {
        $this->table = config('auth.passwords.users.table', 'password_reset_tokens');
        $this->expireMinutes = config('auth.passwords.users.expire', 60);
    }

    /**
     * Crear o reemplazar el token de reseteo para un email dado.
     * El token se almacena hasheado para prevenir extracción desde la BD.
     */
    public function crear(string $email, string $plainToken): void
    {
        DB::table($this->table)->updateOrInsert(
            ['email' => $email],
            [
                'token'      => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Buscar y validar un token por email. Devuelve true si el token
     * existe, coincide y no ha expirado. false en cualquier otro caso.
     */
    public function esValido(string $email, string $plainToken): bool
    {
        $record = DB::table($this->table)->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        $createdAt = new DateTimeImmutable($record->created_at);
        $expiresAt = $createdAt->modify("+{$this->expireMinutes} minutes");

        if (new DateTimeImmutable() > $expiresAt) {
            return false;
        }

        return Hash::check($plainToken, $record->token);
    }

    /**
     * Eliminar el token de un email (tras uso o expiración).
     */
    public function eliminar(string $email): void
    {
        DB::table($this->table)->where('email', $email)->delete();
    }

    /**
     * Limpiar tokens expirados de la tabla.
     */
    public function eliminarExpirados(): void
    {
        $expiracion = now()->subMinutes($this->expireMinutes);

        DB::table($this->table)->where('created_at', '<', $expiracion)->delete();
    }
}
