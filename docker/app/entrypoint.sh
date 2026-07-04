#!/bin/sh
set -e

render_nginx() {
    # Sustituye SOLO ${PORT}, preservando las variables internas de nginx ($uri, $query_string...)
    envsubst '${PORT}' < /etc/nginx/templates/default.template > /etc/nginx/conf.d/default.conf
}

run_migrations() {
    # Resuelve conexion final (Laravel prioriza DATABASE_URL sobre variables sueltas)
    local conn="${DB_CONNECTION:-}"
    if [ -z "$conn" ]; then
        if [ -n "$DATABASE_URL" ]; then
            case "$DATABASE_URL" in
                mysql://*|mysqli://*) conn="mysql" ;;
                postgres://*|pgsql://*) conn="pgsql" ;;
                sqlite:*) conn="sqlite" ;;
            esac
        fi
    fi

    if [ "$conn" = "sqlite" ]; then
        echo "[entrypoint] DB_CONNECTION=sqlite; se omite migrate (esperado en smoke test local)."
        return 0
    fi

    if [ -n "$DB_HOST" ] || [ -n "$DATABASE_URL" ] || [ "$conn" = "mysql" ]; then
        echo "[entrypoint] Running migrations (conn=${conn:-mysql})..."
        php artisan migrate --force --no-interaction || {
            echo "[entrypoint] WARNING: migrate fallo (DB no lista?) — Railway reintentara el contenedor."
        }
    else
        echo "[entrypoint] WARNING: sin DB_HOST/DATABASE_URL/DB_CONNECTION=mysql; se omite migrate."
    fi
}

render_nginx
run_migrations

exec "$@"