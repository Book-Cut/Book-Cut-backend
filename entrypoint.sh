#!/bin/bash
set -e

PORT="${PORT:-10000}"

# Corregir si DB_CONNECTION viene configurado como 'postgresql' en lugar de 'pgsql'
if [ "$DB_CONNECTION" = "postgresql" ]; then
    export DB_CONNECTION=pgsql
fi

echo "Iniciando Apache en el puerto 0.0.0.0:${PORT}..."
sed -i "s/Listen 80/Listen 0.0.0.0:${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/*.conf

# Ejecutar migraciones y seeders automáticamente en cada despliegue
echo "Ejecutando migraciones y seeders de base de datos..."
#php artisan migrate:fresh --seed --force || true

exec apache2-foreground
