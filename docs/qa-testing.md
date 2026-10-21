# QA testing

QA (*Quality Assurance*, o aseguramiento de calidad) es el conjunto de
prácticas que busca prevenir defectos y comprobar que el sistema satisface los
requisitos antes de entregarlo. No se limita a ejecutar pruebas: también define
qué debe verificarse, con qué datos, quién lo valida y cómo se registra un
resultado.

En Colma, donde se manejan datos clínicos y seguimientos de pacientes, QA debe
proteger especialmente la integridad de los registros, los permisos de cada
rol, la autenticación y la continuidad de los circuitos de atención.

## Estrategia propuesta

Se recomienda trabajar en tres niveles, de menor a mayor alcance:

| Nivel | Objetivo | Implementación actual o propuesta |
| --- | --- | --- |
| Unitario | Validar una regla o servicio de forma aislada. | PHPUnit sobre los servicios de `app/Services`; ya hay pruebas en `tests/Unit`. |
| Funcional / integración | Validar una ruta HTTP, validaciones, permisos y persistencia. | Laravel Feature Tests con `RefreshDatabase`, en `tests/Feature`. |
| Exploratorio manual | Comprobar que una persona puede completar un flujo real en la interfaz. | Lista de verificación por cambio, realizada en el entorno de prueba. |

Las pruebas automatizadas usan SQLite en memoria, según `phpunit.xml`. Por lo
tanto no modifican la base MySQL de desarrollo ni la de producción. Cada prueba
que use `RefreshDatabase` inicia con una base limpia.

## Ejecutar las pruebas

Con el entorno Docker de desarrollo levantado, ejecutar:

```bash
# Toda la suite
docker compose exec -T php84 php artisan test

# Solo pruebas unitarias o funcionales
docker compose exec -T php84 php artisan test --testsuite=Unit
docker compose exec -T php84 php artisan test --testsuite=Feature

# Una clase o caso concreto mientras se desarrolla
docker compose exec -T php84 php artisan test tests/Feature/ApiPacienteControllerTest.php
docker compose exec -T php84 php artisan test --filter=test_paciente_puede_sincronizar_sus_datos

# Estilo del código PHP antes de abrir una revisión
docker compose exec -T php84 ./vendor/bin/pint --test
```

Si las dependencias PHP están disponibles en la máquina anfitriona, los
equivalentes son `composer test`, `php artisan test` y `./vendor/bin/pint
--test`. En este repositorio el comando de Composer `test` limpia previamente
la configuración de Laravel.

Una falla debe tratarse como un cambio incompleto: registrar el caso que falló,
corregir la causa y repetir la suite afectada. Para una corrección de seguridad,
permisos o datos clínicos, añadir además una prueba de regresión que hubiera
detectado el defecto.

## Cobertura actual

La suite existente ya valida, entre otros aspectos:

- servicios de pacientes, usuarios, alertas, interacciones, indicadores y datos
  clínicos;
- inicio de sesión y limitación de intentos;
- API de autenticación, sincronización de datos del paciente y pedido de ayuda;
- políticas de acceso a pacientes y usuarios;
- relaciones y conversiones de modelos;
- acceso seguro a archivos multimedia y reglas de tratamientos.

Al crear o modificar una funcionalidad, añadir la prueba en el nivel más bajo
que pruebe el comportamiento completo. Por ejemplo, una regla de cálculo en un
servicio corresponde a `tests/Unit`; una ruta que valida permisos y guarda una
interacción corresponde a `tests/Feature`.

## Casos manuales mínimos por entrega

Antes de aprobar una entrega, ejecutar los escenarios afectados por el cambio
en un ambiente de prueba con datos no reales. Como mínimo, revisar:

1. Inicio y cierre de sesión con credenciales válidas e inválidas.
2. Acceso de cada rol solo a las pantallas y acciones autorizadas.
3. Alta, edición y consulta de paciente, incluidos sus diagnósticos y datos de
   contacto.
4. Registro de una interacción y verificación de que aparece en el historial y
   genera las alertas esperadas.
5. Sincronización desde la API móvil y visualización correcta de los datos
   clínicos asociados al paciente.
6. Validaciones de campos obligatorios, formatos y mensajes de error visibles.
7. Visualización en el navegador y resolución que se usan habitualmente.

Para cada caso documentar el resultado (aprobado o fallido), versión o commit,
fecha, entorno y evidencia útil, como una captura sin datos sensibles o el ID
de un registro de prueba. Nunca usar información clínica real en capturas,
incidencias ni ambientes de prueba.

## Criterio de salida para un cambio

Un cambio queda listo para revisión cuando:

- las pruebas automatizadas aplicables pasan;
- se añadió o actualizó una prueba cuando cambió una regla de negocio, permiso,
  validación o endpoint;
- los casos manuales afectados están aprobados;
- no se exponen datos personales o clínicos en datos de prueba, logs o evidencia;
- el código PHP cumple `Pint`.
