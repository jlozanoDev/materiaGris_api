<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // POST /auth/forgot
    // -------------------------------------------------------------------------

    public function test_forgot_retorna_200_con_email_existente(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'usuario@ejemplo.com']);

        $response = $this->postJson('/auth/forgot', ['email' => 'usuario@ejemplo.com']);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Si el email está registrado, recibirás un enlace para restablecer tu contraseña.']);

        Mail::assertSent(PasswordResetMail::class);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'usuario@ejemplo.com']);
    }

    public function test_forgot_retorna_200_con_email_no_existente_sin_filtrar_info(): void
    {
        Mail::fake();

        $response = $this->postJson('/auth/forgot', ['email' => 'noexiste@ejemplo.com']);

        // Respuesta silenciosa: mismo 200 para no filtrar si el email existe
        $response->assertStatus(200);

        Mail::assertNotSent(PasswordResetMail::class);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'noexiste@ejemplo.com']);
    }

    public function test_forgot_valida_formato_email(): void
    {
        $response = $this->postJson('/auth/forgot', ['email' => 'no-es-un-email']);

        $response->assertStatus(422);
    }

    public function test_forgot_requiere_email(): void
    {
        $response = $this->postJson('/auth/forgot', []);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // POST /auth/reset
    // -------------------------------------------------------------------------

    public function test_reset_cambia_password_con_token_valido(): void
    {
        $user = User::factory()->create(['email' => 'usuario@ejemplo.com']);

        $plainToken = 'token-valido-de-prueba-64-caracteres-xxxx-xxxx-xxxx-xxxx-xxxx';

        DB::table('password_reset_tokens')->insert([
            'email'      => 'usuario@ejemplo.com',
            'token'      => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/auth/reset', [
            'email'                 => 'usuario@ejemplo.com',
            'token'                 => $plainToken,
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.']);

        // El token debe ser eliminado tras su uso
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'usuario@ejemplo.com']);

        // La contraseña debe haberse actualizado
        $user->refresh();
        $this->assertTrue(Hash::check('NuevaPassword123', $user->password));
    }

    public function test_reset_falla_con_token_invalido(): void
    {
        User::factory()->create(['email' => 'usuario@ejemplo.com']);

        DB::table('password_reset_tokens')->insert([
            'email'      => 'usuario@ejemplo.com',
            'token'      => Hash::make('token-correcto'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/auth/reset', [
            'email'                 => 'usuario@ejemplo.com',
            'token'                 => 'token-incorrecto',
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_falla_con_token_expirado(): void
    {
        User::factory()->create(['email' => 'usuario@ejemplo.com']);

        DB::table('password_reset_tokens')->insert([
            'email'      => 'usuario@ejemplo.com',
            'token'      => Hash::make('token-expirado'),
            'created_at' => now()->subMinutes(61), // expirado (config: 60 min)
        ]);

        $response = $this->postJson('/auth/reset', [
            'email'                 => 'usuario@ejemplo.com',
            'token'                 => 'token-expirado',
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_valida_password_minimo_8_caracteres(): void
    {
        $response = $this->postJson('/auth/reset', [
            'email'                 => 'usuario@ejemplo.com',
            'token'                 => 'cualquier-token',
            'password'              => 'corto',
            'password_confirmation' => 'corto',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_valida_confirmacion_de_password(): void
    {
        $response = $this->postJson('/auth/reset', [
            'email'                 => 'usuario@ejemplo.com',
            'token'                 => 'cualquier-token',
            'password'              => 'Password123',
            'password_confirmation' => 'PasswordDistinta',
        ]);

        $response->assertStatus(422);
    }

    public function test_token_no_es_reutilizable(): void
    {
        $user = User::factory()->create(['email' => 'usuario@ejemplo.com']);

        $plainToken = 'token-uso-unico-64-caracteres-xxxx-xxxx-xxxx-xxxx-xxxx-xxxx-xxx';

        DB::table('password_reset_tokens')->insert([
            'email'      => 'usuario@ejemplo.com',
            'token'      => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $payload = [
            'email'                 => 'usuario@ejemplo.com',
            'token'                 => $plainToken,
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ];

        $this->postJson('/auth/reset', $payload)->assertStatus(200);

        // Segundo intento con el mismo token debe fallar
        $this->postJson('/auth/reset', $payload)->assertStatus(422);
    }
}
