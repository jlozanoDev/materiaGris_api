# Materiagris API (Laravel)

API backend desarrollado con Laravel. Contiene la lógica, migraciones, seeders, autenticación JWT y los endpoints consumidos por la SPA frontend.

## Servicios incluidos (docker-compose)
- `app` — PHP 8.2 FPM (contenedor de la aplicación)
- `nginx` — Servidor web, expone `80` en el host (acceso a la API: http://localhost)
- `db` — MySQL 8.0 (puerto host: `33060` → contenedor `3306`)
- `redis` — Redis (cola/cache)
- `mailhog` — Mailhog (interfaz web: http://localhost:8025, SMTP: 1025)

## Requisitos
- Docker y Docker Compose
- Archivo `.env` (copia `.env.example` y ajusta valores)

## Comandos habituales

Levantar servicios (desde la raíz del repo):

```bash
docker-compose up -d --build
```

Ver logs del contenedor de la app:

```bash
docker logs --tail 200 materiagris_app
# o
docker-compose logs --tail 200 --follow app
```

Instalar dependencias dentro del contenedor (si falta `vendor`):

```bash
docker exec -it materiagris_app bash -lc "composer install --no-interaction --prefer-dist --optimize-autoloader"
```

Ejecutar migraciones y seeders:

```bash
docker exec -it materiagris_app bash -lc "php artisan migrate --force"
docker exec -it materiagris_app bash -lc "php artisan db:seed --force"
```

Ejecutar tests (PHPUnit):

```bash
docker exec -it materiagris_app bash -lc "./vendor/bin/phpunit"
```

Acceso a MySQL desde el host: `127.0.0.1:33060` (usuario/clave según `.env`).

## Variables de entorno importantes
- Copia `.env.example` → `.env` y ajusta valores.
- Variables JWT: `JWT_SECRET`, `JWT_ALGO`, `JWT_TTL`, `JWT_REFRESH_TTL`, `JWT_REFRESH_COOKIE`, `JWT_COOKIE_DOMAIN`.
- Mail: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- `FRONTEND_URL` — URL pública del frontend para enlaces en emails.

Para desarrollo con Mailhog (ya incluido):

```ini
MAIL_MAILER=mailhog
MAIL_HOST=mailhog
MAIL_PORT=1025
```

## Endpoints principales
- `POST /api/auth/login` — login
- `POST /api/auth/refresh` — refresh token
- `POST /api/auth/logout` — logout
- `GET /api/auth/me` — datos del usuario (protegida)
- `POST /api/auth/forgot` — solicitar reset de contraseña
- `POST /api/auth/reset` — confirmar nuevo password

## Notas
- La API está pensada para ser consumida por la SPA frontend (`MateriaGris_front`).
- Si quieres que añada scripts `Makefile` o tareas de `docker-compose` específicas, lo preparo.

---

Archivo actualizado con instrucciones de uso y comandos básicos.
