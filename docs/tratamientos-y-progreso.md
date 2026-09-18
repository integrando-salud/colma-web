# Catálogo de tratamientos y progreso

## Fuente de verdad y versionado

La estructura del catálogo se declara exclusivamente en
`storage/app/private/tratamientos/content.json`. El filesystem contiene recursos
físicos: no se usa para descubrir tratamientos, semanas, sesiones ni elementos.

El comando `php artisan tratamientos:sincronizar-catalogo` valida el contrato,
comprueba la existencia de cada ruta declarada y calcula su checksum SHA-256.
El hash de una versión incluye el contenido lógico y los checksums de sus
recursos. Un cambio en cualquiera de los dos crea una `TratamientoVersion` nueva.
Cada `Entrenamiento` conserva `tratamiento_version_id`, por lo que las consultas
de un entrenamiento existente no cambian al importarse una versión posterior.

## Modelo persistido

```text
TratamientoVersion
  └─ TratamientoSesion
       └─ TratamientoElemento (tipo, orden, contenido)
            └─ TratamientoMultimedia (ruta, tipo, checksum)
```

`TratamientoElemento.orden` es el índice base cero de `multimedia[]` en la
subsesión. La combinación `tratamiento_sesion_id + orden` es única. Cada recurso
físico pertenece a un elemento y llega a la sesión mediante esa relación; no hay
una FK directa desde el recurso hacia la sesión.

Los tipos vigentes son `DESCRIPTION`, `VIDEO`, `QUIZ` y `METAS`. Todos cuentan
para completitud.

| Tipo | Recursos físicos |
|---|---|
| DESCRIPTION | AUDIO e IMAGE opcionales; ambos pertenecen al mismo elemento. |
| VIDEO | Un VIDEO obligatorio. |
| QUIZ | Ninguno; preguntas y opciones permanecen en `contenido`. |
| METAS | Ninguno; opciones de metas permanecen en `contenido`. |

El importador rechaza tipos desconocidos, recursos no permitidos, paths vacíos,
inexistentes o repetidos, y estructuras obligatorias inválidas.

## Progreso y completitud

`ProgresoElemento` vincula `paciente_id`, `entrenamiento_id` y
`tratamiento_elemento_id`. Esa combinación es única. Tiene estado reversible
`completado`, `fecha_estado` del dispositivo y `fecha_sincronizacion`.

La app decide cuándo un DESCRIPTION fue leído, un VIDEO finalizó, un QUIZ fue
respondido o un bloque METAS fue cumplido. Laravel persiste esa señal y descarta
eventos cuya `fecha_estado` sea anterior al estado existente.

Una sesión está completa cuando tiene al menos un elemento y todos sus elementos
tienen un progreso del mismo paciente y entrenamiento con `completado: true`.
Las sesiones sin elementos no se completan por la igualdad vacía. Una semana se
completa cuando todas sus sesiones con elementos están completas. El resumen del
entrenamiento se deriva dinámicamente de su versión y de sus progresos, por lo
que una reversión se propaga a sesión, semana, entrenamiento, dashboard e
indicadores.

`metas` y `progreso_elementos` no son duplicados: `metas` conserva el detalle
funcional de cada actividad y `meta_cumplida`; `progreso_elementos` registra la
consecuencia pedagógica del bloque METAS completo.

| Tipo | Datos funcionales adicionales |
|---|---|
| DESCRIPTION | Sólo `ProgresoElemento`. |
| VIDEO | Sólo `ProgresoElemento`. |
| QUIZ | `ProgresoElemento` y una `RespuestaQuiz` con preguntas y respuestas completas. |
| METAS | `ProgresoElemento` y una o más filas `Meta` con actividades individuales. |

`RespuestaQuiz` es única por paciente, entrenamiento y elemento QUIZ. `Meta`
es única por paciente, entrenamiento, elemento METAS e `id_meta` local. El
servidor valida el tipo y la pertenencia a la versión del entrenamiento antes de
persistir ambos datos; ninguno altera automáticamente el progreso pedagógico.
Los reintentos son idempotentes: para QUIZ prevalece la mayor `fecha_usuario` y
para una Meta la mayor `fecha_edicion` (o `fecha_usuario` si no fue editada).
Un evento con la misma fecha o una fecha anterior no reemplaza el estado
funcional ya persistido.

## API móvil

### `GET /api/tratamiento`

Construye semanas, sesiones y `content` desde la versión persistida del
entrenamiento activo. Cada objeto de `content` incluye
`tratamiento_elemento_id`. Los recursos se exponen a partir de
`TratamientoMultimedia`; `tratamiento_multimedia_id` no forma parte del contrato
de progreso ni de la respuesta.

### `POST /api/sync-data-paciente`

El bloque de progreso es:

```json
{
  "userId": 42,
  "progreso_elementos": [
    {
      "tratamiento_elemento_id": 1306,
      "completado": true,
      "fecha_estado": "2026-08-20 10:30:00",
      "fecha_sincronizacion": "2026-08-20 10:35:00"
    }
  ]
}
```

El servidor deriva tipo, sesión, semana, tratamiento y versión desde el
elemento, y valida que pertenezca a la versión del entrenamiento activo. El
endpoint también continúa aceptando bloques independientes de glucosa, presión,
estado de ánimo, `quiz` y metas. No acepta `multimedia_read`.

Cada objeto `quiz` usa `tratamiento_elemento_id`, las tres fechas y el array
histórico completo `preguntas`; no requiere `id_quiz`, `quiz_type` ni
`id_usuario`. Cada objeto `metas` usa `id_meta` para la actividad individual y
`tratamiento_elemento_id` para el bloque METAS; no envía `semana`, `sesion` ni
`id_usuario`.

```json
{
  "quiz": [{
    "tratamiento_elemento_id": 1450,
    "fecha_registro": "2026-08-20 10:30:00",
    "fecha_usuario": "2026-08-20 10:30:00",
    "fecha_sincronizacion": "2026-08-20 10:35:00",
    "preguntas": [{
      "pregunta_index": 0,
      "pregunta_texto": "...",
      "respuesta_usuario": 1,
      "respuesta_usuario_texto": "...",
      "respuesta_correcta": 2,
      "respuesta_correcta_texto": "...",
      "es_correcta": false,
      "posibles_respuestas": ["...", "..."]
    }]
  }],
  "metas": [{
    "id_meta": 123,
    "tratamiento_elemento_id": 1315,
    "tipo_actividad": "...",
    "actividad": "...",
    "lugar": null,
    "acompaniante": null,
    "meta_cumplida": true,
    "motivo_incumplimiento": null,
    "fecha_registro": "2026-08-20 10:30:00",
    "fecha_usuario": "2026-08-20 10:30:00",
    "fecha_sincronizacion": "2026-08-20 10:35:00",
    "fecha_edicion": null,
    "fecha_objetivo": "2026-08-25 10:30:00"
  }]
}
```

### `GET /api/elementos-completados`

Devuelve la restauración de progreso para el entrenamiento activo:

```json
{
  "elementos_completados": [1306, 1315]
}
```

Incluye sólo IDs cuyo estado actual es `completado: true`; la ausencia de un ID
representa un elemento pendiente. No existe la ruta legacy
`/api/multimedia-completados`.

### `GET /api/datos-funcionales-tratamiento`

Devuelve los arrays `quiz` y `metas` del entrenamiento activo. Tras una
reinstalación, la app consulta tratamiento, elementos completados y este endpoint
para reconstruir estructura, progreso y datos funcionales respectivamente.
