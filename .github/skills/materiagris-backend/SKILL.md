name: materiagris-backend
description: "Flujo de trabajo del backend Laravel para Materiagris. Úsalo al trabajar con rutas, controladores, modelos, migraciones, Eloquent, autenticación, configuración, vistas Blade, comandos artisan, lógica PHP o assets Vite del backend bajo backend/resources."
argument-hint: "Tarea de backend en Laravel, PHP, rutas, modelos o migraciones"
user-invocable: true
---

# Backend de Materiagris

Usa esta skill para trabajo del lado servidor dentro de la aplicación Laravel.

## Cuándo Usarla

- La tarea toca `backend/routes/`.
- La tarea toca `backend/routes/` o `backend/routes/api.php` (API REST/JSON).
- La tarea toca controladores, modelos, providers, migraciones o seeders.
- La tarea añade o cambia validación, lógica de negocio, autenticación o persistencia.
- La tarea involucra vistas Blade o assets gestionados por backend en `backend/resources/`.
- La tarea requiere `artisan`, Composer o comandos de test de Laravel.

## Archivos y Áreas Clave

- `backend/routes/web.php`
- `backend/app/Http/Controllers/`
- `backend/app/Models/`
- `backend/database/migrations/`
- `backend/config/`
- `backend/resources/`
- `backend/composer.json`
- `backend/package.json`
- JWT y autenticación: `backend/app/Services/JwtService.php` y la migración `backend/database/migrations/2026_03_28_000000_create_jwt_refresh_tokens_table.php`.

## Notas Actuales del Backend

- El proyecto usa Laravel 12 sobre PHP 8.2.
- Los scripts de Composer incluyen `setup`, `dev` y `test`.
- El tooling frontend del backend usa Vite más Tailwind a través de `backend/vite.config.js`.
- La ruta web visible actualmente devuelve la vista por defecto `welcome`.
 - El backend está pensado como una API REST que devuelve JSON; la autenticación principal se gestiona vía JWT y refresh tokens.

## Regla de imports

- **Importar clases:** Evita usar nombres de clase totalmente cualificados como `\App\...`. En su lugar, añade declaraciones `use` al inicio del archivo y referencia las clases por su nombre (por ejemplo `AuthController::class`). Esto mejora la legibilidad y mantiene un estilo consistente en el código.

## Procedimiento

1. Inspecciona primero el archivo de rutas relevante.
2. Sigue el recorrido de la petición por controlador, modelo, migración y configuración según sea necesario.
3. Si la tarea incluye UI dentro de Laravel, verifica si pertenece a Blade más `backend/resources/` en lugar de a la app Vue independiente.
4. Mantén alineados los cambios de esquema y modelo.
5. Ejecuta una validación backend después de editar.

## Convención: Repositorios por entidad (CQRS en español)

Para mantener orden y claridad, los repositorios se organizan por entidad en `app/Repositories/<Entidad>/` y siguen un patrón CQRS (separación Leer/Escribir) con nombres en español:

- Estructura de ejemplo:
	- `app/Repositories/User/GetUserRepository.php` — métodos de lectura (`buscarPorEmail`, `buscarPorId`, `listarPorRol`, `buscarPorFiltro`, etc.).
	- `app/Repositories/User/SaveUserRepository.php` — métodos de escritura (`crear`, `actualizar`, `cambiarPassword`, etc.).
	- `app/Repositories/RefreshToken/RefreshTokenWriteRepository.php` — persistencia específica de `RefreshToken` (`guardar`, `revocar`, `buscarPorJti`).

- Reglas y decisiones:
	- No usamos interfaces para los repositories (resolución automática por clase concreta).
	- Queries complejas que involucran varias tablas deben ubicarse en el repo del agregado principal (p.ej. `User`), salvo que sean cross-cutting y merezcan su propio repo/facade.
	- Las transacciones se manejan desde `Commands`/servicios que orquestan varias operaciones de escritura.
	- Mantén los repositorios centrados en la persistencia y transformación mínima: recibir/retornar Eloquent models o DTOs simples según necesidad.
	- Prefiere nombres de método descriptivos en español para legibilidad del equipo (ej. `buscarPorEmail`, `guardarRefreshToken` → en este caso delegar a `RefreshTokenWriteRepository->guardar`).

- Ejemplo rápido (métodos recomendados):
	- `GetUserRepository`: `buscarPorId(int $id): ?User`, `buscarPorEmail(string $email): ?User`, `listarActivos(array $filtros): Collection`.
	- `SaveUserRepository`: `crear(array $data): User`, `actualizar(User $user, array $data): User`, `cambiarPassword(User $user, string $password): void`.
	- `RefreshTokenWriteRepository`: `guardar(User $user, string $token, string $jti, string $ip, string $ua, string $expiresAtIso): RefreshToken`, `revocarPorJti(string $jti): void`.

- Tests:
	- Tests unitarios para repositorios con DB mock/ fakes; tests de integración cuando se necesita validar queries complejas.

Estas reglas ayudan a mantener la responsabilidad clara entre entidades, facilitan el refactor y hacen las pruebas más directas.
## Comandos Útiles

Ejecuta estos comandos desde `backend/` o dentro del contenedor `app`. Ejemplos con Docker Compose:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test
```

## Guía de Validación

- Prefiere tests Laravel dirigidos cuando cambie el comportamiento.
- Si todavía no existen tests dirigidos, usa `php artisan test` como comprobación base.
- Si la tarea toca solo configuración o rutas, puede bastar una validación ligera de arranque del framework o de rutas.