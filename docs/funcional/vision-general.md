# Visión General — MateriaGris API

## ¿Qué es MateriaGris?

MateriaGris es un sistema de gestión de pacientes y consultas para clínicas y consultorios médicos. La API REST proporciona la capa de negocio que consumen tanto el frontend SPA como potenciales integraciones de terceros.

## Objetivos de Negocio

- Digitalizar la gestión de pacientes (expediente electrónico).
- Centralizar la administración de usuarios, roles y permisos del sistema.
- Reducir errores administrativos mediante validaciones en servidor.
- Proveer una API segura y auditable para cumplimiento normativo.

## Alcance del Sistema

### Incluye (MVP)
- Autenticación y autorización vía JWT con refresh tokens.
- RBAC con permisos finos y overrides por usuario.
- CRUD de pacientes con soft delete.
- Administración completa de usuarios, roles y permisos.
- Auditoría de eventos sensibles.
- Health check para monitoreo.

### No incluye (corto plazo)
- Módulo de consultas médicas, recetas, órdenes de laboratorio/imagenología.
- Interfaz de usuario (el frontend es un proyecto separado).
- Integración con sistemas externos (laboratorios, farmacias, HIS).

## Perfiles de Usuario

| Perfil | Rol en sistema | Permisos típicos | Frecuencia de uso |
|--------|---------------|-------------------|-------------------|
| Médico | `medico` | `patient.view`, `patient.create`, `patient.update` | Diaria |
| Administrador | `admin` | `admin.user.*`, `admin.role.*`, `admin.permission.view` | Semanal |
| Recepcionista | *(futuro)* | `patient.view`, `patient.create` | Diaria |

## Stack Tecnológico

| Componente | Tecnología |
|------------|------------|
| Framework | Laravel 12 (PHP 8.2+) |
| Arquitectura | Hexagonal (Ports & Adapters) |
| Base de datos | SQLite (dev), MySQL 8.0 (producción) |
| Autenticación | JWT custom (`lcobucci/jwt`) |
| Autorización | RBAC custom (grant/deny con overrides) |
| Cache/Colas | Redis (con fallback a base de datos) |
| Contenedores | Docker Compose (app, nginx, MySQL, Redis, Mailhog) |
| Tests | PHPUnit (feature + unit) |
