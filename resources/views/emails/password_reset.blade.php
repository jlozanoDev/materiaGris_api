<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Restablecer contraseña - Materiagris</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #513AD7; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0; }
        .body { padding: 32px 40px; color: #374151; }
        .body p { line-height: 1.6; margin: 0 0 16px; }
        .btn-wrap { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background: #513AD7; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: bold; font-size: 15px; }
        .footer { padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .link-fallback { word-break: break-all; color: #513AD7; font-size: 13px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Materiagris</h1>
        </div>
        <div class="body">
            <p>Hola, <strong>{{ $user->name }}</strong>.</p>
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Materiagris.</p>
            <p>Haz clic en el botón de abajo para crear una nueva contraseña. Este enlace expirará en <strong>60&nbsp;minutos</strong>.</p>

            <div class="btn-wrap">
                <a href="{{ $enlace }}" class="btn">Restablecer contraseña</a>
            </div>

            <p>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo. Tu cuenta seguirá protegida.</p>

            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <p class="link-fallback">{{ $enlace }}</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Materiagris. Todos los derechos reservados.</p>
            <p>Este correo fue enviado de forma automática, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
