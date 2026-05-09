# Flujo de Autenticación — API

## Login (Happy Path)

1. El frontend envía `POST /api/auth/login` con `{ email, password }`.
2. La API valida credenciales contra la base de datos.
3. Si son válidas, genera un JWT (access token) y un refresh token.
4. Responde con `200` y `{ access_token, token_type, expires_in, refresh_token }`.
5. El frontend almacena el JWT y lo envía como `Authorization: Bearer <token>` en requests subsecuentes.

## Login (Error — Credenciales Inválidas)

1. El frontend envía `POST /api/auth/login` con credenciales incorrectas.
2. La API responde con `401 Unauthorized` y mensaje de error.
3. Si se exceden 5 intentos en 1 minuto, se rechaza con `429 Too Many Requests`.

## Refresh Token

1. Cuando el JWT expira, el frontend envía `POST /api/auth/refresh` con el refresh token.
2. La API valida el refresh token contra la tabla `jwt_refresh_tokens`.
3. Si es válido y no ha sido revocado, genera un nuevo par (JWT + refresh token).
4. Responde con `200` y el nuevo par de tokens.

## Logout

1. El frontend envía `POST /api/auth/logout` con el refresh token.
2. La API revoca el refresh token en base de datos.
3. Responde con `200`.

## Recuperación de Contraseña

1. El usuario solicita restablecimiento: `POST /api/auth/forgot` con `{ email }`.
2. La API genera un token de restablecimiento y envía un email al usuario.
3. El usuario recibe el email y hace clic en el enlace.
4. El frontend envía `POST /api/auth/reset` con `{ email, token, password }`.
5. La API valida el token, actualiza la contraseña y responde con `200`.

## Flujo de Autorización (Request Autenticado)

1. El frontend envía un request con `Authorization: Bearer <jwt>`.
2. Middleware `AuthenticateJwt` valida el JWT (firma, expiración).
3. Si la ruta requiere permisos, middleware `RequirePermissions` verifica que el usuario tenga el permiso necesario.
4. Si no tiene permiso, registra un evento `policy.denied` en auditoría y responde `401`.
5. Si todo es válido, el request pasa al Action correspondiente.
