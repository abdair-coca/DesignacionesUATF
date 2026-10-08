# Spec: Copiar y editar designaciones (Jachasun)

Status: Done
Deciders: Dueño del sistema de designaciones
Date: 2026-09-02
Actualizado: 2026-09-03 (funciones corregidas y APLICADAS en la BD real por el
administrador; smoke tests en BD real OK; regla de negocio docente 898 confirmada)

## Solución

### Funciones de base de datos (schema `designaciones`, BD de Jachasun)

#### `f_copiar_designacion` — copiar a nueva gestión/periodo

```sql
CREATE OR REPLACE FUNCTION designaciones.f_copiar_designacion(
    _id          integer,
    _id_gestion  integer,
    _id_periodo  integer,
    _obs         text
) RETURNS character varying
LANGUAGE plpgsql
AS $function$
DECLARE
    vl_id INTEGER;
BEGIN
    INSERT INTO designaciones.asignaciones(id_programa, id_gestion, id_periodo, obs)
    SELECT id_programa, _id_gestion, _id_periodo, _obs
      FROM designaciones.asignaciones
     WHERE id = _id
    RETURNING id INTO vl_id;

    INSERT INTO designaciones.asignaciones_detalles(id_asignaciones, id_materia, id_docente, id_grupo)
    SELECT vl_id, id_materia, IFF(id_docente = 898, 0, id_docente)::INTEGER, id_grupo
      FROM designaciones.asignaciones_detalles
     WHERE id_asignaciones = _id;

    RETURN 'Copia realizada correctamente';
END;
$function$;
```

### Estado verificado en BD (2026-09-02)

El administrador concedió a `usr_designaciones` `INSERT`/`UPDATE`/`SELECT` sobre
`designaciones.asignaciones` y `designaciones.asignaciones_detalles`, y `USAGE`
sobre las secuencias. `f_designacion` quedó con `_fecha character varying` y
fallback a `now()` si viene vacío.

- `f_designacion` con `'UPD'`: **funciona** (devuelve la fila actualizada).
- `f_designacion` con `''` (select por id): **corregido** — `vl_id := _id` al inicio
  hace que el `RETURN QUERY` devuelva la fila por id.
- `f_designacion` con `'INS'`: **corregido** — devuelve la fila recién insertada con
  `estado = 'SOLICITADO'` (default de la columna).
- `f_copiar_designacion`: **corregido** — copia los detalles por
  `WHERE id_asignaciones = _id` con la regla docente 898 → 0 y `estado = 'SOLICITADO'`.

### Corrección aplicada en BD real (2026-09-03, por el administrador)

`scripts-bd.sql` registra las dos funciones tal como quedaron aplicadas en la BD real:

- `f_copiar_designacion`: detalles con `WHERE id_asignaciones = _id` (antes
  `WHERE id = _id`) y regla de negocio confirmada por el administrador:
  `IFF(id_docente = 898, 0, id_docente)` (al copiar, los detalles con docente 898
  se guardan con docente 0).
- `f_designacion`: `vl_id := _id` al inicio, por lo que el `RETURN QUERY`
  (`WHERE id = vl_id`) devuelve la fila en `INS` (tras `RETURNING`), `UPD` y
  select (`''`); incluye los casts `::timestamp`/`::VARCHAR`/`::INTEGER`/`::TEXT`
  que exige el `RETURNS TABLE` (`v_facultades_programas.programa` es `character(3)`).

`estado = 'SOLICITADO'` lo garantiza el `DEFAULT` de la columna
(`'SOLICITADO'::character varying`); no está en el `INSERT` de las funciones.

Smoke tests ejecutados en la BD real (transacciones con rollback, sin efectos):
`INS`/`UPD`/`''` devuelven la fila esperada, `INS` crea con `estado='SOLICITADO'`,
la copia crea fila + detalles con el nuevo `id` (y la regla 898), y el origen
inexistente no crea filas.

#### `f_designacion` — insertar / actualizar / consultar

```sql
CREATE OR REPLACE FUNCTION designaciones.f_designacion(
    _id          integer,
    _fecha       character varying,
    _id_programa character varying,
    _id_gestion  integer,
    _id_periodo  integer,
    _obs         text,
    _tipo        character varying
) RETURNS TABLE(
    fecha       timestamp without time zone,
    id_programa character varying,
    programa    character varying,
    id_gestion  integer,
    id_periodo  integer,
    obs         text
)
LANGUAGE plpgsql
AS $function$
DECLARE
    vl_id    INTEGER;
    vl_fecha TIMESTAMP;
BEGIN
    vl_id := _id;
    vl_fecha := IIF(_fecha IS NULL OR _fecha = '', now()::TEXT, _fecha::TEXT)::TIMESTAMP;

IF _tipo = 'INS' THEN
        INSERT INTO designaciones.asignaciones(fecha, id_programa, id_gestion, id_periodo, obs)
        VALUES (vl_fecha, _id_programa, _id_gestion, _id_periodo, _obs)
        RETURNING id INTO vl_id;
    END IF;

    IF _tipo = 'UPD' THEN
        vl_id := _id;
        UPDATE designaciones.asignaciones
           SET fecha = vl_fecha,
               id_programa = _id_programa,
               id_gestion = _id_gestion,
               id_periodo = _id_periodo,
               obs = _obs
         WHERE id = vl_id;
    END IF;

    RETURN QUERY
        SELECT da.fecha::timestamp,
               da.id_programa::VARCHAR,
               vfp.programa::VARCHAR,
               da.id_gestion::INTEGER,
               da.id_periodo::INTEGER,
               da.obs::TEXT
          FROM designaciones.asignaciones da
               INNER JOIN academico.v_facultades_programas vf
                       ON vfp.id_programa = da.id_programa
         WHERE id = vl_id;
END;
$function$;
```

Estado actual: la definición de arriba es la **aplicada en la BD real** por el
administrador (registrada en `scripts-bd.sql`). `vl_id := _id` al inicio hace que
el `RETURN QUERY` (`WHERE id = vl_id`) devuelva la fila en `INS` (tras el
`RETURNING` reasigna `vl_id`), `UPD` y select (`''`); incluye los casts que exige
el `RETURNS TABLE` (`v_facultades_programas.programa` es `character(3)`).
`estado = 'SOLICITADO'` lo garantiza el `DEFAULT` de la columna; no está en el
`INSERT`.
Con `_tipo = ''` (o cualquier valor distinto de `INS`/`UPD`) solo se ejecuta la
consulta por id (select).

Ejemplos de invocación (verificación de humo):

```sql
SELECT * FROM designaciones.f_designacion(0, '2026-08-31 12:00', 'INF', 2026, 1, 'DESIGNACION', 'INS');
SELECT * FROM designaciones.f_designacion(10, '2026-08-31 12:00', 'INF', 2026, 1, 'DESIGNACION', 'UPD');
SELECT * FROM designaciones.f_designacion(10, NULL, 'INF', 0, 0, NULL, '');
```

### Integración en la aplicación

#### Servicio `App\Services\Jachasun\JachasunDesignacionesService`

Se agregan métodos de escritura (transacción normal, sin `SET TRANSACTION READ ONLY`):

- `copiar(int $id, string $gestion, string $periodo, string $obs): void` — valida que la
  designación origen pertenezca a la sigla de la carrera autenticada y ejecuta
  `designaciones.f_copiar_designacion(?, ?, ?, ?)`.
- `insertar(string $fecha, string $programa, string $gestion, string $periodo, string $obs): array`
  — valida parámetros y ejecuta `f_designacion(0, ?, ?, ?, ?, ?, 'INS')`, devolviendo la
  fila normalizada.
- `actualizar(int $id, string $fecha, string $programa, string $gestion, string $periodo, string $obs): array`
  — ejecuta `f_designacion(?, ?, ?, ?, ?, ?, 'UPD')` devolviendo la fila.
- `obtener(int $id): ?array` — ejecuta `f_designacion(?, NULL, ..., '')` devolviendo la
  fila o `null`.

La validación de entrada se apoya en `validarParametros()` (sigla `[A-Z0-9_-]{2,20}`,
gestión `0` o 4 dígitos, periodo numérico). El programa siempre se toma de la carrera del
usuario autenticado, nunca del navegador.

La fecha se guarda automáticamente y no se pide al usuario: `validarFecha()` acepta
la fecha vacía (`''`) y la delega a la BD, donde `f_designacion` (`INS`/`UPD`) aplica
`now()`, y la copia usa el `DEFAULT (now())` de `designaciones.asignaciones.fecha`.
Al editar se conserva la fecha existente; las designaciones con `fecha = NULL`
reciben la fecha actual automáticamente (antes fallaban al editar).

#### Rutas y controlador

- `POST /designaciones` → `DesignacionController@store`. Crea una designación vacía
  (`f_designacion` `INS`) o, si llega `importar_desde` (id de una designación anterior de
  la misma carrera), la crea copiando con `f_copiar_designacion`.
- `POST /designaciones/{id}` → `DesignacionController@update` (editar cabecera; solo de la
  carrera del director).

Toda escritura valida `id_programa` contra `carrera.sigla` del usuario autenticado antes
de invocar la función, y registra la clase de excepción en el log sin exponer detalles.

#### Vistas

- `resources/views/designaciones/carrera.blade.php`: edición de cabecera (observación;
  la fecha se conserva automáticamente y se oculta el campo) y acciones por fila
  "Editar" (abre el modal de la fila) y "Desasignar"
  (solo UI; sin función de BD para filas todavía). Las filas se ocultan al imprimir.
- `resources/views/designaciones/lista.blade.php`: acceso a crear una designación nueva
  con opción "Importar de una gestión anterior" (selector de designaciones de la carrera
  limitado a la página actual); sin botón de copiado por fila. El campo "Fecha" está
  oculto (la BD la asigna automáticamente); la validación de crear solo exige gestión y
  periodo.

## Invariantes

- Un Director solo copia/crea/edita designaciones de la sigla de su carrera.
- Las operaciones de escritura no usan `SET TRANSACTION READ ONLY`.
- Todos los valores se pasan con parámetros enlazados (nunca concatenados en SQL).
- Los mensajes de error al usuario no revelan detalle de base de datos ni credenciales.
- `f_copiar_designacion` crea una fila en `asignaciones` y sus detalles en
  `asignaciones_detalles` apuntando a la nueva fila; si el origen no existe, no crea nada.
- `f_designacion` con `_tipo='INS'` devuelve la fila recién insertada; con `'UPD'`
  devuelve la fila actualizada; con `''` devuelve la fila por id (vacío si no existe).

## Test cases

- `f_copiar_designacion`: origen existente → nueva fila + detalles con `id_asignaciones`
  = nuevo id; origen inexistente → sin filas nuevas.
- `f_designacion` `INS`: crea la fila con `fecha` timestamp y devuelve esa fila.
- `f_designacion` `UPD`: actualiza `fecha`/`obs` y devuelve la fila actualizada.
- `f_designacion` `''`: devuelve la fila por id.
- Servicio unit: `copiar`/`insertar`/`actualizar` envuelven la llamada en transacción sin
  `READ ONLY` y con parámetros enlazados (patrón de `JachasunDesignacionesServiceTest`).
  `insertar`/`actualizar` con fecha vacía delegan `''` a la BD (`f_designacion` aplica
  `now()`); una fecha con formato inválido no vacía se rechaza.
- Feature: POST `store` (crear o importar desde otra gestión) y `update` con servicio
  mockeado; autorización por carrera; rechazo si la designación pertenece a otra carrera;
  errores de Jachasun devuelven mensaje seguro y no exponen el detalle. Crear vacía sin
  `fecha` y editar con `fecha = NULL` existente envían `''` y guardan (regresión del
  fallo de fecha nula).
- Smoke en BD: ejecutar los tres `SELECT ... f_designacion(...)` de ejemplo y una copia.

## Riesgos

- Ampliar el contrato de solo lectura contra la BD externa de Jachasun: requiere que el
  servidor de Jachasun autorice las escrituras de la IP de la aplicación en `pg_hba.conf`
  y que `usr_designaciones` tenga permiso de `INSERT`/`UPDATE` sobre
  `designaciones.asignaciones` y `designaciones.asignaciones_detalles`.
- `estado` queda `NULL` en filas nuevas si no se confirma la regla de negocio.
- `UPD` puede cambiar `id_programa` si se pasa otro valor; mitigado por la validación de
  carrera en el servicio (el programa proviene del usuario autenticado).

## Dependencias

- Funciones `f_asignaciones` / `f_asignaciones_detalles` y vista
  `academico.v_facultades_programas` (ya existentes).
- Permisos de escritura en Jachasun (gestión externa).
- Confirmación de negocio de las reglas marcadas `NEEDS_BUSINESS_CONFIRMATION` en
  `proposal.md`.