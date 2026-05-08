# Materiagris API (Laravel 12)

API backend desarrollado con **Laravel 12**. Contiene la lógica, migraciones, seeders, autenticación JWT y los endpoints consumidos por la SPA frontend.

## Servicios incluidos (docker-compose)
- `app` — PHP 8.2 FPM (contenedor de la aplicación)
- `nginx` — Servidor web, expone `80` en el host (acceso a la API: http://localhost)
- `db` — MySQL 8.0 (puerto host: `33060` → contenedor `3306`) — *opcional, por defecto SQLite*
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

Instalar dependencias y configurar proyecto (primer uso):

```bash
docker exec -it materiagris_app bash -lc "composer setup"
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
docker exec -it materiagris_app bash -lc "composer test"
```

Modo desarrollo (servidores con hot-reload):

```bash
docker exec -it materiagris_app bash -lc "composer dev"
```

Acceso a MySQL desde el host: `127.0.0.1:33060` (usuario/clave según `.env`).

## Variables de entorno importantes
- Copia `.env.example` → `.env` y ajusta valores.
- Base de datos: por defecto usa **SQLite** (`DB_CONNECTION=sqlite`). Para MySQL, descomenta las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` en `.env`.
- Variables JWT: `JWT_SECRET`, `JWT_ALGO`, `JWT_TTL`, `JWT_REFRESH_TTL`, `JWT_REFRESH_COOKIE`, `JWT_COOKIE_DOMAIN`.
- CORS: `CORS_ALLOWED_ORIGINS` (orígenes separados por coma). Por defecto incluye `http://localhost:5173`, `http://localhost:8080`, `http://materiagris.local`.
- `FRONTEND_URL` — URL pública del frontend para enlaces en emails.
- Mail: por defecto usa `MAIL_MAILER=log`. Para desarrollo con Mailhog:

```ini
MAIL_MAILER=smtp
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
- JWT se implementa con `lcobucci/jwt` (sin paquete externo de JWT).
- Los scripts de Composer (`setup`, `dev`, `test`) automatizan tareas comunes.
