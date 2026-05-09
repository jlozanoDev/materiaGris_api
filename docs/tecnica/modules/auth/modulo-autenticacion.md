# Módulo de Autenticación — Documentación Técnica

## Rutas

| Método | URI | Middleware | Permiso | Action |
|--------|-----|-----------|---------|--------|
| POST | `/api/auth/login` | `throttle:5,1` | — | `LoginAction` |
| POST | `/api/auth/refresh` | — | — | `RefreshAction` |
| POST | `/api/auth/logout` | — | — | `LogoutAction` |
| GET | `/api/auth/me` | `auth.jwt` | — | `MeAction` |
| POST | `/api/auth/forgot` | `throttle:5,1` | — | `ForgotPasswordAction` |
| POST | `/api/auth/reset` | — | — | `ResetPasswordAction` |

## Actions

### `LoginAction`
- Valida request: `email` (required, email), `password` (required).
- Invoca `LoginCommand`.
- Responde con `{ access_token, token_type, expires_in, refresh_token }`.

### `RefreshAction`
- Valida refresh token del body.
- Invoca `RefreshCommand`.
- Genera nuevo par JWT + refresh token.

### `LogoutAction`
- Revoca refresh token en base de datos.
- Responde `200`.

### `MeAction`
- Protegido por `auth.jwt`.
- Obtiene usuario autenticado, sus roles y permisos efectivos vía `PermissionService`.
- Responde con `{ id, name, email, roles, permissions, permissions_version }`.

### `ForgotPasswordAction`
- Valida email.
- Invoca `PasswordResetService::sendResetLink()`.
- Envía email con `PasswordResetMail`.

### `ResetPasswordAction`
- Valida `{ email, token, password }`.
- Invoca `PasswordResetService::reset()`.
- Actualiza contraseña.

## Commands (Use Cases)

| Command | Descripción |
|---------|-------------|
| `LoginCommand` | Verifica credenciales, genera JWT + refresh token, persiste refresh |
| `RefreshCommand` | Valida refresh token, genera nuevo par, revoca el anterior |
| `LogoutCommand` | Revoca refresh token del usuario |
| `MeCommand` | Obtiene datos del usuario + roles + permisos efectivos |
| `ForgotPasswordCommand` | Genera token de reset, persiste, envía email |
| `ResetPasswordCommand` | Valida token, actualiza contraseña, elimina token |

## Repositories

| Repositorio | Métodos principales |
|-------------|-------------------|
| `GetRefreshTokenRepository` | `getByJti(string $jti): ?RefreshToken` |
| `SaveRefreshTokenRepository` | `save(RefreshToken $token): void`, `revoke(string $jti): void` |
| `PasswordResetRepository` | `createToken(string $email, string $token): void`, `validateToken(string $email, string $token): bool`, `deleteToken(string $email): void` |
| `GetUserRepository` | `getByEmail(string $email): ?User` |

## Models

| Modelo | Tabla | Notas |
|--------|-------|-------|
| `User` | `users` | SoftDeletes, HasFactory, Notifiable |
| `RefreshToken` | `jwt_refresh_tokens` | `$table = 'jwt_refresh_tokens'` |

## Services

### `JwtService`
- `generateToken(User $user): array` — genera JWT + refresh token.
- `validateToken(string $token): ?array` — valida JWT y retorna claims.
- `getUserFromToken(string $token): ?User` — obtiene usuario del JWT.

### `PasswordResetService`
- `sendResetLink(string $email): void` — genera token, persiste, envía email.
- `reset(array $credentials): void` — valida token y actualiza contraseña.

## Middleware

### `AuthenticateJwt`
- Extrae Bearer token del header `Authorization`.
- Valida JWT con `JwtService::validateToken()`.
- Establece `Auth::setUser()` si es válido.
- Responde `401` si el token es inválido o expiró.

## Flujo de Datos

### Login
```
POST /api/auth/login
  → LoginAction (valida email/password)
    → LoginCommand (verifica credenciales)
      → GetUserRepository::getByEmail()
      → JwtService::generateToken()
      → SaveRefreshTokenRepository::save()
    ← { access_token, refresh_token }
```

### Obtener perfil (autenticado)
```
GET /api/auth/me
  → AuthenticateJwt (valida JWT)
  → MeAction
    → MeCommand
      → PermissionService::getEffectivePermissions()
    ← { id, name, email, roles, permissions }
```

## Estado de Desarrollo

✅ Completo — Los 6 endpoints están implementados y funcionales.

## Pendientes (Roadmap)

| Pendiente | Prioridad |
|-----------|-----------|
| Tests de integración para refresh y logout | Media |
| Auditoría de intentos de login fallidos | Baja |
| Rotación automática de refresh tokens | Baja |
| Límite de sesiones concurrentes por usuario | Futura |
