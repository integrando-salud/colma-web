# Registro de integración móvil

Registra solamente los POST a `/api/sync-data-paciente` y `/api/pedido-ayuda`,
incluidos los rechazos de autenticación, autorización y validación.

Aplicar la migración con `php artisan migrate` y configurar en `.env`:

```dotenv
MOBILE_API_LOG_ENABLED=true
MOBILE_API_LOG_AUTO_CLEANUP=true
MOBILE_API_LOG_CLEANUP_EVERY_DAYS=1
MOBILE_API_LOG_RETENTION_DAYS=30
MOBILE_API_LOG_MAX_BODY_BYTES=0
```

Por defecto, registro y limpieza automática están desactivados. La frecuencia
indica cada cuántos días se ejecuta la limpieza; la retención indica qué antigüedad
deben tener los registros para ser eliminados. Ambos valores tienen un mínimo de
un día. La limpieza puede seguir activa aunque se desactive el registro.

La tarea corre a las 03:00 UTC en días cuyo número desde el 01/01/1970 UTC es
múltiplo del intervalo configurado. Esto mantiene intervalos estables entre meses
y reinicios. Requiere que el servidor ejecute el scheduler de Laravel cada minuto:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Si se usa Docker, ejecutar `artisan` dentro del servicio `php84`. Tras cambiar
configuración en un despliegue con caché, reconstruirla con `php artisan config:cache`.
La limpieza manual está disponible siempre con `php artisan mobile-api-logs:prune`.
Elimina en lotes los registros anteriores a la fecha UTC actual menos la retención.

La tabla `mobile_api_logs` permite buscar por `request_id`, usuario, fecha, endpoint
y código HTTP. `response_status = null` indica un intento sin finalización registrada;
no debe interpretarse automáticamente como un fallo clínico. La respuesta incluye
`X-Request-ID` y los errores técnicos reportados incluyen ese identificador en su
contexto. La columna `exception_message` contiene la referencia al log técnico,
para evitar duplicar mensajes SQL o secretos. No se guardan trazas en la tabla.

El límite de tamaño queda desactivado con `MOBILE_API_LOG_MAX_BODY_BYTES=0`,
con un valor vacío o si la variable no está definida. Un valor positivo limita
cada cuerpo registrado a esa cantidad de bytes.

Los cuerpos JSON se almacenan en un envoltorio con `data`, `bytes` y `truncated`.
Se conservan campos no validados y se ocultan recursivamente claves de contraseñas,
tokens, cookies, authorization y secretos. Si superan el límite se guarda una
`preview` truncada después de ocultar secretos. Para cuerpos que no sean JSON válido,
se guardan solamente longitud y SHA-256; el texto arbitrario no se persiste.
Los registros incluyen datos clínicos: restringir el acceso a la tabla y sus backups.

La app puede enviar `X-App-Version`, `X-Platform` y `X-OS-Version` para identificar
el cliente. La identidad se obtiene del usuario autenticado, nunca del `userId`
enviado. No se almacenan encabezados de autenticación.

El registro inicial se escribe antes de la operación y el resultado después de su
transacción. Los errores al guardar el log se reportan al log habitual sin cambiar
la respuesta clínica. Una interrupción del proceso o caída de la base puede dejar
registros pendientes o impedir guardarlos. El registro acredita la respuesta generada
por el servidor, no su recepción por el teléfono. Cada reintento crea otro registro.
