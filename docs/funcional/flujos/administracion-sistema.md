# Flujo de Administración del Sistema — API

## Gestión de Usuarios

### Crear Usuario
1. Frontend envía `POST /api/admin/users` con `{ name, email, password, roles[], permissions{} }`.
2. API valida: email único, contraseña segura.
3. Asigna roles y/o overrides de permisos.
4. Responde `201 Created` con datos del usuario.

### Listar Usuarios
1. Frontend envía `GET /api/admin/users`.
2. API responde `200` con lista de usuarios, sus roles y estado activo.

### Obtener Usuario
1. Frontend envía `GET /api/admin/users/{id}`.
2. API responde `200` con datos del usuario, roles, permisos efectivos.

### Actualizar Usuario
1. Frontend envía `PUT /api/admin/users/{id}` con campos a modificar.
2. API valida email único (excluyendo ID actual), roles, overrides.
3. Responde `200` con datos actualizados.

### Eliminar Usuario
1. Frontend envía `DELETE /api/admin/users/{id}`.
2. API verifica que el usuario no sea de sistema.
3. Marca `deleted_at` (soft delete).
4. Responde `200` (o `204`).

## Gestión de Roles

### Crear Rol
1. Frontend envía `POST /api/admin/roles` con `{ name, slug, description, permissions[] }`.
2. API valida slug único.
3. Asigna permisos con grant/deny.
4. Responde `201 Created`.

### Listar Roles
1. Frontend envía `GET /api/admin/roles`.
2. API responde `200` con roles y permisos asociados.

### Obtener Rol
1. Frontend envía `GET /api/admin/roles/{id}`.
2. API responde `200` con datos del rol y sus permisos.

### Actualizar Rol
1. Frontend envía `PUT /api/admin/roles/{id}` con nombre, descripción, permisos.
2. API actualiza permisos reemplazando el conjunto anterior.
3. Responde `200` con datos actualizados.

### Eliminar Rol
1. Frontend envía `DELETE /api/admin/roles/{id}`.
2. API verifica que `is_system = false`.
3. Elimina el rol. Los usuarios pierden los permisos asociados.
4. Responde `200`.

## Gestión de Permisos

### Listar Permisos
1. Frontend envía `GET /api/admin/permissions`.
2. API responde `200` con catálogo completo de permisos agrupados por categoría.
3. Es solo lectura — no hay creación/modificación/eliminación vía API.

## Variables del Sistema

### Listar Variables
1. Frontend envía `GET /api/admin/system-variables`.
2. API responde `200` con catálogo de variables agrupadas por categoría (`paciente`, `clinica`, `fecha`, `usuario`, `medico`, `informe`).
3. Es solo lectura — 56 variables en total.
4. El frontend usa estos datos para autocompletado en el builder de plantillas (sintaxis `{categoria.clave}`).

## Flujo de Error — Intento de Eliminar Rol de Sistema
1. Frontend envía `DELETE /api/admin/roles/1` (rol admin, `is_system = true`).
2. API rechaza la operación.
3. Responde con error indicando que los roles de sistema no pueden eliminarse.

## Flujo de Error — Email Duplicado
1. Frontend envía `POST /api/admin/users` con email ya existente.
2. API valida unicidad.
3. Responde `422` con error de validación.
