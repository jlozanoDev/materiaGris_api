# Arquitectura — MateriaGris API

## Patrón Arquitectónico: Hexagonal (Ports & Adapters)

La API sigue una arquitectura hexagonal con 5 capas. Cada capa tiene responsabilidades bien definidas y dependencias unidireccionales.

## Capas

```
┌─────────────────────────────────────────────┐
│              HTTP (Routes)                   │
├─────────────────────────────────────────────┤
│           Actions (HTTP Adapters)            │  ← Presentación
├─────────────────────────────────────────────┤
│           Commands (Use Cases)               │  ← Aplicación
├─────────────────────────────────────────────┤
│  Services (Domain)  │  Repositories (Ports)  │  ← Dominio
├─────────────────────────────────────────────┤
│         Models / Eloquent (Adapters)         │  ← Infraestructura
└─────────────────────────────────────────────┘
```

## Flujo de Datos (Request Típico)

```
HTTP Request
    │
    ▼
Route (api.php)
    │
    ▼
Middleware (AuthenticateJwt, RequirePermissions, throttle)
    │
    ▼
Action (HTTP Adapter)
    ├── Valida request (FormRequest)
    ├── Instancia Command (Use Case)
    │
    ▼
Command (Use Case)
    ├── Obtiene datos vía Repository
    ├── Ejecuta lógica de negocio
    ├── Invoca Services (PermissionService, AuditService, etc.)
    │
    ▼
Repository (Port)
    │
    ▼
Model / Eloquent (Adapter)
    │
    ▼
Database
    │
    ▼
Response JSON (← Action)
```

## Estructura de Directorios

```
app/
├── Commands/                    # Casos de uso (Application Layer)
│   ├── Admin/                   #   admin users, roles, permissions
│   │   └── ReportTemplate/      #     report template CRUD commands
│   ├── Auth/                    #   login, logout, refresh, me, forgot/reset
│   ├── Health/                  #   health check
│   └── Reports/                 #   reports CRUD commands (List, Init, SaveDraft, Sign, Close, DownloadPdf, ExtractData, Transcribe)
│
├── Exceptions/                  # Excepciones de dominio
│   ├── Handler.php
│   └── PermissionDeniedException.php
│
├── Http/
│   ├── Actions/                 # HTTP Adapters (Presentation Layer)
│   │   ├── Admin/               #   adaptadores para admin
│   │   │   └── ReportTemplate/  #     adaptadores para CRUD de plantillas
│   │   ├── Auth/                #   adaptadores para auth
│   │   ├── Health/              #   adaptadores para health
│   │   ├── Patients/            #   adaptadores para patients
│   │   └── Reports/             #   adaptadores para reports (CRUD, transcribe, extract-data)
│   │
│   ├── Controllers/             # (residual, no usado activamente)
│   │
│   ├── Kernel.php               # Stack de middleware HTTP
│   │
│   ├── Middleware/               # Middleware personalizado
│   │   ├── AuthenticateJwt.php   #   Valida JWT Bearer token
│   │   └── RequirePermissions.php #   Verifica permisos del usuario
│   │
│   └── Requests/                # Form requests (validación)
│       └── Admin/
│           ├── CreateUserRequest.php
│           └── UpdateUserRequest.php
│
├── Infrastructure/              # Adaptadores de infraestructura
│   └── Http/
│       └── Middleware/
│
├── Mail/                        # Mailables
│   └── PasswordResetMail.php
│
├── Models/                      # Modelos Eloquent (Data Access)
│   ├── Audit.php
│   ├── HealthStatus.php         # DTO, no BD
│   ├── LlmInteraction.php       # Interacciones con LLM (STT y extracción)
│   ├── Patient.php
│   ├── PatientReport.php        # Informes de pacientes
│   ├── Permission.php
│   ├── PermissionCategory.php
│   ├── RefreshToken.php
│   ├── ReportTemplate.php       # Plantillas de informes
│   ├── Role.php
│   └── User.php
│
├── Providers/                   # Service Providers
│   ├── AppServiceProvider.php   #   DI bindings
│   └── RouteServiceProvider.php
│
├── Repositories/                # Repositorios (Ports)
│   ├── HealthRepository.php
│   ├── PasswordReset/
│   │   └── PasswordResetRepository.php
│   ├── Patient/
│   │   ├── PatientReadRepository.php
│   │   └── SavePatientRepository.php
│   ├── Permission/
│   │   └── GetPermissionRepository.php
│   ├── RefreshToken/
│   │   ├── GetRefreshTokenRepository.php
│   │   └── SaveRefreshTokenRepository.php
│   ├── Report/
│   │   ├── PatientReportReadRepository.php
│   │   └── PatientReportSaveRepository.php
│   ├── Role/
│   │   └── RoleRepository.php
│   └── User/
│       ├── GetUserRepository.php
│       └── SaveUserRepository.php
│
└── Services/                    # Servicios de dominio
    ├── AuditService.php
    ├── JwtService.php
    ├── PasswordResetService.php
    ├── PermissionService.php
    ├── RoleAssignmentService.php
    ├── SpeakerClassifierService.php   # Clasifica segmentos como Médico/Paciente por heurística + LLM
    └── SpeechToTextService.php        # Servicio de transcripción de audio (Whisper/OpenAI-compatible)
```

## Inyección de Dependencias

Los bindings se registran en `AppServiceProvider`. Los Actions reciben sus dependencias (Commands, Services) mediante inyección en el constructor o método `__invoke`.

Ejemplo:
```php
// En AppServiceProvider
$this->app->bind(PatientReadRepository::class, function ($app) {
    return new PatientReadRepository();
});

// En Action — Laravel resuelve automáticamente
class GetPatientsAction {
    public function __invoke(
        GetPatientsCommand $command,
        PatientReadRepository $repository
    ) {
        // ...
    }
}
```

## Mapa Módulo-vs-Capa

| Módulo | Actions | Commands | Repositories | Models | Services |
|--------|---------|----------|-------------|--------|----------|
| Health | 1 | 1 | 1 | 0 | 0 |
| Auth | 6 | 6 | 4 | 2 | JwtService, PasswordResetService |
| Patients | 3 | 0 (en Actions) | 2 | 1 | 0 |
| Admin — Users | 5 | 4 | 2 | 1 | RoleAssignmentService |
| Admin — Roles | 5 | 5 | 1 | 1 | PermissionService |
| Admin — Permissions | 1 | 1 | 1 | 2 | PermissionService |
| Admin — Report Templates | 5 | 5 | 0 | 1 | 0 |
| Reports — CRUD | 7 | 7 | 2 | 1 | PermissionService |
| Reports — Dictado | 2 | 2 | 0 | 2 | SpeakerClassifierService, SpeechToTextService |
