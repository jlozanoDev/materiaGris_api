<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## Cómo ejecutar (desarrollo)

Se recomienda usar Docker Compose desde la raíz del repositorio.

1. Levantar servicios:

	docker-compose up --build

2. Ejecutar migraciones/seeders dentro del contenedor de la app:

	docker compose exec app php artisan migrate --seed

3. Para ejecutar tests de PHPUnit desde el contenedor:

	docker compose exec app ./vendor/bin/phpunit

Nota sobre tests y base de datos:

- El entorno de testing está configurado para usar SQLite en memoria (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) en `phpunit.xml`. Esto evita que la suite de tests modifique la base de datos MySQL de desarrollo.
- Si ejecutas pruebas manuales contra la base MySQL, ten cuidado con tests que usan `RefreshDatabase` (recrean esquemas). Para CI, hay un workflow que fuerza SQLite (ver `.github/workflows/phpunit.yml`).

Si trabajas fuera de contenedores, asegúrate de tener PHP, Composer y las extensiones necesarias, luego:

```
composer install
php artisan migrate --seed
php artisan serve
```


Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

---

NOTE: This repository's `backend` has been prepared to operate as an API-only service.
- Web routes (`routes/web.php`) are disabled and return 404 by default.
- Define API endpoints in `routes/api.php`.

Si necesitas que habilite un middleware de autenticación (Sanctum/Passport) o que cree controladores CRUD, dímelo y lo configuro.

### JWT Authentication (implemented)

Este backend incluye una implementación básica de autenticación JWT (access + refresh rotativo) usando `lcobucci/jwt`.

- Variables de entorno importantes (añádelas a `.env` o copia desde `.env.example`):
	- `JWT_SECRET` — clave secreta para firmar HS256. Usa un valor largo y aleatorio.
	- `JWT_ALGO` — algoritmo de firma (por defecto `HS256`).
	- `JWT_TTL` — tiempo de vida del access token en minutos (por defecto `15`).
	- `JWT_REFRESH_TTL` — días de vida del refresh token (por defecto `14`).
	- `JWT_REFRESH_COOKIE` — nombre de la cookie para el refresh token (por defecto `refresh_token`).
 	- `JWT_COOKIE_DOMAIN` — dominio para la cookie (por defecto `materiagris.local`).

Rutas expuestas: `POST /api/auth/login`, `POST /api/auth/refresh`, `POST /api/auth/logout`, `GET /api/auth/me` (protegida). El refresh token se envía en una cookie `HttpOnly; Secure; SameSite=None`.

Ejecuta las migraciones para crear la tabla de refresh tokens:

```bash
docker exec -it materiagris_app php artisan migrate
```

### Recuperación de contraseña (Forgot Password)

Flujo completo implementado: `POST /api/auth/forgot` y `POST /api/auth/reset`.

**Variables de entorno necesarias** (añadir a `.env`):

| Variable | Descripción | Ejemplo |
|---|---|---|
| `FRONTEND_URL` | URL base del frontend; se usa para construir el enlace del email | `http://localhost:5173` |
| `MAIL_MAILER` | Driver de correo | `log` (desarrollo) / `smtp` (producción) |
| `MAIL_HOST` | Servidor SMTP o host de Mailhog | `127.0.0.1` |
| `MAIL_PORT` | Puerto SMTP | `2525` (Mailhog) / `587` (SMTP) |
| `MAIL_FROM_ADDRESS` | Dirección remitente del email de reseteo | `noreply@materiagris.local` |
| `MAIL_FROM_NAME` | Nombre remitente | `Materiagris` |

**Probar en desarrollo con driver `log`** (sin servidor de correo):

```ini
# .env
MAIL_MAILER=log
FRONTEND_URL=http://localhost:5173
```

El enlace de reseteo aparecerá en `storage/logs/laravel.log`. Búscalo con:

```bash
docker compose exec app grep "reset-password" storage/logs/laravel.log
```

**Probar con Mailhog** (servicio incluido en `docker-compose.yml`):

```ini
# .env
MAIL_MAILER=mailhog
MAIL_HOST=mailhog
MAIL_PORT=1025
FRONTEND_URL=http://localhost:5173
```

Accede a la interfaz web de Mailhog en `http://localhost:8025` para ver los emails recibidos.

**Flujo E2E manual**:

1. `POST /api/auth/forgot` con `{ "email": "usuario@ejemplo.com" }`
2. Copiar el enlace del log o de Mailhog: `http://localhost:5173/reset-password?token=…&email=…`
3. Abrir el enlace en el navegador → formulario de nueva contraseña
4. `POST /api/auth/reset` con `{ email, token, password, password_confirmation }`
5. Verificar login con la nueva contraseña en `POST /api/auth/login`

**Tabla utilizada**: `password_reset_tokens` (creada por la migración inicial, no requiere migración adicional). Expiración: 60 minutos (configurable en `config/auth.php` → `passwords.users.expire`).

**Tests**:

```bash
docker compose exec app ./vendor/bin/phpunit --filter PasswordReset
```


## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
