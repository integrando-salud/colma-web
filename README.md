### Comandos producción

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
docker compose -f docker-compose.prod.yml run --rm php artisan <command>
````
