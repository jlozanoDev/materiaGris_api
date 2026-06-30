<?php

namespace Tests\Unit\Mail;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Tests\TestCase;

class PasswordResetMailTest extends TestCase
{
    public function test_envelope_has_correct_subject(): void
    {
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        $mail = new PasswordResetMail($user, 'https://example.com/reset?token=abc');

        $envelope = $mail->envelope();

        $this->assertEquals('Restablecer contraseña - Materiagris', $envelope->subject);
    }

    public function test_content_uses_correct_view(): void
    {
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        $mail = new PasswordResetMail($user, 'https://example.com/reset?token=abc');

        $content = $mail->content();

        $this->assertEquals('emails.password_reset', $content->view);
    }

}
