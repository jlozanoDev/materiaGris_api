# MateriaGris API

**MateriaGris** es una API RESTful de gestión clínica que da servicio al frontend SPA de MateriaGris. Proporciona toda la lógica de negocio, autenticación, almacenamiento y procesamiento de informes médicos para que profesionales de la salud administren su práctica diaria de forma digital.

- **Autenticación JWT** con access token (15 min) + refresh token httpOnly (14 días) y recuperación de contraseña.
- **Gestión completa de pacientes:** alta, edición, consulta y búsqueda.
- **Informes médicos:** ciclo de vida completo — creación, edición como borrador, firma digital, archivado, descarga en PDF y eliminación.
- **Asistente de IA para informes:** extracción de datos clínicos mediante LLM y transcripción de audio con diarización de hablantes.
- **Panel de administración:** gestión de usuarios, roles con permisos granulares, configuración de clínica y plantillas de informes personalizables con estructura JSON dinámica.
- **Control de acceso basado en roles (RBAC):** tres perfiles — Administrador, Profesional (Médico) y Recepcionista — cada uno con permisos específicos sobre recursos y acciones.

Desarrollada con **Laravel 12** siguiendo los principios de **Arquitectura Hexagonal**, con separación en capas (Actions → Commands → Repositories/Services), inyección de dependencias y testing automatizado.

**Frontend:** [MateriaGris Frontend](https://github.com/jlozanoDev/materiaGris_front)

## Stack tecnológico

| Tecnología | Versión | Uso |
|---|---|---|
| PHP | ^8.2 | Lenguaje del backend |
| Laravel | ^12.0 | Framework |
| lcobucci/jwt | ^4.2 | Autenticación JWT (implementación propia sin paquete Laravel) |
| barryvdh/laravel-dompdf | ^3.1 | Generación de PDFs de informes |
| MySQL | 8.0 | Base de datos en producción |
| Redis | 7-alpine | Cache y colas |
| Nginx | stable-alpine | Servidor web |
| PHPUnit | ^11.5 | Testing |
| Laravel Pint | ^1.24 | Code style |

> Consulta `composer.json` para las versiones exactas.

## Requisitos previos

- **Docker** y **Docker Compose**
- Archivo `.env` (copia `.env.example` y ajusta valores)
- Repositorio frontend: [`github.com/jlozanoDev/materiaGris_front`](https://github.com/jlozanoDev/materiaGris_front.git)

## Instalación y ejecución

```bash
# Clonar el repositorio
git clone <url-del-repositorio>
cd MateriaGris_api

# Crear archivo de entorno (ajustar según tu configuración)
cp .env.example .env

# Levantar los servicios
docker-compose up -d --build

# Instalar dependencias, generar key, migrar y compilar assets
docker exec -it materiagris_app bash -lc "composer setup"

# Poblar base de datos con datos de prueba
docker exec -it materiagris_app bash -lc "php artisan db:seed"

# Verificar que la API responde
curl http://localhost/api/health
```

La API se sirve en `http://localhost`.

### Servicios del stack

| Servicio | Puerto (host) | Acceso |
|---|---|---|
| **API (nginx → PHP-FPM)** | `80` | `http://localhost` |
| **MySQL** | `33060` | `127.0.0.1:33060` |
| **Mailhog (interfaz web)** | `8025` | `http://localhost:8025` |
| **Mailhog (SMTP)** | `1025` | — |

## Variables de entorno

| Variable | Descripción | Ejemplo |
|---|---|---|
| `DB_CONNECTION` | Conexión a BD (por defecto SQLite, MySQL opcional) | `sqlite` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Credenciales MySQL (si se usa) | `127.0.0.1` / `3306` / `laravel` / `root` / — |
| `JWT_SECRET` | Clave secreta para firmar tokens HS256 (vacío = usa `APP_KEY`) | — |
| `JWT_TTL` | Tiempo de vida del access token (minutos) | `15` |
| `JWT_REFRESH_TTL` | Tiempo de vida del refresh token (días) | `14` |
| `JWT_REFRESH_COOKIE` | Nombre de la cookie para refresh token | `refresh_token` |
| `JWT_COOKIE_DOMAIN` | Dominio de la cookie de refresh | `materiagris.local` |
| `CORS_ALLOWED_ORIGINS` | Orígenes permitidos por CORS (separados por coma) | `http://localhost:5173,http://materiagris.local` |
| `FRONTEND_URL` | URL pública del frontend (para enlaces en emails) | `http://materiagris.local:5173` |
| `MAIL_MAILER` | Driver de correo (`log` para desarrollo, `smtp` para Mailhog) | `log` |
| `MAIL_HOST` / `MAIL_PORT` | Host y puerto SMTP (Mailhog) | `mailhog` / `1025` |
| `LLM_PROVIDER` / `LLM_API_KEY` / `LLM_MODEL` | Proveedor de IA para extracción de datos | `openai` / — / `gpt-4o` |
| `STT_PROVIDER` / `STT_API_KEY` / `STT_MODEL` | Proveedor de transcripción de audio | — / — / — |

Copia `.env.example` a `.env` y ajusta los valores según tu entorno.

## Estructura del proyecto

```
MateriaGris_api/
├── app/
│   ├── Commands/           # Casos de uso (Auth, Admin, Health, Reports)
│   ├── DTOs/               # Data Transfer Objects
│   ├── Enums/              # Enumeraciones de dominio
│   ├── Exceptions/         # Excepciones de dominio
│   ├── Http/
│   │   ├── Actions/        # Entradas HTTP invocables (Controllers)
│   │   ├── Middleware/     # auth.jwt, require_permissions
│   │   ├── Requests/       # Form Requests con validación
│   │   └── Resources/      # API Resources para respuestas JSON
│   ├── Infrastructure/     # Clientes HTTP externos
│   ├── Mail/               # Clases de correo electrónico
│   ├── Models/             # Modelos Eloquent
│   ├── Repositories/       # Acceso a datos (10 repositorios)
│   └── Services/           # Servicios de dominio (JWT, LLM, STT, etc.)
├── config/                 # Configuración de Laravel y paquetes
├── database/
│   ├── factories/          # Model factories
│   ├── migrations/         # 39 migraciones
│   └── seeders/            # 7 seeders con datos de prueba
├── docker/
│   ├── app/                # PHP-FPM Dockerfile + configuraciones
│   └── nginx/              # Virtual hosts (dev y producción)
├── docs/
│   ├── funcional/          # Documentación funcional (módulos, flujos)
│   └── tecnica/            # Documentación técnica (arquitectura, endpoints, BD)
├── routes/
│   ├── api.php             # 43 endpoints REST
│   ├── console.php         # Comandos Artisan
│   └── web.php             # Rutas web mínimas
├── tests/
│   ├── Feature/            # Tests de integración HTTP
│   └── Unit/               # Tests unitarios
├── docker-compose.yml      # Orquestación local
├── Dockerfile              # Imagen de producción (Railway)
├── composer.json           # Dependencias y scripts
└── .env.example            # Plantilla de variables de entorno
```

El proyecto sigue una **Arquitectura Hexagonal** con tres capas de aplicación:

| Capa | Responsabilidad |
|---|---|
| `Http/Actions` | Punto de entrada HTTP. Reciben la request, delegan en Commands y devuelven respuestas JSON. |
| `Commands/` | Casos de uso. Contienen la lógica de aplicación y orquestan repositorios y servicios. |
| `Repositories/` + `Services/` | Acceso a datos e integraciones externas (JWT, LLM, STT, PDF, etc.). |

## Funcionalidades principales

| Módulo | Endpoints | Perfiles RBAC |
|---|---|---|
| **Health** | `GET /api/health` | Público |
| **Auth** | `POST /api/auth/login`, `POST /api/auth/refresh`, `POST /api/auth/logout`, `GET /api/auth/me`, `POST /api/auth/forgot`, `POST /api/auth/reset` | Público (salvo `/me` que requiere autenticación) |
| **Admin — Usuarios** | `GET /admin/users`, `GET /admin/users/{id}`, `POST /admin/users`, `PUT /admin/users/{id}`, `DELETE /admin/users/{id}` | Administrador |
| **Admin — Roles** | `GET /admin/roles`, `GET /admin/roles/{id}`, `POST /admin/roles`, `PUT /admin/roles/{id}`, `DELETE /admin/roles/{id}` | Administrador |
| **Admin — Permisos** | `GET /admin/permissions` | Administrador |
| **Admin — Plantillas** | `GET /admin/report-templates`, `GET /admin/report-templates/{id}`, `POST /admin/report-templates`, `PUT /admin/report-templates/{id}`, `DELETE /admin/report-templates/{id}` | Administrador |
| **Admin — Clínica** | `GET /admin/clinic`, `PUT /admin/clinic`, `POST /admin/clinic/logo` | Administrador |
| **Admin — Variables** | `GET /admin/system-variables` | Administrador |
| **Pacientes** | `GET /patients/find`, `GET /patients/{id}`, `POST /patients`, `PUT /patients/{id}` | Administrador, Profesional |
| **Informes** | `GET /reports`, `GET /reports/{id}`, `POST /reports`, `PUT /reports/{id}`, `POST /reports/{id}/sign`, `POST /reports/{id}/archive`, `GET /reports/{id}/pdf`, `POST /reports/{id}/extract-data`, `POST /reports/{id}/transcribe`, `DELETE /reports/{id}` | Administrador, Profesional |
| **Plantillas activas** | `GET /templates/active` | Administrador, Profesional |
| **Logos** | `GET /logos/{filename}` | Público |

La API cuenta con **43 endpoints** y un sistema de permisos granular basado en slugs (`admin.user.view`, `report.create`, `patient.edit`, etc.) que se validan mediante el middleware `require_permissions`.

### Autenticación JWT

El sistema de autenticación usa `lcobucci/jwt` con una implementación propia (sin paquetes Laravel de terceros):

1. **Login:** `POST /api/auth/login` recibe `{email, password}`, valida credenciales y devuelve `{access_token, expires_at}` en el body de la respuesta. El refresh token se envía en una cookie httpOnly (`refresh_token`) con dominio configurable.
2. **Refresh:** `POST /api/auth/refresh` lee el refresh token de la cookie, valida contra base de datos y emite un nuevo par de tokens.
3. **Logout:** invalida el refresh token en base de datos y elimina la cookie.
4. **Protección:** las rutas protegidas usan el middleware `auth.jwt` que valida el access token del header `Authorization: Bearer <token>`. La autorización granular se aplica con `require_permissions:<slug>`.

```bash
# Login de prueba (después de ejecutar los seeders)
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@materiagris.local","password":"secret123"}'
```

| Parámetro | Valor |
|---|---|
| Algoritmo | HS256 |
| TTL access token | 15 minutos |
| TTL refresh token | 14 días |

## Credenciales de prueba

El backend incluye seeders que crean los siguientes usuarios de prueba:

| Email | Contraseña | Rol |
|---|---|---|
| `test@materiagris.local` | `secret123` | Administrador |
| `testprofesional@materiagris.local` | `secret123` | Profesional (Médico) |

> Estas credenciales solo funcionan si ejecutaste los seeders (`php artisan db:seed`).

## Comandos

| Comando | Descripción |
|---|---|
| `docker-compose up -d --build` | Levanta todos los servicios |
| `docker exec -it materiagris_app bash -lc "composer setup"` | Instalación completa (composer install + key:generate + migrate + npm install + npm run build) |
| `docker exec -it materiagris_app bash -lc "php artisan db:seed"` | Poblar BD con datos de prueba |
| `docker exec -it materiagris_app bash -lc "composer test"` | Ejecutar tests (PHPUnit) |
| `docker exec -it materiagris_app bash -lc "composer dev"` | Modo desarrollo con hot-reload (servidor + queue + logs + Vite) |
| `docker exec -it materiagris_app bash -lc "php artisan migrate --force"` | Ejecutar migraciones |
| `docker-compose logs --tail 200 --follow app` | Ver logs del contenedor app |
| `docker exec -it materiagris_app bash` | Acceder al shell del contenedor |
| `docker exec -it materiagris_app bash -lc "composer pint"` | Verificar y corregir estilo de código |

## Documentación

El proyecto cuenta con documentación completa en la carpeta `docs/`:

- **[Índice de documentación](docs/INDICE.md)** — Punto de entrada a toda la documentación técnica y funcional.
- **[Documentación funcional](docs/funcional/INDICE.md)** — Propósito de negocio, módulos funcionales, reglas de negocio, flujos de API, perfiles de usuario y glosario.
- **[Documentación técnica](docs/tecnica/INDICE.md)** — Arquitectura hexagonal, guía de endpoints, modelo de datos, servicios, middleware y esquema de base de datos.
- **[Guía de endpoints](docs/tecnica/guia-endpoints-api.md)** — Listado detallado de todos los endpoints con ejemplos de request/response.
