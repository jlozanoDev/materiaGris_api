# Spec: README.md para MateriaGris API

## Purpose

Criterios de aceptación para el nuevo `README.md` raíz — 10 secciones en español neutro, reemplazando el actual de 90 líneas.

## Requirements

### R1: Idioma y reemplazo

El README **MUST** estar en español neutro/profesional y **MUST** reemplazar el `README.md` raíz existente.

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 1 | Reemplazo + idioma | README actual existe | se aplica el cambio | el archivo raíz es el nuevo README completo en español sin regionalismos |

### R2: Estructura de 10 secciones

El README **MUST** tener 10 secciones con formato de tablas markdown consistente con el frontend.

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 2 | Secciones | README generado | se cuentan headings `##` | hay 10 secciones: título+descripción, stack, requisitos, instalación, variables entorno, estructura proyecto, funcionalidades, credenciales, comandos, documentación |

### R3: Stack tecnológico

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 3 | Tabla stack | README generado | se lee esa sección | tabla nombre/versión/propósito incluye: PHP 8.2, Laravel 12, lcobucci/jwt, MySQL 8.0, Redis 7, Nginx, barryvdh/dompdf |

### R4: Requisitos previos

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 4 | Requisitos | README generado | dev nuevo lee | lista Docker + Docker Compose; indica copiar `.env.example` → `.env` |

### R5: Instalación y ejecución

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 5 | Setup | README generado | dev sigue pasos | comandos `docker-compose up -d --build` y `docker exec -it materiagris_app bash -lc "composer setup"` son copiables y funcionales |

### R6: Variables de entorno

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 6 | Tabla .env | README generado | se inspecciona | tabla variable/descripción/ejemplo cubre: DB_CONNECTION, JWT_SECRET, JWT_TTL, JWT_REFRESH_TTL, CORS_ALLOWED_ORIGINS, FRONTEND_URL, variables mail |

### R7: Estructura del proyecto

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 7 | Árbol + capas | README generado | dev mira estructura | incluye árbol simplificado y tabla de capas hexagonal: HTTP → Actions → Commands → Repositories → Models |

### R8: Funcionalidades y endpoints

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 8 | Módulos | README generado | se inspecciona | tabla cubre módulos (Auth, Admin/Usuarios/Roles/Permisos/Plantillas/Clínica, Reports, Patients, Templates) con rutas base y roles RBAC; total ~39 endpoints |
| 9 | Auth JWT | README generado | se lee módulo Auth | se describe flujo: access_token (15 min) en JSON + refresh_token en cookie httpOnly (14 días) |

### R9: Credenciales de prueba

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 10 | Credenciales | README generado | se inspecciona tabla | `test@materiagris.local` / `secret123` (Admin) y `testprofesional@materiagris.local` / `secret123` (Médico); dominio sin 's' |

### R10: Comandos frecuentes

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 11 | Tabla comandos | README generado | dev busca comandos | incluye: `composer setup`, `composer test`, `composer dev`, `php artisan migrate`, `php artisan db:seed` |

### R11: Enlaces a documentación

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 12 | Enlaces | README generado | se verifican links | apuntan a `docs/INDICE.md`, `docs/funcional/INDICE.md`, `docs/tecnica/INDICE.md`, `docs/tecnica/guia-endpoints-api.md` |

### R12: Autocontenido para onboarding

| # | Scenario | GIVEN | WHEN | THEN |
|---|----------|-------|------|------|
| 13 | Levantar desde cero | dev clona repo sin contexto previo | sigue README paso a paso | levanta la API localmente sin consultar fuentes externas |
| 14 | Login prueba | entorno levantado con seeders | ejecuta POST `/api/auth/login` con credenciales de prueba | recibe access_token + refresh_token en cookie httpOnly |
