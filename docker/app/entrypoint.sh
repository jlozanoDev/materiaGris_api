#!/bin/sh
set -e

render_nginx() {
    # Sustituye SOLO ${PORT}, preservando las variables internas de nginx ($uri, $query_string...)
    envsubst '${PORT}' < /etc/nginx/templates/default.template > /etc/nginx/conf.d/default.conf
}

run_migrations() {
    # Solo intentamos migrar si hay configuracion de base de datos en runtime
    if [ -n "$DB_HOST" ] || [ -n "$DATABASE_URL" ]; then
        echo "[entrypoint] Running migrations..."
        php artisan migrate --force --no-interaction
    else
        echo "[entrypoint] WARNING: DB_HOST/DATABASE_URL no definidos; se omite migrate."
    fi
}

render_nginx
run_migrations

exec "$@"