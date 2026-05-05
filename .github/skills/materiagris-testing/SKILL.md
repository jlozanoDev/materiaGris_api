---
name: materiagris-testing
description: "Flujo de validación para Materiagris. Úsalo cuando la tarea trate sobre tests, smoke checks, prevención de regresiones, verificación backend, validación del build frontend o para decidir la comprobación más ligera y útil después de un cambio."
argument-hint: "Tarea de testing o validación para Materiagris"
user-invocable: true
---

# Validación de Materiagris

Usa esta skill para elegir y ejecutar el nivel correcto de validación después de un cambio.

## Cuándo Usarla

- El usuario pide tests.
- Necesitas validar un cambio antes de cerrar la tarea.
- El usuario pregunta qué comando debería ejecutarse tras una edición en backend o frontend.
- Necesitas elegir entre un smoke check y una regresión más amplia.

## Superficie Actual de Validación

- El soporte de tests backend existe a través de Laravel y PHPUnit.
- `backend/composer.json` expone un script `test`.
- El frontend independiente expone scripts de build y preview, pero no se ve un runner automatizado de tests.
- Los servicios Docker pueden validarse con smoke checks mediante logs de compose y comprobaciones de endpoints.

## Procedimiento

1. Identifica la capa afectada: backend, frontend, infraestructura o mixto.
2. Elige la comprobación más ligera que siga demostrando el cambio.
3. Prefiere validación dirigida cuando exista.
4. Escala a comprobaciones más amplias solo cuando el riesgo lo justifique.
5. Informa de qué se ejecutó y qué no se ejecutó.

## Comprobaciones Sugeridas

- Código backend: `composer test` o `php artisan test`
- Configuración o rutas backend: arranque del framework o smoke check de rutas, y luego test más amplio si el riesgo es mayor
- Código frontend: `npm run build`
- Cambios de infraestructura o compose: arranque de contenedores más verificación dirigida del servicio
- Cambios transversales: una comprobación por cada capa afectada

## Comandos Útiles

```bash
cd backend && composer test
cd backend && php artisan test
cd frontend && npm run build
docker-compose ps
```

## Reglas de Reporte

- Indica exactamente qué comando se ejecutó.
- Si una comprobación no se ejecutó, explica por qué.
- Si no existe test automatizado para el área afectada, dilo con claridad.