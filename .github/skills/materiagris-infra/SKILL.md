---
name: materiagris-infra
description: "Flujo de trabajo de infraestructura y entorno para Materiagris. Úsalo al trabajar con Docker Compose, Dockerfiles, Nginx, variables de entorno, red entre contenedores, MySQL, Redis, Mailhog, dominios locales o problemas de arranque en desarrollo."
argument-hint: "Tarea de infraestructura o entorno en Docker, Nginx, variables env o servicios locales"
user-invocable: true
---

# Infraestructura de Materiagris

Usa esta skill para cambios de entorno local, ejecución y contenedores.

## Cuándo Usarla

- La tarea toca `docker-compose.yml`.
- La tarea toca Dockerfiles en `docker/` o configuración de Nginx.
- El usuario pregunta por `.env`, puertos, servicios o hostnames.
- Hay problemas de arranque, red o integración entre contenedores.
- La tarea involucra MySQL, Redis o Mailhog en desarrollo local.

## Archivos y Áreas Clave

- `docker-compose.yml`
- `docker/app/Dockerfile`
- `docker/node/Dockerfile`
- `docker/nginx/vhost.conf`
- `.env`

## Notas Actuales de Infraestructura

- `nginx` expone el tráfico backend en el puerto host `8080`.
- `node` expone el servidor de desarrollo del frontend en el puerto host `5173`.
- `db` expone MySQL en el puerto host `33060`.
- `mailhog` expone la UI en `8025` y SMTP en `1025`.
- Los extra hosts incluyen `materiagris.local` y `api.materiagris.local`.

## Procedimiento

1. Lee primero `docker-compose.yml` para entender cómo están conectados los servicios.
2. Si el problema es de routing de peticiones, inspecciona después `docker/nginx/vhost.conf`.
3. Si el problema es de arranque de aplicación o runtime de paquetes, inspecciona el Dockerfile relevante.
4. Si el problema es de credenciales o configuración de ejecución, inspecciona `.env` y la configuración consumidora.
5. Valida con el comando más pequeño que demuestre que los contenedores o servicios se comportan como se espera.

## Comandos Útiles

Ejecuta estos comandos desde la raíz del repositorio.

```bash
docker-compose up --build
docker-compose ps
docker-compose logs nginx
docker-compose logs app
docker-compose logs node
docker-compose exec app php artisan migrate
```

## Guía de Validación

- Después de cambios en Compose, valida el arranque de servicios y la salud de contenedores.
- Después de cambios en Nginx, valida la ruta backend a través del proxy inverso.
- Después de cambios en variables de entorno, verifica el comportamiento afectado en la aplicación en lugar de asumir que los nuevos valores se cargaron correctamente.