## Deploy

1. Crear una copia del archivo `docker/config/nginx/production.conf.example`, renombrarlo a `production.conf` y llenar los datos que correspondan a la configuración del servidor.
2. Crear una copia del archivo `docker/config/php/production.ini.example`, renombrarlo a `production.ini` y llenar los datos que correspondan a la configuración de PHP.
3. Crear una copia del archivo `.env.example` y renombrarlo a `.env` y llenar los datos que correspondan a la configuración de la app.
4. Levantar los contenedores
````bash
docker compose -f docker-compose.prod.yml up -d
````
5. Instalar dependencias de PHP y Javascript y compilar assets
````bash
docker compose -f docker-compose.prod.yml exec php84 composer install
docker compose -f docker-compose.prod.yml run --rm node npm ci
docker compose -f docker-compose.prod.yml run --rm node npm run build
````
6. Generar la clave secreta de la aplicación
````bash
docker compose -f docker-compose.prod.yml exec php84 php artisan key:generate
````
7. Crear paciente de prueba (opcional)
````bash
docker compose -f docker-compose.prod.yml exec php84 php artisan colma:crear-paciente-test
````
8. Ejecutar migraciones y seeders de la base de datos
````bash
docker compose -f docker-compose.prod.yml exec php84 php artisan migrate --step
docker compose -f docker-compose.prod.yml exec php84 php artisan db:seed
````

## Comandos producción

Levantar contenedores

````bash
docker compose -f docker-compose.prod.yml up -d
````

Instalar dependencias de PHP

````bash
docker compose -f docker-compose.prod.yml exec --user www-data php84 composer install --no-dev --optimize-autoloader
````

Instalar dependencias de JS

````bash
docker compose -f docker-compose.prod.yml run --rm node npm ci
````

Compilar assets

````bash
docker compose -f docker-compose.prod.yml run --rm node npm run build
````

Ejecutar comandos de Artisan

````bash
docker compose -f docker-compose.prod.yml exec --user www-data php84 php artisan <command>
````

Los comandos de Artisan se ejecutan como `www-data` para que los archivos generados
(por ejemplo, cachés) tengan los permisos adecuados para el proceso de PHP-FPM.

## Piloto de seguimientos mensuales

La variable `PILOTO` define la cantidad de seguimientos mensuales de los
entrenamientos nuevos:

```dotenv
PILOTO=true  # 3 seguimientos mensuales
PILOTO=false # 5 seguimientos mensuales
```

El valor se guarda al crear cada entrenamiento. Por eso, cambiar la variable no
altera los tratamientos ya iniciados; al finalizar el piloto, usar `PILOTO=false`
para que los nuevos tratamientos vuelvan al circuito de cinco seguimientos. Luego
de modificarla en producción, regenerar la caché de configuración.

## Actualizar la aplicación

Después de actualizar el código en el servidor, ejecutar la siguiente secuencia.
No es necesario bajar los contenedores:

````bash
# Si cambiaron dependencias PHP
docker compose -f docker-compose.prod.yml exec --user www-data php84 composer install --no-dev --optimize-autoloader

# Siempre que cambien recursos Vue, JavaScript o CSS
docker compose -f docker-compose.prod.yml run --rm node npm ci
docker compose -f docker-compose.prod.yml run --rm node npm run build

# Si existen migraciones nuevas
docker compose -f docker-compose.prod.yml exec --user www-data php84 php artisan migrate --force

# Limpiar y regenerar las cachés de Laravel
docker compose -f docker-compose.prod.yml exec --user www-data php84 php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec --user www-data php84 php artisan config:cache
docker compose -f docker-compose.prod.yml exec --user www-data php84 php artisan route:cache
docker compose -f docker-compose.prod.yml exec --user www-data php84 php artisan view:cache
````

`opcache.validate_timestamps` debe estar en `1` y `opcache.revalidate_freq` en `0`
en `docker/config/php/production.ini`. Con esos valores, PHP-FPM detecta el código
actualizado en la siguiente petición. Si el servidor conserva la configuración
anterior con `opcache.validate_timestamps = 0`, aplicar el cambio y reiniciar solo
PHP-FPM (`docker compose -f docker-compose.prod.yml restart php84`); no hace falta
ejecutar `docker compose down`.
