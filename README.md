## Deploy

1. Crear una copia del archivo `docker/config/nginx/production.conf.example`, renombrarlo a `production.conf` y llenar los datos que correspondan a la configuración del servidor.
2. Crear una copia del archivo `docker/config/php/production.ini`, renombrarlo a `production.ini` y llenar los datos que correspondan a la configuración de PHP.
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
docker compose -f docker-compose.prod.yml run --rm composer install
````

Instalar dependencias de JS

````bash
docker compose -f docker-compose.prod.yml run --rm node npm install
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
