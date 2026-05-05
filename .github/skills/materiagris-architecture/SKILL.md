---
name: materiagris-architecture
description: "Visión general del sistema Materiagris. Úsala cuando la tarea trate sobre arquitectura, estructura del proyecto, dónde vive el backend o el frontend, cómo está montado el stack, qué servicio es responsable de una funcionalidad o para onboarding del repositorio."
argument-hint: "Pregunta sobre la arquitectura o la estructura del proyecto Materiagris"
user-invocable: true
---

# Arquitectura de Materiagris

Usa esta skill para recorridos del repositorio y explicaciones a nivel de sistema.

## Cuándo Usarla

- El usuario pregunta qué es Materiagris.
- El usuario pregunta cómo está montado el sistema.
- El usuario pregunta dónde debería vivir una funcionalidad.
- El usuario necesita onboarding sobre la estructura del repositorio.
- El usuario no tiene claro si un cambio pertenece a backend, frontend o infraestructura.

## Hechos Clave

- La orquestación principal está en `docker-compose.yml`.
- El backend es una aplicación Laravel 12 en `backend/`.
- La UI cliente es una app Vue 3 + Vite en `frontend/`.
- También existe un pipeline Vite del lado Laravel en `backend/resources/` y `backend/vite.config.js`.
- La configuración de Nginx vive en `docker/nginx/vhost.conf`.
- El stack local incluye `app`, `node`, `nginx`, `db`, `redis` y `mailhog`.

## Mapa de Servicios

- `app`: contenedor de aplicación PHP para Laravel.
- `node`: contenedor del servidor de desarrollo Vite del frontend.
- `nginx`: proxy inverso para el backend.
- `db`: MySQL 8.0 para datos de aplicación.
- `redis`: soporte para cache o colas.
- `mailhog`: servicio local para capturar correos.

## Guía de Responsabilidad del Repositorio

- Usa `backend/` para rutas Laravel, controladores, modelos, migraciones, configuración, vistas Blade y tests.
- Usa `frontend/` para componentes de la SPA en Vue y comportamiento cliente independiente.
- Usa `backend/resources/` cuando el cambio pertenezca a assets gestionados por Laravel en lugar de la SPA independiente.
- Usa `docker/` y `docker-compose.yml` para comportamiento de ejecución local.

## Procedimiento

1. Empieza por `docker-compose.yml` para entender la topología de ejecución.
2. Comprueba si el cambio solicitado pertenece a `backend/`, `frontend/` o a ambos.
3. Si el usuario pregunta por el flujo de una petición, inspecciona `docker/nginx/vhost.conf`, `backend/public/index.php` y los archivos de rutas relevantes.
4. Si el usuario pregunta dónde implementar un cambio de UI, decide antes de avanzar si pertenece a `frontend/` o a `backend/resources/`.
5. Resume la arquitectura en pocas líneas antes de entrar en detalle archivo por archivo.

## Notas Conocidas del Proyecto

- El backend expone actualmente una ruta raíz simple en `backend/routes/web.php`.
- El servidor de desarrollo del frontend está configurado con host `0.0.0.0` en el puerto `5173`.
- Nginx sirve el directorio público de Laravel y enruta el tráfico PHP al contenedor `app`.

## Arquitectura recomendada (Hexagonal / Clean)

Materiagris adopta o recomienda un estilo hexagonal/clean para mantener la lógica de negocio separada de la infraestructura y del transporte HTTP.

- Capas principales y convenciones:
	- **HTTP / Actions:** `app/Http/Actions/` — entradas del transporte HTTP; son clases invocables que orquestan la petición y delegan a casos de uso (Commands). Mantener Actions delgadas.
	- **Commands / Use-cases:** `app/Commands/` — implementan la lógica de negocio y orquestan repositorios y servicios. Un Command no debe depender de detalles HTTP.
	- **Repositories / Gateways:** `app/Repositories/` — encapsulan el acceso a datos y clientes externos (DB, APIs, colas). Devuelven DTOs o modelos simples.
	- **Models / DTOs:** `app/Models/` — objetos de dominio o DTOs para transportar datos entre capas; preferir estructuras inmutables o simples cuando sea posible.

- Principios y prácticas:
	- **Inversión de dependencias:** inyectar dependencias por constructor para Actions, Commands y Repositories.
	- **Serialización en el perímetro:** Commands y Repositories retornan modelos/DTOs; la conversión a arrays/JSON se realiza en Actions (`__invoke()`), o el Action envuelve la respuesta en `JsonResponse`.
	- **Bindings perezosos:** si registras bindings en ServiceProviders, usa closures para que la resolución sea perezosa y no inicialice servicios que requieran configuración sensible (p.ej. `encrypter`) durante el boot temprano.
	- **Tests por capa:** tests unitarios para Commands/Repositories; tests de feature para Actions y rutas HTTP.

- Ejemplo rápido de rutas:
	- Acción invocable en ruta: `Route::get('/health', App\Http\Actions\Health\CheckHealthAction::class);`
	- Acción que delega a Command: el método `execute()` del Action retorna un DTO y `__invoke()` convierte a array o `JsonResponse`.

Adoptar estas convenciones facilita el mantenimiento, testing y evolución del código a medida que el producto crece.