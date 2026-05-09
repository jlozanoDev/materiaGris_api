# Módulo de Autenticación — API

## Propósito de Negocio
Permitir el acceso seguro al sistema mediante credenciales, manteniendo la sesión activa con JWT y refresh tokens, y habilitar la recuperación de contraseña por email.

## Actores
- Médico, Administrador, Recepcionista (todos los usuarios del sistema).

## Funcionalidades

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/auth/login` | POST | Inicio de sesión con email y contraseña |
| `/api/auth/refresh` | POST | Renovación del JWT mediante refresh token |
| `/api/auth/logout` | POST | Cierre de sesión y revocación del refresh token |
| `/api/auth/me` | GET | Obtener perfil, roles y permisos del usuario autenticado |
| `/api/auth/forgot` | POST | Solicitar restablecimiento de contraseña |
| `/api/auth/reset` | POST | Restablecer contraseña con token recibido por email |

## Criterios de Aceptación
- El login debe rechazar credenciales inválidas con error 401.
- El refresh debe entregar un nuevo JWT sin requerir credenciales.
- El logout debe invalidar el refresh token.
- `/api/auth/me` debe exponer roles y permisos efectivos del usuario.
- El restablecimiento de contraseña debe enviar un email con un token de un solo uso.

## Reglas de Negocio
- No existe auto-registro; los usuarios son creados por administradores.
- El login está limitado a 5 intentos por minuto (middleware `throttle:5,1`).
- El JWT tiene expiración configurable (ver `config/jwt.php`).
- El refresh token puede ser revocado individualmente.
- Contraseñas almacenadas con hash bcrypt (hash de Laravel).

## Estructura de Datos

**POST /api/auth/login — Request:**
```json
{ "email": "medico@example.com", "password": "secret" }
```

**POST /api/auth/login — Response (200):**
```json
{
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "eyJ..."
}
```

**GET /api/auth/me — Response (200):**
```json
{
  "id": 1,
  "name": "Dr. García",
  "email": "medico@example.com",
  "roles": ["admin"],
  "permissions": { "patient.view": true, "patient.create": true },
  "permissions_version": "2026-04-12T08:00:00Z"
}
```

## Dependencias
- Servicio de correo electrónico (Mailhog en dev, SMTP en prod).
- `jwt_refresh_tokens` — tabla para persistencia de refresh tokens.

## Estado de Desarrollo
✅ Implementado — Completo.

## Pendientes (Roadmap)
- OAuth2 con proveedores externos (Google, Microsoft).
- Autenticación de dos factores (2FA).
- Historial de inicios de sesión para auditoría.
- Notificación por email en inicio de sesión desde nuevo dispositivo.
