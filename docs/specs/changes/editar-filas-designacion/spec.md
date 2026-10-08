# Spec: Editar filas de detalle de una designación (Jachasun)

Status: Implementado y aplicado
Deciders: Dueño del sistema de designaciones
Date: 2026-09-03
Actualizado: 2026-09-04 (función APLICADA en la BD real por el administrador y
smoke verificado por la conexión de la app; la versión aplicada difiere de la
propuesta original en el `RETURNS TABLE`)

## Solución

### Función de base de datos (schema `designaciones`, BD de Jachasun)

#### `f_designacion_detalle` — insertar / actualizar una fila de detalle

Firma corregida (validada con función temporal en `public` + rollback; ver
`repro-bd.md`):

```sql
CREATE OR REPLACE FUNCTION designaciones.f_designacion_detalle(
    _id              integer,   -- id de la designación (asignaciones.id)
    _id_detalle      integer,   -- id del detalle; 0 = insertar, >0 = actualizar
    _id_docente      integer,
    _id_materia      integer,
    _id_grupo        integer,
    _hrs_teoria      integer,
    _hrs_practica    integer,
    _hrs_laboratorio integer
) RETURNS TABLE(
    r_id, r_id_docente, r_ci, r_nombres, r_id_materia, r_sigla, r_materia,
    r_id_grupo, r_hrs_teoricas, r_hrs_practicas, r_hrs_laboratorio
)
```

Comportamiento:
- `_id_detalle = 0` → `INSERT` de una fila nueva en `asignaciones_detalles`
  para la designación `_id` (materia, docente, grupo y horas).
- `_id_detalle > 0` → `UPDATE` de la fila **solo si** `id = _id_detalle AND
  id_asignaciones = _id`; además actualiza `fecha = now()`.
- `_id` inexistente o `_id_detalle > 0` inexistente/perteneciente a otra
  designación → no-op.
- El `RETURN QUERY` devuelve las filas visibles de la designación `_id` (mismo
  formato que `f_asignaciones_detalles`, incluyendo `r_ci`, `r_sigla` y el
  nombre con `abre_titulo_a`), con las horas guardadas en el detalle
  (`dad.hrs_*`) y excluyendo docentes 0 y 898 (`NOT IN (0, 898)`).

Definición aplicada en `scripts-bd.sql` (2026-09-04). La versión aplicada por el
administrador usa `RETURNS TABLE` de 14 columnas (`r_id, r_id_detalle,
r_id_gestion, r_id_periodo, r_id_materia, r_materia, r_id_docente, r_docente,
r_id_grupo, r_hrs_teoricas, r_hrs_practicas, r_hrs_laboratorio, r_id_programa,
r_programa`) y devuelve **solo la fila afectada** (`WHERE dad.id =
vl_id_detalle`), sin filtro `NOT IN (0, 898)`. La vista quedó calificada como
`academico.v_facultades_programas` (sin `academico.` la función fallaba con
`42P01` para la app, cuyo search_path no incluye `academico`). La app no usa el
resultado de esta función para refrescar la vista (el controlador redirige y
`show()` relee con `f_asignaciones_detalles`), por lo que las diferencias de
columnas no afectan el flujo.

### Integración en la aplicación

#### Servicio `App\Services\Jachasun\JachasunDesignacionesService`

Se agrega un método de escritura (transacción normal, sin `READ ONLY`):

- `guardarDetalle(int $id, string $programa, int $idDetalle, int $idDocente,
  int $idMateria, int $idGrupo, int $hrsTeoria, int $hrsPractica,
  int $hrsLaboratorio): Collection` — valida los parámetros, verifica que la
  designación pertenezca a la carrera autenticada (`listar($programa, '0', '0')`)
  y ejecuta `designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)`
  dentro de `escribir()`, normalizando las filas con `normalizarDetalle()`.

#### Controlador y ruta

- `GET /designaciones/{id}` (`show`) pasa además a la vista los catálogos de
  la designación: `docentesDisponibles` y `materiasDisponibles`, derivados de
  las filas ya leídas (sin consultas adicionales a Jachasun).
- `POST /designaciones/{id}/detalle` → `DesignacionController@actualizarDetalle`
  (nueva). Valida el request, autoriza por carrera y ejecuta `guardarDetalle`;
  los errores de Jachasun devuelven un mensaje genérico y registran solo la
  clase de excepción.

#### Vista `resources/views/designaciones/carrera.blade.php`

El modal "Editar" de cada fila se conecta al nuevo endpoint:
- **Docente** y **Materia** pasan a ser `<select>` con las opciones de
  `docentesDisponibles` / `materiasDisponibles` (solo los que ya figuran en la
  designación), preseleccionando los valores actuales de la fila.
- El **CI** mostrado es el del docente seleccionado (derivado del catálogo).
- **Grupo** y **horas** siguen siendo inputs numéricos.
- El botón "Guardar cambios" envía el formulario al nuevo endpoint con
  confirmación (patrón de la edición de cabecera).
- "Desasignar" permanece solo UI.

## Invariantes

- Un Director solo edita filas de detalle de designaciones de la sigla de su carrera.
- Solo se pueden seleccionar docentes/materias que ya figuran en la designación.
- La escritura usa parámetros enlazados y transacción normal (sin `READ ONLY`).
- Los mensajes de error al usuario no revelan detalle de base de datos ni credenciales.
- Docentes 0 y 898 no se asignan desde la UI ni aparecen en el resultado.

## Test cases

- Servicio unit: `guardarDetalle` ejecuta el SQL exacto con parámetros enlazados
  en transacción sin `READ ONLY`, rechaza designaciones de otra carrera y
  normaliza el resultado (patrón `JachasunDesignacionesServiceTest`).
- Feature: `POST /designaciones/{id}/detalle` con servicio mockeado — autorización
  por carrera, rechazo de otra carrera y error Jachasun con mensaje seguro
  (patrón `JachasunDesignacionesEscrituraTest`).
- Smoke BD: UPDATE, INSERT, no-op de detalle ajeno e id inexistente en
  transacciones con rollback (ejecutado; ver `repro-bd.md`).

## Riesgos

- `scripts-bd.sql` requiere ser aplicado por el administrador en la BD real y
  que `usr_designaciones` tenga permiso de `INSERT`/`UPDATE` sobre
  `asignaciones_detalles` (concedido en la feature anterior).
- Fuente de horas sin conciliar entre listado (`pln_materias`) y escritura
  (manual): el detalle editado puede mostrar horas distintas a las de la materia.
- `fecha = now()` en cada UPDATE de fila: comportamiento heredado, a confirmar
  con el dueño si debe conservarse.

## Dependencias

- Funciones `f_asignaciones` / `f_asignaciones_detalles` y vista
  `academico.v_facultades_programas` (ya existentes).
- Permisos de escritura en Jachasun (gestión externa).
- Aplicación de `scripts-bd.sql` por el administrador.