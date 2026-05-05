# Materia Gris API - Instrucciones de Desarrollo (Docker-First)

Este repositorio contiene la **API de Materia Gris**. Todo el entorno de ejecución está orquestado mediante **Docker**.

## 🏗️ Arquitectura de Servicios

- **`materiagris_app`**: Backend Laravel 12 (PHP 8.2).
- **`materiagris_nginx`**: Servidor web (Puerto host: `80`).
- **`materiagris_db`**: MySQL 8.0 (Puerto host: `33060`).
- **`materiagris_mailhog`**: Pruebas de correo (Puerto host: `8025`).

## 🐳 Mandatos de Docker

1. **Comandos vía Docker**: Artisan, Composer y PHPUnit deben ejecutarse dentro del contenedor `app`.
2. **Puertos**: La API es accesible en `http://localhost`.
3. **Frontend**: El frontend corre fuera de Docker en `http://localhost:5173`.

## 🛠️ Comandos Frecuentes

- **Artisan**: `docker exec -it materiagris_app php artisan [comando]`
- **Composer**: `docker exec -it materiagris_app composer [comando]`
- **Tests**: `docker exec -it materiagris_app vendor/bin/phpunit`
- **Logs**: `tail -f storage/logs/laravel.log`

## 📝 Convenciones

- **Idioma**: Código en **inglés**, documentación/comentarios en **español**.
- **Arquitectura**: Sigue el patrón Hexagonal (`Domain`, `Application`, `Infrastructure`).
- **Auditoría**: Acciones sensibles en la tabla `audits`.
