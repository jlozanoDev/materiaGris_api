FROM mcr.microsoft.com/playwright:v1.59.1-jammy

WORKDIR /var/www/frontend

# Instalamos dependencias adicionales si son necesarias
RUN apt-get update && apt-get install -y git python3 make g++ && rm -rf /var/lib/apt/lists/*

# Asegurar que el directorio de trabajo pertenece al usuario no-root de Playwright
# (la imagen de Playwright crea el usuario `pwuser`). Esto permite que el contenedor
# ejecute procesos de la app sin usar root.
RUN chown -R pwuser:pwuser /var/www/frontend

# Ejecutar como usuario no-root proporcionado por la imagen
USER pwuser

EXPOSE 5173

CMD ["sh", "-c", "npm install --no-audit --no-fund || true && npm run dev -- --host 0.0.0.0"]
